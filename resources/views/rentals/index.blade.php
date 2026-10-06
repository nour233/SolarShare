@extends('layouts.front')
@section('title', 'Mes réservations')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="mb-0">Mes réservations</h2>
            <p class="text-muted mb-0">Retrouvez toutes vos demandes de location.</p>
        </div>
        <a href="{{ route('equipments.index') }}" class="btn" style="background:#278658;color:white">
            <i class="fa fa-search me-2"></i>Parcourir les équipements
        </a>
    </div>

    {{-- Status filter --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('rentals.index') }}"
           class="btn btn-sm {{ !$status ? 'btn-dark' : 'btn-outline-secondary' }}">
            Toutes
        </a>
        @foreach(['pending' => 'En attente', 'accepted' => 'Acceptées', 'ongoing' => 'En cours', 'returned' => 'Retournées', 'cancelled' => 'Annulées'] as $key => $label)
        <a href="{{ route('rentals.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-dark' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @forelse($rentals as $rental)
    <div class="card border-0 mb-3" style="border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.07)">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div class="d-flex gap-3 align-items-center">
                    @if(!empty($rental->equipment->photos[0]))
                    <img src="{{ asset($rental->equipment->photos[0]) }}" alt="{{ $rental->equipment->title }}"
                         style="width:68px;height:56px;object-fit:cover;border-radius:10px;flex-shrink:0">
                    @else
                    <div style="width:68px;height:56px;border-radius:10px;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fa fa-solar-panel" style="color:#278658;font-size:1.4rem"></i>
                    </div>
                    @endif
                    <div>
                        <span class="badge mb-1" style="background:#e8f5e9;color:#278658;font-size:.75rem">{{ $rental->equipment->category->name }}</span>
                        <h6 class="mb-0">{{ $rental->equipment->title }}</h6>
                        <small class="text-muted">
                            {{ $rental->start_date->format('d/m/Y') }} → {{ $rental->end_date->format('d/m/Y') }}
                            &nbsp;·&nbsp; {{ $rental->durationDays() }} jour{{ $rental->durationDays() > 1 ? 's' : '' }}
                        </small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="rental-badge {{ $rental->statusClass() }}">{{ $rental->statusLabel() }}</span>
                    <div class="mt-1">
                        <strong style="color:#278658">{{ number_format($rental->total_price, 2, ',', ' ') }} €</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('rentals.show', $rental) }}" class="btn btn-sm btn-outline-success">
                    Voir le détail
                </a>
                @if($rental->isPending())
                <form method="POST" action="{{ route('rentals.cancel', $rental) }}"
                      onsubmit="return confirm('Annuler cette réservation ?')" class="m-0">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-danger">Annuler</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5" style="background:white;border-radius:16px;border:1px solid #e5ebe3">
        <i class="fa fa-calendar-alt" style="font-size:2.5rem;color:#c8e6c9"></i>
        <h5 class="mt-3">Aucune réservation</h5>
        <p class="text-muted">Vous n'avez pas encore fait de demande de location.</p>
        <a href="{{ route('equipments.index') }}" class="btn" style="background:#278658;color:white">
            Explorer les équipements
        </a>
    </div>
    @endforelse

    <div class="mt-4">{{ $rentals->links('pagination::bootstrap-5') }}</div>
</div>

<style>
.rental-badge{padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:600;display:inline-block}
.badge-pending{background:#fff8e1;color:#e65100}
.badge-accepted{background:#e8f5e9;color:#2e7d32}
.badge-ongoing{background:#e3f2fd;color:#1565c0}
.badge-returned{background:#f3e5f5;color:#6a1b9a}
.badge-cancelled{background:#fce4ec;color:#b71c1c}
</style>
@endsection
