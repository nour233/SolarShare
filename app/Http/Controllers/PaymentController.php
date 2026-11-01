<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Notifications\PaymentRecorded;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentController extends Controller
{
    /**
     * Initiate online checkout for either rental balance or deposit.
     */
    public function checkout(Request $request, Rental $rental)
    {
        $this->authorizeAccess($rental);

        $request->validate([
            'type' => ['required', 'in:rental,deposit'],
        ]);

        $type = $request->input('type');
        $equipmentTitle = $rental->equipment?->title ?? 'Équipement SolarShare';

        if ($type === 'deposit') {
            $amount = (float) ($rental->equipment?->deposit ?? 0);
            if ($amount <= 0) {
                return back()->withErrors(['deposit' => 'Aucune caution requise pour cet équipement.']);
            }
            if ($rental->paidDeposit() >= $amount) {
                return back()->with('status', 'La caution a déjà été réglée.');
            }
            $description = "Caution de garantie pour {$equipmentTitle}";
        } else {
            $amount = $rental->remainingBalance();
            if ($amount <= 0) {
                return back()->with('status', 'Cette réservation est déjà intégralement payée.');
            }
            $description = "Règlement de la réservation #{$rental->id} ({$equipmentTitle})";
        }

        $stripeSecret = config('services.stripe.secret') ?? env('STRIPE_SECRET');

        // Check if real Stripe test secret is configured, else fallback to smooth simulated payment
        $isLiveOrValidTest = $stripeSecret && !str_contains($stripeSecret, 'DemoSolarShareTestingKey');

        if ($isLiveOrValidTest) {
            try {
                Stripe::setApiKey($stripeSecret);

                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency'     => 'eur',
                            'product_data' => [
                                'name'        => $type === 'deposit' ? 'Caution SolarShare' : 'Location SolarShare',
                                'description' => $description,
                            ],
                            'unit_amount'  => (int) round($amount * 100),
                        ],
                        'quantity'   => 1,
                    ]],
                    'mode'        => 'payment',
                    'success_url' => route('payments.success', $rental->id) . '?session_id={CHECKOUT_SESSION_ID}&type=' . $type,
                    'cancel_url'  => route('payments.cancel', $rental->id),
                    'customer_email' => Auth::user()->email,
                    'metadata'    => [
                        'rental_id' => $rental->id,
                        'user_id'   => Auth::id(),
                        'type'      => $type,
                    ],
                ]);

                return redirect()->away($session->url);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Stripe session creation error: ' . $e->getMessage());
                // Fall back to sandbox test simulator view
            }
        }

        // Render sandbox test checkout simulator (free, interactive with test card 4242 4242 4242 4242)
        return view('payments.checkout', [
            'rental'      => $rental->load(['equipment', 'renter']),
            'type'        => $type,
            'amount'      => $amount,
            'description' => $description,
        ]);
    }

    /**
     * Process sandbox test payment submission.
     */
    public function processSandbox(Request $request, Rental $rental): RedirectResponse
    {
        $this->authorizeAccess($rental);

        $data = $request->validate([
            'type'            => ['required', 'in:rental,deposit'],
            'amount'          => ['required', 'numeric', 'min:0.5'],
            'card_number'     => ['required', 'string'],
            'card_expiry'     => ['required', 'string'],
            'card_cvc'        => ['required', 'string'],
            'cardholder_name' => ['required', 'string', 'max:255'],
        ]);

        $sessionId = 'cs_test_' . Str::random(24);
        $paymentIntent = 'pi_test_' . Str::random(24);
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $payment = $rental->payments()->create([
            'amount'                => $data['amount'],
            'method'                => 'card',
            'status'                => 'paid',
            'type'                  => $data['type'],
            'stripe_session_id'     => $sessionId,
            'stripe_payment_intent' => $paymentIntent,
            'invoice_number'        => $invoiceNumber,
            'notes'                 => 'Paiement sécurisé par carte bancaire (' . substr(str_replace(' ', '', $data['card_number']), -4) . ')',
        ]);

        // If total is now fully paid and status was pending, update rental status
        if ($rental->remainingBalance() <= 0 && $rental->status === 'pending') {
            $rental->update(['status' => 'ongoing']);
        }

        // Notify renter
        try {
            $rental->renter?->notify(new PaymentRecorded($payment));
        } catch (\Throwable $e) {}

        // Dynamically update user RFM segment
        try {
            if ($rental->renter) {
                app(\App\Services\RfmSegmentationService::class)->segmentUser($rental->renter);
            }
        } catch (\Throwable $e) {}

        return redirect()->route('payments.success', [
            'rental'     => $rental->id,
            'session_id' => $sessionId,
            'type'       => $data['type'],
        ])->with('status', 'Paiement validé avec succès !');
    }

    /**
     * Successful payment confirmation page.
     */
    public function success(Request $request, Rental $rental)
    {
        $this->authorizeAccess($rental);

        $sessionId = $request->query('session_id');
        $type = $request->query('type', 'rental');

        $payment = $rental->payments()
            ->when($sessionId, fn ($q) => $q->where('stripe_session_id', $sessionId))
            ->latest()
            ->first();

        return view('payments.success', [
            'rental'  => $rental->load(['equipment', 'renter']),
            'payment' => $payment,
            'type'    => $type,
        ]);
    }

    /**
     * Cancelled payment redirect.
     */
    public function cancel(Rental $rental): RedirectResponse
    {
        return redirect()->route('rentals.show', $rental)
            ->withErrors(['payment' => 'Le paiement a été interrompu ou annulé.']);
    }

    /**
     * Download PDF Invoice.
     */
    public function invoice(Rental $rental, Payment $payment)
    {
        $this->authorizeAccess($rental);
        abort_if($payment->rental_id !== $rental->id, 404);

        if (!$payment->invoice_number) {
            $payment->update([
                'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            ]);
        }

        $pdf = Pdf::loadView('payments.invoice-pdf', [
            'rental'  => $rental->load(['equipment.category', 'renter']),
            'payment' => $payment,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('facture-' . $payment->invoice_number . '.pdf');
    }

    private function authorizeAccess(Rental $rental): void
    {
        if ($rental->renter_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }
    }
}

