<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\PickupPoint;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function create(Rental $rental)
    {
        $this->authorizeRental($rental);

        $delivery = $rental->deliveries()->latest()->first();

        if ($delivery) {
            return redirect()->route('deliveries.edit', $delivery);
        }

        $pickupPoints = PickupPoint::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('deliveries.form', compact(
            'rental',
            'pickupPoints'
        ));
    }

    public function store(Request $request, Rental $rental)
    {
        $this->authorizeRental($rental);

        $validated = $this->validateDelivery($request);

        if ($validated['type'] === 'home_delivery') {
            $validated['pickup_point_id'] = null;
        }

        $validated['rental_id'] = $rental->id;
        $validated['status'] = 'planned';

        Delivery::create($validated);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('status', 'Votre mode de livraison a été enregistré.');
    }

    public function edit(Delivery $delivery)
    {
        $this->authorizeDelivery($delivery);

        $rental = $delivery->rental;

        $pickupPoints = PickupPoint::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('deliveries.form', compact(
            'delivery',
            'rental',
            'pickupPoints'
        ));
    }

    public function update(Request $request, Delivery $delivery)
    {
        $this->authorizeDelivery($delivery);

        $validated = $this->validateDelivery($request);

        if ($validated['type'] === 'home_delivery') {
            $validated['pickup_point_id'] = null;
        }

        $delivery->update($validated);

        return redirect()
            ->route('rentals.show', $delivery->rental)
            ->with('status', 'Votre livraison a été modifiée.');
    }

    public function destroy(Delivery $delivery)
    {
        $this->authorizeDelivery($delivery);

        $rental = $delivery->rental;

        $delivery->delete();

        return redirect()
            ->route('rentals.show', $rental)
            ->with('status', 'La livraison a été supprimée.');
    }

    private function validateDelivery(Request $request): array
    {
        return $request->validate(
            [
                'type' => [
                    'required',
                    Rule::in(['pickup', 'home_delivery']),
                ],

                'pickup_point_id' => [
                    Rule::requiredIf(
                        $request->input('type') === 'pickup'
                    ),
                    'nullable',
                    'integer',
                    'exists:pickup_points,id',
                ],

                'scheduled_date' => [
                    'required',
                    'date',
                ],

                'scheduled_time' => [
                    'required',
                    'date_format:H:i',
                ],
            ],
            [
                'type.required' =>
                    'Veuillez choisir un mode de livraison.',

                'type.in' =>
                    'Le mode de livraison sélectionné est invalide.',

                'pickup_point_id.required' =>
                    'Veuillez choisir un point de retrait.',

                'pickup_point_id.exists' =>
                    'Le point de retrait sélectionné est invalide.',

                'scheduled_date.required' =>
                    'La date est obligatoire.',

                'scheduled_date.date' =>
                    'La date sélectionnée est invalide.',

                'scheduled_time.required' =>
                    'L’heure est obligatoire.',

                'scheduled_time.date_format' =>
                    'L’heure sélectionnée est invalide.',
            ]
        );
    }

    private function authorizeRental(Rental $rental): void
    {
        if ($rental->renter_id !== Auth::id()) {
            abort(403);
        }
    }

    private function authorizeDelivery(Delivery $delivery): void
    {
        $delivery->loadMissing('rental');

        if ($delivery->rental->renter_id !== Auth::id()) {
            abort(403);
        }
    }
}