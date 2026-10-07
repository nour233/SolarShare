<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\PickupPoint;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = Delivery::with([
            'rental.equipment',
            'rental.renter',
            'pickupPoint',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Status filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        /*
        |--------------------------------------------------------------------------
        | Type filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $deliveries = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $counts = [
            'all' => Delivery::count(),

            'planned' => Delivery::where(
                'status',
                'planned'
            )->count(),

            'in_transit' => Delivery::where(
                'status',
                'in_transit'
            )->count(),

            'delivered' => Delivery::where(
                'status',
                'delivered'
            )->count(),

            'returned' => Delivery::where(
                'status',
                'returned'
            )->count(),
        ];

        return view(
            'back.deliveries.index',
            compact('deliveries', 'counts')
        );
    }


    public function create()
    {
        $delivery = new Delivery();

        $rentals = Rental::with([
            'equipment',
            'renter',
        ])
            ->latest()
            ->get();

        $pickupPoints = PickupPoint::where(
            'status',
            'active'
        )
            ->orderBy('name')
            ->get();

        return view(
            'back.deliveries.form',
            compact(
                'delivery',
                'rentals',
                'pickupPoints'
            )
        );
    }


    public function store(Request $request)
    {
        $validated = $this->validateDelivery($request);

        /*
        |--------------------------------------------------------------------------
        | Home delivery has no pickup point
        |--------------------------------------------------------------------------
        */

        if ($validated['type'] === 'home_delivery') {
            $validated['pickup_point_id'] = null;
        }

        Delivery::create($validated);

        return redirect()
            ->route('admin.deliveries.index')
            ->with(
                'success',
                'Livraison ajoutée avec succès.'
            );
    }


    public function show(Delivery $delivery)
    {
        $delivery->load([
            'rental.equipment',
            'rental.renter',
            'pickupPoint',
        ]);

        return view(
            'back.deliveries.show',
            compact('delivery')
        );
    }


    public function edit(Delivery $delivery)
    {
        $rentals = Rental::with([
            'equipment',
            'renter',
        ])
            ->latest()
            ->get();

        $pickupPoints = PickupPoint::where(
            'status',
            'active'
        )
            ->orderBy('name')
            ->get();

        return view(
            'back.deliveries.form',
            compact(
                'delivery',
                'rentals',
                'pickupPoints'
            )
        );
    }


    public function update(
        Request $request,
        Delivery $delivery
    ) {
        $validated = $this->validateDelivery($request);

        if ($validated['type'] === 'home_delivery') {
            $validated['pickup_point_id'] = null;
        }

        $delivery->update($validated);

        return redirect()
            ->route('admin.deliveries.index')
            ->with(
                'success',
                'Livraison modifiée avec succès.'
            );
    }


    public function destroy(Delivery $delivery)
    {
        $delivery->delete();

        return redirect()
            ->route('admin.deliveries.index')
            ->with(
                'success',
                'Livraison supprimée avec succès.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Backend validation
    |--------------------------------------------------------------------------
    */

    private function validateDelivery(Request $request): array
    {
        return $request->validate(
            [
                'rental_id' => [
                    'required',
                    'integer',
                    'exists:rentals,id',
                ],

                'type' => [
                    'required',
                    Rule::in([
                        'pickup',
                        'home_delivery',
                    ]),
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

                'delivery_fee' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:10000',
                ],

                'status' => [
                    'required',
                    Rule::in([
                        'planned',
                        'in_transit',
                        'delivered',
                        'returned',
                    ]),
                ],
            ],

            [
                'rental_id.required' =>
                    'Veuillez sélectionner une location.',

                'rental_id.exists' =>
                    'La location sélectionnée est invalide.',

                'type.required' =>
                    'Veuillez sélectionner un type de livraison.',

                'type.in' =>
                    'Le type de livraison sélectionné est invalide.',

                'pickup_point_id.required' =>
                    'Veuillez sélectionner un point de retrait.',

                'pickup_point_id.exists' =>
                    'Le point de retrait sélectionné est invalide.',

                'scheduled_date.required' =>
                    'La date de livraison est obligatoire.',

                'scheduled_date.date' =>
                    'La date de livraison est invalide.',

                'scheduled_time.required' =>
                    'L’heure de livraison est obligatoire.',

                'scheduled_time.date_format' =>
                    'L’heure de livraison est invalide.',

                'delivery_fee.required' =>
                    'Les frais de livraison sont obligatoires.',

                'delivery_fee.numeric' =>
                    'Les frais de livraison doivent être un nombre.',

                'delivery_fee.min' =>
                    'Les frais de livraison ne peuvent pas être négatifs.',

                'status.required' =>
                    'Le statut est obligatoire.',

                'status.in' =>
                    'Le statut sélectionné est invalide.',
            ]
        );
    }
}