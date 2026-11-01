<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\DeliveryBookingService;
use App\Services\SmartPickupRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SmartPickupController extends Controller
{
    public function index(
        Request $request,
        Rental $rental,
        SmartPickupRecommendationService $recommendations
    ): View {
        $this->authorizeRental($rental);
        $validated = $this->validateSearch($request);
        $results = [];

        if (isset($validated['date'])) {
            $results = $recommendations->recommend(
                $rental,
                \Carbon\Carbon::createFromFormat('Y-m-d', $validated['date']),
                $validated['preferred_time'] ?? null,
                isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                isset($validated['longitude']) ? (float) $validated['longitude'] : null
            );
        }

        return view('smart-pickup.index', [
            'rental' => $rental->load('equipment'),
            'rentals' => collect(),
            'results' => $results,
            'isAdmin' => false,
            'formRoute' => route('rentals.smart-pickup', $rental),
            'confirmRoute' => route('rentals.smart-pickup.confirm', $rental),
            'selectedDate' => $validated['date'] ?? '',
            'preferredTime' => $validated['preferred_time'] ?? '',
            'latitude' => $validated['latitude'] ?? '',
            'longitude' => $validated['longitude'] ?? '',
        ]);
    }

    public function confirm(
        Request $request,
        Rental $rental,
        SmartPickupRecommendationService $recommendations
    ): View {
        $this->authorizeRental($rental);
        $validated = $this->validateSelection($request, includeRental: false);
        $recommendation = $this->findAvailableSelection($rental, $validated, $recommendations);
        $delivery = $rental->deliveries()
            ->where('type', 'pickup')
            ->where('status', 'planned')
            ->latest('id')
            ->first();

        return view('smart-pickup.confirmation', [
            'rental' => $rental->load('equipment'),
            'recommendation' => $recommendation,
            'selectedTime' => $validated['scheduled_time'],
            'isAdmin' => false,
            'bookRoute' => route('rentals.smart-pickup.book', $rental),
            'deliveryFee' => $delivery?->delivery_fee ?? 0,
            'selection' => $validated,
        ]);
    }

    public function book(
        Request $request,
        Rental $rental,
        DeliveryBookingService $booking
    ) {
        $this->authorizeRental($rental);
        $validated = $this->validateSelection($request, includeRental: false);
        $request->validate(['confirmed' => ['required', 'accepted']]);

        $result = $booking->bookPickup(
            $rental,
            (int) $validated['pickup_point_id'],
            $validated['date'],
            $validated['scheduled_time'],
            $request->user()
        );

        return redirect()
            ->route('rentals.show', $rental)
            ->with(
                'status',
                $result['changed']
                    ? 'Votre rendez-vous de retrait est confirmé.'
                    : 'Ce rendez-vous de retrait était déjà confirmé.'
            );
    }

    public function validateSearch(Request $request): array
    {
        return $request->validate([
            'rental_id' => ['nullable', 'integer', 'exists:rentals,id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);
    }

    public function validateSelection(Request $request, bool $includeRental = true): array
    {
        return $request->validate([
            'rental_id' => $includeRental ? ['required', 'integer', 'exists:rentals,id'] : ['sometimes', 'integer'],
            'pickup_point_id' => ['required', 'integer', 'exists:pickup_points,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);
    }

    public function findAvailableSelection(
        Rental $rental,
        array $selection,
        SmartPickupRecommendationService $recommendations
    ): array {
        $ranked = $recommendations->recommend(
            $rental,
            \Carbon\Carbon::createFromFormat('Y-m-d', $selection['date']),
            $selection['preferred_time'] ?? null,
            isset($selection['latitude']) ? (float) $selection['latitude'] : null,
            isset($selection['longitude']) ? (float) $selection['longitude'] : null
        );
        $selected = collect($ranked)->firstWhere('pickup_point_id', (int) $selection['pickup_point_id']);

        if (! $selected || ! collect($selected['slots'])->contains('start', $selection['scheduled_time'])) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'Ce créneau n’est plus disponible. Relancez la recherche et choisissez une autre heure.',
            ]);
        }

        return $selected;
    }

    private function authorizeRental(Rental $rental): void
    {
        abort_unless($rental->renter_id === auth()->id(), 403);
    }
}
