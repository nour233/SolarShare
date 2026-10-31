<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Payment;
use App\Models\Rental;
use App\Notifications\PaymentRecorded;
use App\Notifications\RentalStatusUpdated;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RentalController extends Controller
{
    public function __construct(
        private AvailabilityService $availability,
        private PricingService $pricing,
    ) {}

    // ── List ──────────────────────────────────────────────────────────────────

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

    // ── Create ────────────────────────────────────────────────────────────────

    public function create()
    {
        return view('back.rentals.form', [
            'rental'     => new Rental,
            'equipments' => Equipment::with('category')->orderBy('title')->get(),
            'renters'    => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data      = $this->validateRental($request);
        $equipment = Equipment::findOrFail($data['equipment_id']);
        $start     = Carbon::parse($data['start_date']);
        $end       = Carbon::parse($data['end_date']);

        if (! $this->availability->isAvailable($equipment, $start, $end)) {
            return back()->withInput()
                ->withErrors(['start_date' => 'Cet équipement est déjà réservé pour ces dates.']);
        }

        $data['total_price'] = $this->pricing->totalPrice($equipment, $start, $end);

        $rental = Rental::create($data);

        return redirect()->route('admin.rentals.show', $rental)->with('status', 'Location créée.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(Rental $rental)
    {
        $rental->load(['equipment.category', 'renter', 'payments']);

        // Build pricing breakdown for display
        $pricingBreakdown = $this->pricing->calculate(
            $rental->equipment,
            $rental->start_date,
            $rental->end_date
        );

        return view('back.rentals.show', compact('rental', 'pricingBreakdown'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(Rental $rental)
    {
        return view('back.rentals.form', [
            'rental'     => $rental->load(['equipment', 'renter']),
            'equipments' => Equipment::with('category')->orderBy('title')->get(),
            'renters'    => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, Rental $rental)
    {
        $data      = $this->validateRental($request, $rental);
        $equipment = Equipment::findOrFail($data['equipment_id']);
        $start     = Carbon::parse($data['start_date']);
        $end       = Carbon::parse($data['end_date']);

        if (! $this->availability->isAvailable($equipment, $start, $end, $rental->id)) {
            return back()->withInput()
                ->withErrors(['start_date' => 'Cet équipement est déjà réservé pour ces dates.']);
        }

        $data['total_price'] = $this->pricing->totalPrice($equipment, $start, $end);
        $rental->update($data);

        return redirect()->route('admin.rentals.show', $rental)->with('status', 'Location mise à jour.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(Rental $rental)
    {
        $rental->delete();

        return redirect()->route('admin.rentals.index')->with('status', 'Location supprimée.');
    }

    // ── Quick status change ───────────────────────────────────────────────────

    public function updateStatus(Request $request, Rental $rental)
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'ongoing', 'returned', 'cancelled'])],
        ]);

        $previousStatus = $rental->status;
        $rental->update(['status' => $request->status]);

        if ($previousStatus !== $request->status) {
            try {
                $rental->renter?->notify(new RentalStatusUpdated($rental, $request->status, $previousStatus));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('RentalStatusUpdated notification failed: ' . $e->getMessage());
            }
        }

        return back()->with('status', 'Statut mis à jour.');
    }

    // ── Payments ──────────────────────────────────────────────────────────────

    public function storePayment(Request $request, Rental $rental)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['card', 'cash', 'transfer'])],
            'type'   => ['required', Rule::in(['rental', 'deposit'])],
            'status' => ['required', Rule::in(['pending', 'paid', 'refunded'])],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $rental->payments()->create($data);

        try {
            $rental->renter?->notify(new PaymentRecorded($payment));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PaymentRecorded notification failed: ' . $e->getMessage());
        }

        return back()->with('status', 'Paiement enregistré.');
    }

    public function updatePayment(Request $request, Rental $rental, Payment $payment)
    {
        abort_if($payment->rental_id !== $rental->id, 404);

        $request->validate([
            'status' => ['required', Rule::in(['pending', 'paid', 'refunded'])],
        ]);

        $payment->update(['status' => $request->status]);

        return back()->with('status', 'Paiement mis à jour.');
    }

    public function destroyPayment(Rental $rental, Payment $payment)
    {
        abort_if($payment->rental_id !== $rental->id, 404);
        $payment->delete();

        return back()->with('status', 'Paiement supprimé.');
    }

    // ── Statistics ────────────────────────────────────────────────────────────

    public function statistics()
    {
        // Revenue per month (last 12 months)
        $revenueByMonth = Rental::selectRaw("DATE_FORMAT(start_date, '%Y-%m') as month, SUM(total_price) as revenue, COUNT(*) as count")
            ->whereNotIn('status', ['cancelled', 'pending'])
            ->where('start_date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top rented equipment (top 5)
        $topEquipments = Rental::selectRaw('equipment_id, COUNT(*) as rental_count, SUM(total_price) as total_revenue')
            ->whereNotIn('status', ['cancelled'])
            ->with('equipment.category')
            ->groupBy('equipment_id')
            ->orderByDesc('rental_count')
            ->limit(5)
            ->get();

        // Average rental duration
        $avgDuration = Rental::whereNotIn('status', ['cancelled'])
            ->selectRaw('AVG(DATEDIFF(end_date, start_date) + 1) as avg_days')
            ->value('avg_days');

        // Pending payments total
        $pendingPayments = Payment::where('status', 'pending')->sum('amount');

        // Deposit stats
        $depositsPaid     = Payment::where('type', 'deposit')->where('status', 'paid')->sum('amount');
        $depositsRefunded = Payment::where('type', 'deposit')->where('status', 'refunded')->sum('amount');
        $depositsPending  = $depositsPaid - $depositsRefunded;

        // Status breakdown
        $statusBreakdown = Rental::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('back.rentals.statistics', compact(
            'revenueByMonth',
            'topEquipments',
            'avgDuration',
            'pendingPayments',
            'depositsPaid',
            'depositsRefunded',
            'depositsPending',
            'statusBreakdown'
        ));
    }

    // ── PDF Contract ──────────────────────────────────────────────────────────

    public function contract(Rental $rental)
    {
        $rental->load(['equipment.category', 'renter', 'payments']);

        $pricingBreakdown = $this->pricing->calculate(
            $rental->equipment,
            $rental->start_date,
            $rental->end_date
        );

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('back.rentals.contract-pdf', compact('rental', 'pricingBreakdown'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('contrat-location-' . str_pad($rental->id, 4, '0', STR_PAD_LEFT) . '.pdf');
    }

    // ── Shared validation ─────────────────────────────────────────────────────

    private function validateRental(Request $request, ?Rental $rental = null): array
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
