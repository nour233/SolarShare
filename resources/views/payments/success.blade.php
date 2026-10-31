@extends('layouts.front')

@section('title', 'Paiement confirmé · SolarShare')

@section('content')
<div class="container py-5" style="max-width: 640px;">
    <div class="card shadow-sm border-0 rounded-4 text-center p-4 p-md-5">
        <div class="mb-4">
            <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-3">
                <i class="fa fa-check-circle fa-4x"></i>
            </div>
            <h2 class="fw-bold text-success mb-2">Paiement validé !</h2>
            <p class="text-muted">Merci, votre transaction a été traitée avec succès.</p>
        </div>

        @if($payment)
            <div class="bg-light rounded-3 p-3 text-start mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Équipement</span>
                    <span class="fw-semibold">{{ $rental->equipment?->title }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Type de versement</span>
                    <span class="fw-semibold">{{ $payment->typeLabel() }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Montant réglé</span>
                    <span class="fw-bold text-primary">{{ number_format($payment->amount, 2, ',', ' ') }} €</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">N° Facture</span>
                    <span class="font-monospace small">{{ $payment->invoice_number ?? 'Généré automatiquement' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">ID Transaction</span>
                    <span class="font-monospace small text-truncate" style="max-width: 200px;">{{ $payment->stripe_payment_intent ?? $payment->stripe_session_id }}</span>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <a href="{{ route('payments.invoice', [$rental->id, $payment->id]) }}" class="btn btn-outline-primary py-2 rounded-pill">
                    <i class="fa fa-file-pdf me-2 text-danger"></i> Télécharger la facture PDF
                </a>
            </div>
        @endif

        <div class="mt-2">
            <a href="{{ route('rentals.show', $rental->id) }}" class="btn btn-primary py-2 px-4 rounded-pill">
                Voir les détails de la réservation &rarr;
            </a>
        </div>
    </div>
</div>
@endsection

