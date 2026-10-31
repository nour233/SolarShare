@extends('layouts.back')
@section('title', 'Statistiques des locations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <span class="section-kicker">ANALYSE</span>
        <h2 class="mb-0">Statistiques des locations</h2>
    </div>
    <a href="{{ route('admin.rentals.index') }}" class="btn btn-outline-secondary btn-sm">
        ← Retour aux locations
    </a>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="surface h-100 text-center p-3">
            <small class="text-muted d-block">Revenus totaux</small>
            <strong style="font-size:1.6rem;color:#278658">
                {{ number_format($revenueByMonth->sum('revenue'), 2, ',', ' ') }} €
            </strong>
            <small class="text-muted">12 derniers mois</small>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="surface h-100 text-center p-3">
            <small class="text-muted d-block">Durée moyenne</small>
            <strong style="font-size:1.6rem">{{ number_format($avgDuration, 1, ',', ' ') }}</strong>
            <small class="text-muted">jours / location</small>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="surface h-100 text-center p-3">
            <small class="text-muted d-block">Paiements en attente</small>
            <strong style="font-size:1.6rem;color:#e65100">{{ number_format($pendingPayments, 2, ',', ' ') }} €</strong>
            <small class="text-muted">à encaisser</small>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="surface h-100 text-center p-3">
            <small class="text-muted d-block">Cautions retenues</small>
            <strong style="font-size:1.6rem;color:#1565c0">{{ number_format($depositsPending, 2, ',', ' ') }} €</strong>
            <small class="text-muted">non encore remboursées</small>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Revenue chart --}}
    <div class="col-lg-8">
        <div class="surface">
            <h5 class="mb-3">Revenus mensuels (12 derniers mois)</h5>
            @if($revenueByMonth->isEmpty())
                <p class="text-muted">Aucune donnée disponible.</p>
            @else
            <canvas id="revenueChart" height="100"></canvas>
            @endif
        </div>
    </div>

    {{-- Status breakdown --}}
    <div class="col-lg-4">
        <div class="surface">
            <h5 class="mb-3">Répartition par statut</h5>
            <canvas id="statusChart" height="180"></canvas>
        </div>
    </div>

    {{-- Top equipment --}}
    <div class="col-lg-6">
        <div class="surface">
            <h5 class="mb-3">Top 5 équipements les plus loués</h5>
            @forelse($topEquipments as $i => $item)
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span style="width:24px;height:24px;background:#278658;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:bold;flex-shrink:0">
                        {{ $i + 1 }}
                    </span>
                    <div>
                        <div class="fw-semibold" style="font-size:.9rem">
                            {{ $item->equipment?->title ?? 'Équipement supprimé' }}
                        </div>
                        <small class="text-muted">{{ $item->equipment?->category?->name }}</small>
                    </div>
                </div>
                <div class="text-end">
                    <strong style="color:#278658">{{ number_format($item->total_revenue, 2, ',', ' ') }} €</strong>
                    <small class="d-block text-muted">{{ $item->rental_count }} location(s)</small>
                </div>
            </div>
            @empty
            <p class="text-muted">Aucune donnée.</p>
            @endforelse
        </div>
    </div>

    {{-- Deposit summary --}}
    <div class="col-lg-6">
        <div class="surface">
            <h5 class="mb-3">Suivi des cautions</h5>
            <div class="row g-3">
                <div class="col-6">
                    <div class="p-3 text-center" style="background:#e8f5e9;border-radius:12px">
                        <small class="text-muted d-block">Cautions perçues</small>
                        <strong style="color:#2e7d32;font-size:1.2rem">{{ number_format($depositsPaid, 2, ',', ' ') }} €</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 text-center" style="background:#e3f2fd;border-radius:12px">
                        <small class="text-muted d-block">Cautions remboursées</small>
                        <strong style="color:#1565c0;font-size:1.2rem">{{ number_format($depositsRefunded, 2, ',', ' ') }} €</strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 text-center" style="background:#fff8e1;border-radius:12px">
                        <small class="text-muted d-block">En attente de remboursement</small>
                        <strong style="color:#e65100;font-size:1.3rem">{{ number_format($depositsPending, 2, ',', ' ') }} €</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if($revenueByMonth->isNotEmpty())
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: @json($revenueByMonth->pluck('month')),
        datasets: [{
            label: 'Revenus (€)',
            data: @json($revenueByMonth->pluck('revenue')),
            backgroundColor: 'rgba(39,134,88,0.7)',
            borderColor: '#278658',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v + ' €' } } }
    }
});
@endif

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['En attente', 'Acceptée', 'En cours', 'Retournée', 'Annulée'],
        datasets: [{
            data: [
                {{ $statusBreakdown['pending']   ?? 0 }},
                {{ $statusBreakdown['accepted']  ?? 0 }},
                {{ $statusBreakdown['ongoing']   ?? 0 }},
                {{ $statusBreakdown['returned']  ?? 0 }},
                {{ $statusBreakdown['cancelled'] ?? 0 }},
            ],
            backgroundColor: ['#fff3e0','#e8f5e9','#e3f2fd','#f3e5f5','#fce4ec'],
            borderColor:     ['#e65100','#2e7d32','#1565c0','#6a1b9a','#b71c1c'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
    }
});
</script>
@endsection
