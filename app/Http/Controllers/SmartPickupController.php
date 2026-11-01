<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use App\Models\Rental;
use App\Models\UserLogisticsPreference;
use App\Services\DeliveryBookingService;
use App\Services\SmartPickupRecommendationService;
use Carbon\Carbon;
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
        $preference = $request->user()->logisticsPreference()->with('preferredPickupPoint')->first();
        $preferences = $this->resolvePreferences($validated, $preference);
        $results = [];

        if (isset($validated['date'])) {
            $results = $recommendations->recommend(
                $rental,
                Carbon::createFromFormat('Y-m-d', $validated['date']),
                $validated['preferred_time'] ?? null,
                isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                isset($validated['longitude']) ? (float) $validated['longitude'] : null,
                $preferences['favorite_pickup_point_id'],
                $preferences['preferred_city'],
                $preferences['preferred_start_time'],
                $preferences['preferred_end_time']
            );
        }

        $favoritePoint = $preferences['favorite_pickup_point_id'] === null
            ? null
            : ($preference?->preferredPickupPoint?->id === $preferences['favorite_pickup_point_id']
                ? $preference->preferredPickupPoint
                : PickupPoint::find($preferences['favorite_pickup_point_id']));
        $pickupPoints = PickupPoint::query()
            ->where('status', 'active')
            ->orderBy('city')
            ->orderBy('name')
            ->get();
        if ($favoritePoint && ! $pickupPoints->contains('id', $favoritePoint->id)) {
            $pickupPoints->prepend($favoritePoint);
        }
        $favoriteAvailable = $preferences['favorite_pickup_point_id'] !== null && isset($validated['date'])
            ? collect($results)->contains('pickup_point_id', $preferences['favorite_pickup_point_id'])
            : null;

        return view('smart-pickup.index', [
            'rental' => $rental->load('equipment'),
            'rentals' => collect(),
            'results' => $results,
            'isAdmin' => false,
            'pickupPoints' => $pickupPoints,
            'personalization' => [
                'enabled' => $preference !== null || collect($preferences)->contains(fn ($value) => filled($value)),
                'favorite_name' => $favoritePoint?->name,
                'favorite_available' => $favoriteAvailable,
                'alternative_name' => $favoriteAvailable === false ? ($results[0]['name'] ?? null) : null,
                'preferred_city' => $preferences['preferred_city'],
                'preferred_start_time' => $preferences['preferred_start_time'],
                'preferred_end_time' => $preferences['preferred_end_time'],
            ],
            'formRoute' => route('rentals.smart-pickup', $rental),
            'confirmRoute' => route('rentals.smart-pickup.confirm', $rental),
            'selectedDate' => $validated['date'] ?? '',
            'preferredTime' => $validated['preferred_time'] ?? '',
            'latitude' => $validated['latitude'] ?? '',
            'longitude' => $validated['longitude'] ?? '',
            'preferences' => $preferences,
        ]);
    }

    public function confirm(
        Request $request,
        Rental $rental,
        SmartPickupRecommendationService $recommendations
    ): View {
        $this->authorizeRental($rental);
        $validated = $this->validateSelection($request, includeRental: false);
        $recommendation = $this->findAvailableSelection($rental, $validated, $recommendations, usePreferences: true);
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
            'favorite_pickup_point_id' => ['nullable', 'integer', 'exists:pickup_points,id'],
            'preferred_city' => ['nullable', 'string', 'max:100'],
            'preferred_start_time' => ['nullable', 'required_with:preferred_end_time', 'date_format:H:i'],
            'preferred_end_time' => ['nullable', 'required_with:preferred_start_time', 'date_format:H:i', 'after:preferred_start_time'],
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
            'favorite_pickup_point_id' => ['nullable', 'integer', 'exists:pickup_points,id'],
            'preferred_city' => ['nullable', 'string', 'max:100'],
            'preferred_start_time' => ['nullable', 'required_with:preferred_end_time', 'date_format:H:i'],
            'preferred_end_time' => ['nullable', 'required_with:preferred_start_time', 'date_format:H:i', 'after:preferred_start_time'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);
    }

    public function findAvailableSelection(
        Rental $rental,
        array $selection,
        SmartPickupRecommendationService $recommendations,
        bool $usePreferences = false
    ): array {
        $preferences = $usePreferences
            ? $this->resolvePreferences(
                $selection,
                auth()->user()->logisticsPreference()->first()
            )
            : [
                'favorite_pickup_point_id' => null,
                'preferred_city' => null,
                'preferred_start_time' => null,
                'preferred_end_time' => null,
            ];
        $ranked = $recommendations->recommend(
            $rental,
            Carbon::createFromFormat('Y-m-d', $selection['date']),
            $selection['preferred_time'] ?? null,
            isset($selection['latitude']) ? (float) $selection['latitude'] : null,
            isset($selection['longitude']) ? (float) $selection['longitude'] : null,
            $preferences['favorite_pickup_point_id'],
            $preferences['preferred_city'],
            $preferences['preferred_start_time'],
            $preferences['preferred_end_time']
        );
        $selected = collect($ranked)->firstWhere('pickup_point_id', (int) $selection['pickup_point_id']);

        if (! $selected || ! collect($selected['slots'])->contains('start', $selection['scheduled_time'])) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'Ce créneau n’est plus disponible. Relancez la recherche et choisissez une autre heure.',
            ]);
        }

        return $selected;
    }

    private function resolvePreferences(array $provided, ?UserLogisticsPreference $saved): array
    {
        $preferenceValues = [
            'favorite_pickup_point_id' => $saved?->preferred_pickup_point_id,
            'preferred_city' => $saved?->preferred_city,
            'preferred_start_time' => $saved?->preferred_start_time
                ? substr($saved->preferred_start_time, 0, 5)
                : null,
            'preferred_end_time' => $saved?->preferred_end_time
                ? substr($saved->preferred_end_time, 0, 5)
                : null,
        ];

        foreach (array_keys($preferenceValues) as $key) {
            if (array_key_exists($key, $provided)) {
                $preferenceValues[$key] = $provided[$key] === null
                    ? null
                    : ($key === 'favorite_pickup_point_id' ? (int) $provided[$key] : $provided[$key]);
            }
        }

        return $preferenceValues;
    }

    private function authorizeRental(Rental $rental): void
    {
        abort_unless($rental->renter_id === auth()->id(), 403);
    }
}
