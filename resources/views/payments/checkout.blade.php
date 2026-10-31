@extends('layouts.front')

@section('title', 'Paiement sécurisé · SolarShare')

@section('content')
<div class="container py-5" style="max-width: 760px;">
    <div class="text-center mb-4">
        <h2 class="fw-bold">Paiement sécurisé</h2>
        <p class="text-muted">Réglez votre {{ $type === 'deposit' ? 'caution' : 'location' }} en toute sécurité via carte bancaire</p>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-primary text-white p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-light text-primary mb-1 text-uppercase">{{ $type === 'deposit' ? 'Caution de garantie' : 'Location' }}</span>
                    <h5 class="mb-0 text-white">{{ $rental->equipment?->title }}</h5>
                </div>
                <div class="text-end">
                    <small class="text-white-50 d-block">Montant à régler</small>
                    <span class="fs-3 fw-bold text-white">{{ number_format($amount, 2, ',', ' ') }} €</span>
                </div>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fa fa-shield-alt fa-lg"></i>
                <div class="small">
                    <strong>Environnement sécurisé de test Stripe :</strong> Utilisez la carte de test standard <code>4242 4242 4242 4242</code> avec n'importe quelle date future et n'importe quel CVC.
                </div>
            </div>

            <form method="POST" action="{{ route('payments.sandbox.process', $rental->id) }}" id="payment-form">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="amount" value="{{ $amount }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nom sur la carte</label>
                    <input type="text" name="cardholder_name" class="form-control" value="{{ auth()->user()->name }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Numéro de carte</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-credit-card text-muted"></i></span>
                        <input type="text" name="card_number" id="card_number" class="form-control" placeholder="4242 4242 4242 4242" value="4242 4242 4242 4242" maxlength="19" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Expiration</label>
                        <input type="text" name="card_expiry" class="form-control" placeholder="MM/AA" value="12/28" maxlength="5" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">CVC / CVV</label>
                        <input type="text" name="card_cvc" class="form-control" placeholder="123" value="123" maxlength="4" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm" id="pay-button">
                    <i class="fa fa-lock me-2"></i> Payer {{ number_format($amount, 2, ',', ' ') }} €
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('rentals.show', $rental->id) }}" class="text-muted small text-decoration-none">
                    &larr; Annuler et retourner à ma réservation
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

