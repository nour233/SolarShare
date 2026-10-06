<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RentalController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    // ── List all rentals ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $status = $request->query('status');

        $rentals = Rental::with(['equipment.category', 'renter'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all'       => Rental::count(),
            'pending'   => Rental::where('status', 'pending')->count(),
            'accepted'  => Rental::where('status', 'accepted')->count(),
            'ongoing'   => Rental::where('status', 'ongoing')->count(),
            'returned'  => Rental::where('status', 'returned')->count(),
            'cancelled' => Rental::where('status', 'cancelled')->count(),
        ];

        return view('back.rentals.index', compact('rentals', 'status', 'counts'));
    }

    // ── Create a rental manually (admin) ─────────────────────────────────────

    public function create()
    {
        $equipments = Equipment::with('category')->orderBy('title')->get();

        return view('back.rentals.form', [
            'rental'     => new Rental,
            'equipments' => $equipments,
            'renters'    => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    // ── Store manually created rental ────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $this->validate($request);

        $equipment = Equipment::findOrFail($data['equipment_id']);
        $start     = Carbon::parse($data['start_date']);
        $end       = Carbon::parse($data['end_date']);

        if (! $this->availability->isAvailable($equipment, $start, $end)) {
            return back()->withInput()
                ->withErrors(['start_date' => 'Cet équipement est déjà réservé pour ces dates.']);
        }

        $days  = $start->diffInDays($end) + 1;
        $data['total_price'] = round($days * $equipment->price_per_day, 2);

        $rental = Rental::create($data);

        return redirect()->route('admin.rentals.show', $rental)
            ->with('status', 'Location créée.');
    }

    // ── Show a rental detail with payments ───────────────────────────────────

    public function show(Rental $rental)
    {
        return view('back.rentals.show', [
            'rental' => $rental->load(['equipment.category', 'renter', 'payments']),
        ]);
    }

    // ── Edit form ────────────────────────────────────────────────────────────

    public function edit(Rental $rental)
    {
        return view('back.rentals.form', [
            'rental'     => $rental->load(['equipment', 'renter']),
            'equipments' => Equipment::with('category')->orderBy('title')->get(),
            'renters'    => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    // ── Update rental ────────────────────────────────────────────────────────

    public function update(Request $request, Rental $rental)
    {
        $data = $this->validate($request, $rental);

        $equipment = Equipment::findOrFail($data['equipment_id']);
        $start     = Carbon::parse($data['start_date']);
        $end       = Carbon::parse($data['end_date']);

        if (! $this->availability->isAvailable($equipment, $start, $end, $rental->id)) {
            return back()->withInput()
                ->withErrors(['start_date' => 'Cet équipement est déjà réservé pour ces dates.']);
        }

        $days  = $start->diffInDays($end) + 1;
        $data['total_price'] = round($days * $equipment->price_per_day, 2);

        $rental->update($data);

        return redirect()->route('admin.rentals.show', $rental)
            ->with('status', 'Location mise à jour.');
    }

    // ── Delete rental ────────────────────────────────────────────────────────

    public function destroy(Rental $rental)
    {
        $rental->delete();

        return redirect()->route('admin.rentals.index')
            ->with('status', 'Location supprimée.');
    }

    // ── Quick status change (PATCH /admin/rentals/{rental}/status) ───────────

    public function updateStatus(Request $request, Rental $rental)
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'ongoing', 'returned', 'cancelled'])],
        ]);

        $rental->update(['status' => $request->status]);

        return back()->with('status', 'Statut mis à jour.');
    }

    // ── Add a payment to a rental ────────────────────────────────────────────

    public function storePayment(Request $request, Rental $rental)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['card', 'cash', 'transfer'])],
            'type'   => ['required', Rule::in(['rental', 'deposit'])],
            'status' => ['required', Rule::in(['pending', 'paid', 'refunded'])],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $rental->payments()->create($data);

        return back()->with('status', 'Paiement enregistré.');
    }

    // ── Update payment status ────────────────────────────────────────────────

    public function updatePayment(Request $request, Rental $rental, Payment $payment)
    {
        if ($payment->rental_id !== $rental->id) {
            abort(404);
        }

        $request->validate([
            'status' => ['required', Rule::in(['pending', 'paid', 'refunded'])],
        ]);

        $payment->update(['status' => $request->status]);

        return back()->with('status', 'Paiement mis à jour.');
    }

    // ── Delete payment ───────────────────────────────────────────────────────

    public function destroyPayment(Rental $rental, Payment $payment)
    {
        if ($payment->rental_id !== $rental->id) {
            abort(404);
        }

        $payment->delete();

        return back()->with('status', 'Paiement supprimé.');
    }

    // ── Shared validation ────────────────────────────────────────────────────

    private function validate(Request $request, ?Rental $rental = null): array
    {
        return $request->validate([
            'equipment_id' => ['required', 'exists:equipment,id'],
            'renter_id'    => ['required', 'exists:users,id'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'status'       => ['required', Rule::in(['pending', 'accepted', 'ongoing', 'returned', 'cancelled'])],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
