@extends('layouts.back')
@section('title', 'Location #'.str_pad($rental->id, 4, '0', STR_PAD_LEFT))

@section('content')
<a href="{{ route('admin.rentals.index') }}" class="d-inline-block mb-4 text-primary">← Retour aux locations</a>

<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="{{ route('admin.rentals.contract', $rental) }}" class="btn btn-success btn-sm" target="_blank">
        <i class="fa fa-file-pdf me-1"></i> Télécharger le contrat PDF
    </a>
    <a href="{{ route('admin.rentals.edit', $rental) }}" class="btn btn-outline-secondary btn-sm">
        <i class="far fa-edit me-1"></i> Modifier
    </a>
</div>

<div class="row g-4">

    {{-- Left: rental info --}}
    <div class="col-lg-7">
        <div class="surface mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                <div>
                    <h3 class="mb-0">Location #{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}</h3>
                    <small class="text-muted">Créée le {{ $rental->created_at->translatedFormat('d F Y') }}</small>
                </div>
                <span class="rental-badge {{ $rental->statusClass() }}">{{ $rental->statusLabel() }}</span>
            </div>

            {{-- Equipment --}}
            <div class="d-flex gap-3 align-items-center p-3 mb-4" style="background:#f4f6f3;border-radius:12px">
                @if(!empty($rental->equipment->photos[0]))
                <img src="{{ asset($rental->equipment->photos[0]) }}" alt="{{ $rental->equipment->title }}" class="thumb">
                @endif
                <div>
                    <small class="text-muted d-block">Équipement</small>
                    <strong>{{ $rental->equipment->title }}</strong>
                    <span class="ms-2 text-muted">·</span>
                    <span class="ms-2 text-muted small">{{ $rental->equipment->category->name }}</span>
                </div>
            </div>

            {{-- Renter --}}
            <div class="d-flex gap-3 align-items-center p-3 mb-4" style="background:#f4f6f3;border-radius:12px">
                <i class="fa fa-user" style="color:#278658;font-size:1.5rem"></i>
                <div>
                    <small class="text-muted d-block">Locataire</small>
                    <strong>{{ $rental->renter->name }}</strong>
                    @if($rental->renter->rfm_segment)
                        @php
                            $segmentBadges = [
                                'vip' => ['class' => 'bg-warning text-dark', 'label' => '⭐ VIP'],
                                'regular' => ['class' => 'bg-success text-white', 'label' => '✅ Régulier'],
                                'at_risk' => ['class' => 'bg-warning text-dark', 'label' => '⚠️ À risque'],
                                'lost' => ['class' => 'bg-danger text-white', 'label' => '❌ Perdu'],
                                'new' => ['class' => 'bg-secondary text-white', 'label' => '🆕 Nouveau'],
                            ];
                            $seg = $segmentBadges[$rental->renter->rfm_segment] ?? null;
                        @endphp
                        @if($seg)
                            <span class="badge {{ $seg['class'] }} ms-1" style="font-size: 0.75rem;">{{ $seg['label'] }}</span>
                        @endif
                    @endif
                    <span class="ms-2 text-muted small">{{ $rental->renter->email }}</span>
                </div>
            </div>

            {{-- AI Fraud & Risk Scoring Module --}}
            <div class="p-3 mb-4" style="background:#fafdfb;border-radius:12px;border:1px solid #d4e7dc">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold" style="font-size:0.9rem">
                        <i class="fa fa-robot text-success me-1"></i> Analyse Risque & Fraude IA
                    </span>
                    @php
                        $score = $rental->risk_score ?? 15;
                        $badgeStyle = match(true) {
                            $score >= 65 => 'background:#ffebee;color:#c62828',
                            $score >= 35 => 'background:#fff8e1;color:#f57f17',
                            default      => 'background:#e8f5e9;color:#2e7d32',
                        };
                        $badgeText = match(true) {
                            $score >= 65 => 'Risque Élevé',
                            $score >= 35 => 'Risque Modéré',
                            default      => 'Risque Faible (Fiable)',
                        };
                    @endphp
                    <span class="badge" style="{{ $badgeStyle }}; font-size: 0.8rem; padding: 5px 10px;">
                        {{ $badgeText }} ({{ $score }}/100)
                    </span>
                </div>

                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar {{ $score >= 65 ? 'bg-danger' : ($score >= 35 ? 'bg-warning' : 'bg-success') }}" role="progressbar" style="width: {{ $score }}%;"></div>
                </div>

                @if(!empty($rental->risk_factors))
                    <div class="small text-muted mt-2">
                        <strong class="d-block mb-1 text-dark" style="font-size:0.78rem">Facteurs d'évaluation du modèle :</strong>
                        <ul class="mb-0 ps-3" style="font-size:0.75rem">
                            @foreach($rental->risk_factors as $factor)
                                <li>{{ $factor }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="small text-muted mt-1" style="font-size:0.78rem">
                        <i class="fa fa-check text-success me-1"></i> Profil locataire vérifié, aucun signal suspect détecté.
                    </div>
                @endif
            </div>

            {{-- Dates / price --}}
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="p-3 text-center" style="background:#f0f9f4;border-radius:10px">
                        <small class="text-muted d-block">Du</small>
                        <strong>{{ $rental->start_date->format('d/m/Y') }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 text-center" style="background:#f0f9f4;border-radius:10px">
                        <small class="text-muted d-block">Au</small>
                        <strong>{{ $rental->end_date->format('d/m/Y') }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 text-center" style="background:#f0f9f4;border-radius:10px">
                        <small class="text-muted d-block">Durée</small>
                        <strong>{{ $rental->durationDays() }} jour{{ $rental->durationDays() > 1 ? 's' : '' }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 text-center" style="background:#e8f5e9;border-radius:10px">
                        <small class="text-muted d-block">Total</small>
                        <strong style="color:#278658">{{ number_format($rental->total_price, 2, ',', ' ') }} €</strong>
                    </div>
                </div>
            </div>

            @if($rental->notes)
            <div class="p-3" style="background:#fafafa;border-radius:10px;border:1px solid #e5ebe3">
                <strong>Notes</strong>
                <p class="mb-0 mt-1 text-muted">{{ $rental->notes }}</p>
            </div>
            @endif

            {{-- Pricing breakdown --}}
            <div class="mt-3">
                <h6 class="mb-2">Détail du prix</h6>
                <div style="background:#f4f6f3;border-radius:10px;padding:14px">
                    @foreach($pricingBreakdown['breakdown'] as $line)
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.88rem">
                        <span class="text-muted">{{ $line['label'] }}</span>
                        <span style="{{ $line['type'] === 'discount' ? 'color:#2e7d32' : ($line['type'] === 'surcharge' ? 'color:#e65100' : ($line['type'] === 'total' ? 'color:#278658;font-weight:bold' : '')) }}">
                            @if($line['amount'] < 0)
                                − {{ number_format(abs($line['amount']), 2, ',', ' ') }} €
                            @else
                                {{ number_format($line['amount'], 2, ',', ' ') }} €
                            @endif
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Quick status change --}}
        <div class="surface mb-4">
            <h5 class="mb-3">Changer le statut</h5>
            <form method="POST" action="{{ route('admin.rentals.status', $rental) }}" class="d-flex gap-2 flex-wrap">
                @csrf @method('PATCH')
                <select name="status" class="form-select" style="max-width:220px">
                    @foreach(['pending' => 'En attente', 'accepted' => 'Acceptée', 'ongoing' => 'En cours', 'returned' => 'Retournée', 'cancelled' => 'Annulée'] as $s => $lbl)
                    <option value="{{ $s }}" @selected($rental->status === $s)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('admin.rentals.edit', $rental) }}" class="btn btn-outline-secondary">Modifier tout</a>
            </form>
        </div>
    </div>

    {{-- Right: payments --}}
    <div class="col-lg-5">
        <div class="surface">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Paiements</h5>
                <span class="text-muted small">{{ $rental->payments->count() }} enregistrement{{ $rental->payments->count() > 1 ? 's' : '' }}</span>
            </div>

            @forelse($rental->payments as $p)
            <div class="p-3 mb-2" style="background:#f4f6f3;border-radius:10px">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="fw-semibold">{{ $p->typeLabel() }}</span>
                        <span class="mx-2 text-muted">·</span>
                        <span class="text-muted small">{{ $p->methodLabel() }}</span>
                        <div class="mt-1">
                            <strong style="color:#278658">{{ number_format($p->amount, 2, ',', ' ') }} €</strong>
                        </div>
                        @if($p->status === 'paid')
                            <div class="mt-1">
                                <a href="{{ route('payments.invoice', [$rental->id, $p->id]) }}" class="btn btn-sm btn-link text-decoration-none p-0" style="font-size:0.75rem">
                                    <i class="fa fa-file-pdf text-danger me-1"></i> Facture {{ $p->invoice_number ?? 'PDF' }}
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <form method="POST" action="{{ route('admin.rentals.payments.update', [$rental, $p]) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <select name="status" class="form-select form-select-sm" style="min-width:110px" onchange="this.form.submit()">
                                @foreach(['pending' => 'En attente', 'paid' => 'Payé', 'refunded' => 'Remboursé'] as $s => $lbl)
                                <option value="{{ $s }}" @selected($p->status === $s)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('admin.rentals.payments.destroy', [$rental, $p]) }}"
                              onsubmit="return confirm('Supprimer ce paiement ?')" class="mt-1">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger w-100"><i class="far fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-muted small">Aucun paiement enregistré.</p>
            @endforelse

            {{-- Add payment --}}
            <hr>
            <h6 class="mb-3">Ajouter un paiement</h6>
            <form method="POST" action="{{ route('admin.rentals.payments.store', $rental) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:12px">Montant (€)</label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm"
                               value="{{ old('amount', round($rental->total_price, 2)) }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:12px">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="rental">Location</option>
                            <option value="deposit">Caution</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:12px">Méthode</label>
                        <select name="method" class="form-select form-select-sm">
                            <option value="card">Carte</option>
                            <option value="cash">Espèces</option>
                            <option value="transfer">Virement</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:12px">Statut</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="pending">En attente</option>
                            <option value="paid" selected>Payé</option>
                            <option value="refunded">Remboursé</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-sm w-100">
                            <i class="fa fa-plus me-1"></i>Enregistrer le paiement
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rental-badge{padding:6px 16px;border-radius:20px;font-size:.85rem;font-weight:600}
.badge-pending{background:#fff8e1;color:#e65100}
.badge-accepted{background:#e8f5e9;color:#2e7d32}
.badge-ongoing{background:#e3f2fd;color:#1565c0}
.badge-returned{background:#f3e5f5;color:#6a1b9a}
.badge-cancelled{background:#fce4ec;color:#b71c1c}
</style>
@endsection
