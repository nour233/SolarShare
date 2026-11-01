@extends('layouts.back')

@section('title', 'Segmentation Clients RFM')

@section('content')

<style>
    .seg-card {
        border-radius: 16px;
        padding: 22px;
        text-align: center;
        border: 1px solid #e5ebe3;
        background: white;
        transition: transform 0.2s;
    }
    .seg-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .seg-card .count { font-size: 38px; font-weight: bold; }
    .seg-card .label { font-size: 14px; color: #555; margin-top: 5px; }
    .seg-card .pct { font-size: 12px; color: #888; }

    .seg-vip { border-left: 4px solid #ffd700; }
    .seg-regular { border-left: 4px solid #28a745; }
    .seg-at_risk { border-left: 4px solid #fd7e14; }
    .seg-lost { border-left: 4px solid #dc3545; }
    .seg-new { border-left: 4px solid #6c757d; }

    .chart-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .pie-chart {
        width: 280px;
        height: 280px;
        border-radius: 50%;
        position: relative;
    }
    .pie-legend {
        list-style: none;
        padding: 0;
        margin: 0 0 0 30px;
    }
    .pie-legend li {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        font-size: 14px;
    }
    .pie-legend .dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        display: inline-block;
    }

    .rfm-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: white;
    }
    .rfm-badge-vip { background: linear-gradient(135deg, #f0c27f, #d4a64a); color: #333; }
    .rfm-badge-regular { background: #28a745; }
    .rfm-badge-at_risk { background: #fd7e14; }
    .rfm-badge-lost { background: #dc3545; }
    .rfm-badge-new { background: #6c757d; }

    .score-bar {
        display: inline-block;
        height: 8px;
        border-radius: 4px;
        background: #e0e0e0;
        width: 60px;
        position: relative;
        overflow: hidden;
    }
    .score-bar-fill {
        position: absolute;
        top: 0; left: 0;
        height: 100%;
        border-radius: 4px;
        background: #278658;
    }

    .radar-container { text-align: center; }
</style>

{{-- Header with actions --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">
            Analyse RFM (Récence, Fréquence, Montant) des clients
            @if($lastSegmented)
                — Dernière analyse : {{ \Carbon\Carbon::parse($lastSegmented)->format('d/m/Y H:i') }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('admin.segmentation.run') }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-sync-alt me-1"></i> Relancer l'analyse
            </button>
        </form>

        @if(($distribution['at_risk'] ?? 0) + ($distribution['lost'] ?? 0) > 0)
            <form method="POST" action="{{ route('admin.segmentation.notify') }}">
                @csrf
                <button type="submit" class="btn btn-warning" onclick="return confirm('Envoyer les notifications de ré-engagement ?')">
                    <i class="fa fa-bell me-1"></i> Notifier à risque
                </button>
            </form>
        @endif
    </div>
</div>


{{-- Segment cards --}}
<div class="row g-3 mb-4">
    @php
        $segmentInfo = [
            'vip'     => ['icon' => '⭐', 'label' => 'VIP',       'color' => '#ffd700'],
            'regular' => ['icon' => '✅', 'label' => 'Régulier',  'color' => '#28a745'],
            'new'     => ['icon' => '🆕', 'label' => 'Nouveau',   'color' => '#6c757d'],
            'at_risk' => ['icon' => '⚠️', 'label' => 'À risque', 'color' => '#fd7e14'],
            'lost'    => ['icon' => '❌', 'label' => 'Perdu',     'color' => '#dc3545'],
        ];
        $total = max(1, $totalUsers);
    @endphp

    @foreach($segmentInfo as $key => $info)
        <div class="col-md col-6">
            <div class="seg-card seg-{{ $key }}">
                <div class="count" style="color: {{ $info['color'] }}">
                    {{ $distribution[$key] ?? 0 }}
                </div>
                <div class="label">{{ $info['icon'] }} {{ $info['label'] }}</div>
                <div class="pct">{{ round((($distribution[$key] ?? 0) / $total) * 100) }}%</div>
            </div>
        </div>
    @endforeach
</div>


{{-- Charts row --}}
<div class="row g-4 mb-4">

    {{-- Pie Chart --}}
    <div class="col-md-6">
        <div class="surface">
            <h5 class="mb-3"><i class="fa fa-chart-pie me-2"></i>Distribution des segments</h5>
            <div class="chart-container">
                <canvas id="segmentPieChart" width="280" height="280"></canvas>
                <ul class="pie-legend" id="pieLegend"></ul>
            </div>
        </div>
    </div>

    {{-- Average RFM Scores --}}
    <div class="col-md-6">
        <div class="surface">
            <h5 class="mb-3"><i class="fa fa-bullseye me-2"></i>Scores RFM moyens</h5>
            <div class="radar-container">
                <canvas id="rfmRadarChart" width="300" height="300"></canvas>
            </div>
            <div class="mt-3 text-center text-muted small">
                R = Récence (activité récente) · F = Fréquence (nb locations) · M = Montant (dépenses)
            </div>
        </div>
    </div>
</div>


{{-- User table --}}
<div class="surface">
    <h5 class="mb-3"><i class="fa fa-users me-2"></i>Détails par client ({{ $totalUsers }})</h5>

    @if($users->isEmpty())
        <div class="alert alert-info">
            Aucun client trouvé. Lancez d'abord l'analyse RFM.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Segment</th>
                        <th>Récence (R)</th>
                        <th>Fréquence (F)</th>
                        <th>Montant (M)</th>
                        <th>Score total</th>
                        <th>Locations</th>
                        <th>Dépensé</th>
                        <th>Dernière activité</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                <br><small class="text-muted">{{ $user->email }}</small>
                            </td>
                            <td>
                                <span class="rfm-badge rfm-badge-{{ $user->rfm_segment }}">
                                    {{ $segmentInfo[$user->rfm_segment]['icon'] ?? '' }}
                                    {{ $segmentInfo[$user->rfm_segment]['label'] ?? ucfirst($user->rfm_segment) }}
                                </span>
                            </td>
                            <td>
                                @if($user->rfm_recency_score)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="score-bar">
                                            <span class="score-bar-fill" style="width: {{ ($user->rfm_recency_score / 5) * 100 }}%; background: {{ $user->rfm_recency_score >= 4 ? '#28a745' : ($user->rfm_recency_score >= 2 ? '#fd7e14' : '#dc3545') }}"></span>
                                        </span>
                                        <small>{{ $user->rfm_recency_score }}/5</small>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($user->rfm_frequency_score)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="score-bar">
                                            <span class="score-bar-fill" style="width: {{ ($user->rfm_frequency_score / 5) * 100 }}%; background: {{ $user->rfm_frequency_score >= 4 ? '#28a745' : ($user->rfm_frequency_score >= 2 ? '#fd7e14' : '#dc3545') }}"></span>
                                        </span>
                                        <small>{{ $user->rfm_frequency_score }}/5</small>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($user->rfm_monetary_score)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="score-bar">
                                            <span class="score-bar-fill" style="width: {{ ($user->rfm_monetary_score / 5) * 100 }}%; background: {{ $user->rfm_monetary_score >= 4 ? '#28a745' : ($user->rfm_monetary_score >= 2 ? '#fd7e14' : '#dc3545') }}"></span>
                                        </span>
                                        <small>{{ $user->rfm_monetary_score }}/5</small>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($user->rfm_total_score)
                                    <strong>{{ number_format($user->rfm_total_score, 2) }}</strong>/5
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $user->rfm_rental_count ?? 0 }}</td>
                            <td>{{ number_format($user->rfm_total_spent ?? 0, 2) }} DT</td>
                            <td>
                                @if($user->rfm_days_since_last !== null)
                                    <span class="{{ $user->rfm_days_since_last > 90 ? 'text-danger' : ($user->rfm_days_since_last > 30 ? 'text-warning' : 'text-success') }}">
                                        il y a {{ $user->rfm_days_since_last }} jour(s)
                                    </span>
                                @else
                                    <span class="text-muted">Jamais</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>


{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Pie Chart ──────────────────────────────────────────────
    const pieCtx = document.getElementById('segmentPieChart').getContext('2d');
    const distribution = @json($distribution);

    const pieData = {
        labels: ['⭐ VIP', '✅ Régulier', '🆕 Nouveau', '⚠️ À risque', '❌ Perdu'],
        datasets: [{
            data: [
                distribution.vip || 0,
                distribution.regular || 0,
                distribution['new'] || 0,
                distribution.at_risk || 0,
                distribution.lost || 0,
            ],
            backgroundColor: ['#ffd700', '#28a745', '#6c757d', '#fd7e14', '#dc3545'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    };

    new Chart(pieCtx, {
        type: 'doughnut',
        data: pieData,
        options: {
            responsive: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 13 },
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return ` ${context.label}: ${context.parsed} (${pct}%)`;
                        }
                    }
                }
            },
            cutout: '55%',
        }
    });


    // ── Radar Chart ───────────────────────────────────────────
    const radarCtx = document.getElementById('rfmRadarChart').getContext('2d');

    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Récence (R)', 'Fréquence (F)', 'Montant (M)'],
            datasets: [{
                label: 'Score moyen',
                data: [
                    {{ round($avgRecency, 2) }},
                    {{ round($avgFrequency, 2) }},
                    {{ round($avgMonetary, 2) }},
                ],
                backgroundColor: 'rgba(39, 134, 88, 0.2)',
                borderColor: '#278658',
                borderWidth: 2,
                pointBackgroundColor: '#278658',
                pointRadius: 5,
            }]
        },
        options: {
            responsive: false,
            scales: {
                r: {
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 },
                    },
                    grid: { color: '#e0e0e0' },
                    pointLabels: { font: { size: 13, weight: 'bold' } },
                }
            },
            plugins: {
                legend: { display: false },
            }
        }
    });

});
</script>

@endsection

