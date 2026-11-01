<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PickupPointRequest;
use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use App\Services\PickupPointOperationsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        $pickupPoint->setRelation('openingHours', collect());

        return view(
            'back.pickup-points.form',
            compact('pickupPoint')
        );
    }


    /**
     * Store a new pickup point.
     */
    public function store(PickupPointRequest $request)
    {
        $validated = $request->validated();
        $schedule = $validated['weekly_schedule'] ?? null;
        unset($validated['weekly_schedule']);

        DB::transaction(function () use ($validated, $schedule): void {
            $pickupPoint = PickupPoint::create($validated);

            if (is_array($schedule)) {
                $this->syncOpeningHours($pickupPoint, $schedule);
            }
        });

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
    public function show(
        Request $request,
        PickupPoint $pickupPoint,
        PickupPointOperationsService $operations
    )
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = $request->query('date', now()->toDateString());

        $pickupPoint->load([
            'openingHours',
            'closures' => fn ($query) => $query
                ->where(function ($query) use ($date) {
                    $query->whereDate('closure_date', '>=', today())
                        ->orWhereDate('closure_date', $date);
                })
                ->orderBy('closure_date'),
        ])->loadCount('deliveries');

        $capacity = $operations->utilizationForDate(
            collect([$pickupPoint]),
            Carbon::parse($date)
        )[$pickupPoint->id];
        $isOpenNow = $operations->isOpenAt($pickupPoint, now());

        return view(
            'back.pickup-points.show',
            compact('pickupPoint', 'capacity', 'date', 'isOpenNow')
        );
    }


    /**
     * Show edit form.
     */
    public function edit(PickupPoint $pickupPoint)
    {
        $pickupPoint->load('openingHours');

        return view(
            'back.pickup-points.form',
            compact('pickupPoint')
        );
    }


    /**
     * Update pickup point.
     */
    public function update(
        PickupPointRequest $request,
        PickupPoint $pickupPoint
    ) {
        $validated = $request->validated();
        $schedule = $validated['weekly_schedule'] ?? null;
        unset($validated['weekly_schedule']);

        DB::transaction(function () use ($pickupPoint, $validated, $schedule): void {
            $pickupPoint->update($validated);

            if (is_array($schedule)) {
                $this->syncOpeningHours($pickupPoint, $schedule);
            }
        });

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


    private function syncOpeningHours(PickupPoint $pickupPoint, array $schedule): void
    {
        $pickupPoint->openingHours()->delete();

        foreach (range(1, 7) as $day) {
            $dayData = $schedule[$day] ?? [];
            if (filter_var($dayData['closed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            foreach ($dayData['intervals'] ?? [] as $interval) {
                if (! filled($interval['opens_at'] ?? null) || ! filled($interval['closes_at'] ?? null)) {
                    continue;
                }

                $pickupPoint->openingHours()->create([
                    'day_of_week' => $day,
                    'opens_at' => $interval['opens_at'],
                    'closes_at' => $interval['closes_at'],
                ]);
            }
        }
    }
}