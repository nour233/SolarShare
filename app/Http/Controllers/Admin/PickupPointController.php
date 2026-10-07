<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use Illuminate\Http\Request;

class PickupPointController extends Controller
{
    /**
     * Display all pickup points.
     */
    public function index(Request $request)
    {
        $query = PickupPoint::query();

        /*
        |--------------------------------------------------------------------------
        | Filter by status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $pickupPoints = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $counts = [
            'all' => PickupPoint::count(),

            'active' => PickupPoint::where(
                'status',
                'active'
            )->count(),

            'inactive' => PickupPoint::where(
                'status',
                'inactive'
            )->count(),
        ];

        return view(
            'back.pickup-points.index',
            compact('pickupPoints', 'counts')
        );
    }


    /**
     * Show create form.
     */
    public function create()
    {
        $pickupPoint = new PickupPoint();

        return view(
            'back.pickup-points.form',
            compact('pickupPoint')
        );
    }


    /**
     * Store a new pickup point.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePickupPoint($request);

        PickupPoint::create($validated);

        return redirect()
            ->route('admin.pickup-points.index')
            ->with(
                'success',
                'Point de retrait ajouté avec succès.'
            );
    }


    /**
     * Show pickup point details.
     */
    public function show(PickupPoint $pickupPoint)
    {
        $pickupPoint->loadCount('deliveries');

        return view(
            'back.pickup-points.show',
            compact('pickupPoint')
        );
    }


    /**
     * Show edit form.
     */
    public function edit(PickupPoint $pickupPoint)
    {
        return view(
            'back.pickup-points.form',
            compact('pickupPoint')
        );
    }


    /**
     * Update pickup point.
     */
    public function update(
        Request $request,
        PickupPoint $pickupPoint
    ) {
        $validated = $this->validatePickupPoint($request);

        $pickupPoint->update($validated);

        return redirect()
            ->route('admin.pickup-points.index')
            ->with(
                'success',
                'Point de retrait modifié avec succès.'
            );
    }


    /**
     * Delete pickup point.
     */
    public function destroy(PickupPoint $pickupPoint)
    {
        $pickupPoint->delete();

        return redirect()
            ->route('admin.pickup-points.index')
            ->with(
                'success',
                'Point de retrait supprimé avec succès.'
            );
    }


    /**
     * Backend validation for create and update.
     */
    private function validatePickupPoint(Request $request): array
    {
        return $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'min:3',
                    'max:255',
                ],

                'city' => [
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                ],

                'address' => [
                    'required',
                    'string',
                    'min:5',
                    'max:255',
                ],

                'latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],

                'opening_hours' => [
                    'required',
                    'string',
                    'min:3',
                    'max:255',
                ],

                'capacity' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:10000',
                ],

                'status' => [
                    'required',
                    'in:active,inactive',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | French validation messages
            |--------------------------------------------------------------------------
            */

            [
                'name.required' =>
                    'Le nom du point de retrait est obligatoire.',

                'name.min' =>
                    'Le nom doit contenir au moins 3 caractères.',

                'name.max' =>
                    'Le nom ne peut pas dépasser 255 caractères.',


                'city.required' =>
                    'La ville est obligatoire.',

                'city.min' =>
                    'La ville doit contenir au moins 2 caractères.',

                'city.max' =>
                    'La ville ne peut pas dépasser 255 caractères.',


                'address.required' =>
                    'L’adresse est obligatoire.',

                'address.min' =>
                    'L’adresse doit contenir au moins 5 caractères.',

                'address.max' =>
                    'L’adresse ne peut pas dépasser 255 caractères.',


                'latitude.required' =>
                    'Veuillez sélectionner un emplacement sur la carte.',

                'latitude.numeric' =>
                    'La latitude doit être valide.',

                'latitude.between' =>
                    'La latitude doit être comprise entre -90 et 90.',


                'longitude.required' =>
                    'Veuillez sélectionner un emplacement sur la carte.',

                'longitude.numeric' =>
                    'La longitude doit être valide.',

                'longitude.between' =>
                    'La longitude doit être comprise entre -180 et 180.',


                'opening_hours.required' =>
                    'Les horaires d’ouverture sont obligatoires.',

                'opening_hours.min' =>
                    'Veuillez saisir des horaires valides.',


                'capacity.required' =>
                    'La capacité est obligatoire.',

                'capacity.integer' =>
                    'La capacité doit être un nombre entier.',

                'capacity.min' =>
                    'La capacité doit être supérieure à 0.',

                'capacity.max' =>
                    'La capacité ne peut pas dépasser 10 000.',


                'status.required' =>
                    'Le statut est obligatoire.',

                'status.in' =>
                    'Le statut sélectionné est invalide.',
            ]
        );
    }
}