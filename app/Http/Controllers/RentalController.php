<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Rental;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RentalController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    // ── Show the booking form for a specific piece of equipment ──────────────

    public function create(Equipment $equipment)
    {
        $bookedRanges = $this->availability->bookedRangesFor($equipment);

        return view('rentals.create', compact('equipment', 'bookedRanges'));
    }

    // ── Store a new rental (with availability check + AI suggestions) ────────

    public function store(Request $request, Equipment $equipment)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);

        // AI: conflict detection
        if (! $this->availability->isAvailable($equipment, $start, $end)) {
            $rec = $this->availability->recommend($equipment, $start, $end);

            return back()
                ->withInput()
                ->with('availability_conflict', true)
                ->with('next_date', $rec['next_date']?->format('Y-m-d'))
                ->with('alternatives', $rec['alternatives']);
        }

        $days  = $start->diffInDays($end) + 1;
        $total = round($days * $equipment->price_per_day, 2);

        $rental = Rental::create([
            'start_date'   => $start,
            'end_date'     => $end,
            'total_price'  => $total,
            'status'       => 'pending',
            'equipment_id' => $equipment->id,
            'renter_id'    => Auth::id(),
            'notes'        => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('status', 'Votre demande de location a été envoyée. En attente de validation.');
    }

    // ── Rental detail (renter can see their own) ─────────────────────────────

  public function show(Rental $rental)
{
    $this->authorizeRental($rental);

    return view('rentals.show', [
        'rental' => $rental->load([
            'equipment.category',
            'payments',
            'deliveries.pickupPoint',
        ]),
    ]);
}

    // ── My rentals list ──────────────────────────────────────────────────────

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

    // ── Renter can cancel a pending rental ───────────────────────────────────

    public function cancel(Rental $rental)
    {
        $this->authorizeRental($rental);

        if (! $rental->isPending()) {
            return back()->withErrors(['rental' => 'Seules les demandes en attente peuvent être annulées.']);
        }

        $rental->update(['status' => 'cancelled']);

        return back()->with('status', 'Location annulée.');
    }

    // ── Guard: only the renter (or admin) can access a rental ────────────────

    private function authorizeRental(Rental $rental): void
    {
        if ($rental->renter_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }
    }
}
