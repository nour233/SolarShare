<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Rental;
use App\Models\User;
use App\Notifications\NewRentalRequested;
use App\Services\AvailabilityService;
use App\Services\FraudScoringService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentalController extends Controller
{
    public function __construct(
        private AvailabilityService $availability,
        private PricingService $pricing,
        private FraudScoringService $fraudScoring,
    ) {}

    // ── Show the booking form ─────────────────────────────────────────────────

    public function create(Equipment $equipment)
    {
        $bookedRanges = $this->availability->bookedRangesFor($equipment);

        return view('rentals.create', compact('equipment', 'bookedRanges'));
    }

    // ── Store a new rental ────────────────────────────────────────────────────

    public function store(Request $request, Equipment $equipment)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);

        // AI: conflict detection + smart recommendations
        if (! $this->availability->isAvailable($equipment, $start, $end)) {
            $rec = $this->availability->recommend($equipment, $start, $end);

            return back()
                ->withInput()
                ->with('availability_conflict', true)
                ->with('next_date', $rec['next_date']?->format('Y-m-d'))
                ->with('alternatives', $rec['alternatives']);
        }

        // Smart pricing
        $pricing = $this->pricing->calculate($equipment, $start, $end);

        $rental = Rental::create([
            'start_date'   => $start,
            'end_date'     => $end,
            'total_price'  => $pricing['total_price'],
            'status'       => 'pending',
            'equipment_id' => $equipment->id,
            'renter_id'    => Auth::id(),
            'notes'        => $data['notes'] ?? null,
        ]);

        // ML Fraud & Risk Scoring
        try {
            $risk = $this->fraudScoring->evaluate($rental);
            $rental->update([
                'risk_score'   => $risk['score'],
                'risk_level'   => $risk['level'],
                'risk_factors' => $risk['factors'],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Fraud scoring failed: ' . $e->getMessage());
        }

        // Notify admins of new rental request
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new NewRentalRequested($rental));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('NewRentalRequested notification failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('rentals.show', $rental)
            ->with('status', 'Votre demande de location a été envoyée. En attente de validation.');
    }

    // ── Rental detail ─────────────────────────────────────────────────────────

    public function show(Rental $rental)
    {
        $this->authorizeRental($rental);

        return view('rentals.show', [
            'rental' => $rental->load(['equipment.category', 'payments', 'deliveries.pickupPoint']),
        ]);
    }

    // ── My rentals list ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $status  = $request->query('status');
        $rentals = Auth::user()
            ->rentals()
            ->with('equipment.category')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('rentals.index', compact('rentals', 'status'));
    }

    // ── Cancel a pending rental ───────────────────────────────────────────────

    public function cancel(Rental $rental)
    {
        $this->authorizeRental($rental);

        if (! $rental->isPending()) {
            return back()->withErrors(['rental' => 'Seules les demandes en attente peuvent être annulées.']);
        }

        $rental->update(['status' => 'cancelled']);

        return back()->with('status', 'Location annulée.');
    }

    // ── Live pricing API (AJAX) ───────────────────────────────────────────────

    public function priceEstimate(Request $request, Equipment $equipment)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $pricing = $this->pricing->calculate(
            $equipment,
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date'])
        );

        return response()->json($pricing);
    }

    // ── Guard ─────────────────────────────────────────────────────────────────

    private function authorizeRental(Rental $rental): void
    {
        if ($rental->renter_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }
    }
}
