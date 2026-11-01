<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use App\Services\PickupPointOperationsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PickupPointCapacityController extends Controller
{
    public function __invoke(
        Request $request,
        PickupPointOperationsService $operations
    ): View {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = Carbon::parse($validated['date'] ?? today()->toDateString());

        $pickupPoints = PickupPoint::query()
            ->with([
                'openingHours',
                'closures' => fn ($query) => $query
                    ->whereDate('closure_date', $date->format('Y-m-d')),
            ])
            ->orderBy('name')
            ->get();

        $utilization = $operations->utilizationForDate($pickupPoints, $date);
        $counts = [
            'all' => $pickupPoints->count(),
            'active' => $pickupPoints->where('status', 'active')->count(),
            'inactive' => $pickupPoints->where('status', 'inactive')->count(),
            'reservations' => collect($utilization)->sum('reservation_count'),
            'available_capacity' => collect($utilization)->sum('available_capacity'),
            'full_points' => collect($utilization)->where('status', 'full')->count(),
            'nearly_full_points' => collect($utilization)->where('status', 'nearly_full')->count(),
        ];

        return view('back.pickup-points.capacity', compact(
            'pickupPoints',
            'utilization',
            'counts',
            'date'
        ));
    }
}
