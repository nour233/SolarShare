@extends('layouts.front')
@section('title', 'Ma réservation #'.str_pad($rental->id, 4, '0', STR_PAD_LEFT))

@section('content')
<div class="container py-5" style="max-width:760px">
    <a href="{{ route('rentals.index') }}" class="d-inline-block mb-4 text-decoration-none" style="color:#278658">
        ← Mes réservations
    </a>

    @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-0">Réservation #{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}</h2>
            <small class="text-muted">Créée le {{ $rental->created_at->translatedFormat('d F Y') }}</small>
        </div>
        <span class="rental-badge {{ $rental->statusClass() }}">{{ $rental->statusLabel() }}</span>
    </div>

    {{-- Equipment card --}}
    <div class="card border-0 mb-4" style="border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
        <div class="card-body">
            <div class="d-flex gap-3 align-items-center">
                @if(!empty($rental->equipment->photos[0]))
                <img src="{{ asset($rental->equipment->photos[0]) }}" alt="{{ $rental->equipment->title }}"
                     style="width:80px;height:64px;object-fit:cover;border-radius:10px;flex-shrink:0">
                @endif
                <div>
                    <span class="badge mb-1" style="background:#e8f5e9;color:#278658">{{ $rental->equipment->category->name }}</span>
                    <h5 class="mb-0">{{ $rental->equipment->title }}</h5>
                    <a href="{{ route('equipments.show', $rental->equipment) }}" class="small" style="color:#278658">Voir l'équipement</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Dates + price --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="text-center p-3" style="background:#f0f9f4;border-radius:12px">
                <small class="text-muted d-block">Début</small>
                <strong>{{ $rental->start_date->format('d/m/Y') }}</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-center p-3" style="background:#f0f9f4;border-radius:12px">
                <small class="text-muted d-block">Fin</small>
                <strong>{{ $rental->end_date->format('d/m/Y') }}</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-center p-3" style="background:#f0f9f4;border-radius:12px">
                <small class="text-muted d-block">Durée</small>
                <strong>{{ $rental->durationDays() }} j</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-center p-3" style="background:#e8f5e9;border-radius:12px">
                <small class="text-muted d-block">Total</small>
                <strong style="color:#278658">{{ number_format($rental->total_price, 2, ',', ' ') }} €</strong>
            </div>
        </div>
    </div>

    @if($rental->notes)
    <div class="mb-4 p-3" style="background:#fafafa;border-radius:10px;border:1px solid #e5ebe3">
        <strong>Notes</strong>
        <p class="mb-0 mt-1 text-muted">{{ $rental->notes }}</p>
    </div>
    @endif

    {{-- Payments --}}
    @if($rental->payments->isNotEmpty())
    <div class="mb-4">
        <h5 class="mb-3">Paiements</h5>
        <div class="table-responsive">
            <table class="table table-sm" style="border-radius:12px;overflow:hidden">
                <thead style="background:#f0f9f4">
                    <tr><th>Type</th><th>Montant</th><th>Méthode</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    @foreach($rental->payments as $p)
                    <tr>
                        <td>{{ $p->typeLabel() }}</td>
                        <td><strong>{{ number_format($p->amount, 2, ',', ' ') }} €</strong></td>
                        <td>{{ $p->methodLabel() }}</td>
                        <td>
                            <span class="badge" style="{{ $p->status === 'paid' ? 'background:#e8f5e9;color:#2e7d32' : ($p->status === 'refunded' ? 'background:#e3f2fd;color:#1565c0' : 'background:#fff8e1;color:#e65100') }}">
                                {{ $p->statusLabel() }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Cancel action --}}
    @if($rental->isPending())
    <form method="POST" action="{{ route('rentals.cancel', $rental) }}"
          onsubmit="return confirm('Annuler cette réservation ?')">
        @csrf @method('PATCH')
        <button class="btn btn-outline-danger">
            <i class="fa fa-times me-2"></i>Annuler la réservation
        </button>
    </form>
    @endif
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
