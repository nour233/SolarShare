@extends('layouts.back')

@section('title', 'Capacité des points de retrait')

@section('content')
<div class="catalog-intro">
    <div>
        <span class="section-kicker">GESTION DES LIVRAISONS</span>
        <h2>Capacité des points de retrait</h2>
        <p>Occupation par créneau de {{ $date->format('d/m/Y') }}. La capacité configurée s’applique à chaque créneau.</p>
    </div>
    <a href="{{ route('admin.pickup-points.index') }}" class="btn btn-outline-secondary">
        <i class="fa fa-map-marker-alt me-2"></i>Gérer les points
    </a>
</div>

<form method="GET" action="{{ route('admin.pickup-points.capacity') }}" class="surface mb-4">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <div class="row align-items-end g-3">
        <div class="col-sm-6 col-md-4">
            <label for="capacity-date" class="form-label">Date à consulter</label>
            <input id="capacity-date" type="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter me-1"></i>Afficher</button>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.pickup-points.capacity') }}" class="btn btn-outline-secondary">Aujourd’hui</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    @foreach([
        ['Total des points', $counts['all'], 'fa-map-marker-alt'],
        ['Actifs', $counts['active'], 'fa-check-circle'],
        ['Inactifs', $counts['inactive'], 'fa-times-circle'],
        ['Réservations planifiées', $counts['reservations'], 'fa-calendar-check'],
        ['Places disponibles', $counts['available_capacity'], 'fa-chair'],
            ['Points presque complets', $counts['nearly_full_points'], 'fa-exclamation-triangle'],
            ['Points complets', $counts['full_points'], 'fa-exclamation-circle'],
        ] as [$label, $value, $icon])
        <div class="col-6 col-lg-2">
            <div class="stat-box h-100">
                <i class="fa {{ $icon }} text-success mb-2"></i>
                <small class="text-muted d-block">{{ $label }}</small>
                <strong>{{ $value }}</strong>
            </div>
        </div>
    @endforeach
</div>

@if($pickupPoints->isEmpty())
    <div class="surface text-center py-5 text-muted">Aucun point de retrait n’est configuré.</div>
@else
    <div class="surface p-0" style="overflow:hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background:#f4f6f3">
                    <tr>
                        <th class="px-4">Point de retrait</th>
                        <th>Capacité / créneau</th>
                        <th>Réservations</th>
                        <th>Places disponibles</th>
                        <th>Occupation journalière</th>
                        <th>Créneaux chargés</th>
                        <th>État</th>
                        <th class="text-end px-4">Détail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pickupPoints as $pickupPoint)
                        @php($metrics = $utilization[$pickupPoint->id])
                        <tr>
                            <td class="px-4">
                                <strong>{{ $pickupPoint->name }}</strong>
                                <small class="text-muted d-block">{{ $pickupPoint->city }}</small>
                                @if($pickupPoint->status !== 'active')
                                    <span class="badge bg-secondary mt-1">Inactif</span>
                                @endif
                            </td>
                            <td>{{ $pickupPoint->capacity }} <small class="text-muted">/ {{ $pickupPoint->slot_duration_minutes }} min</small></td>
                            <td>{{ $metrics['reservation_count'] }}</td>
                            <td>{{ $metrics['available_capacity'] }}</td>
                            <td style="min-width:180px">
                                @if($metrics['total_slot_capacity'] > 0)
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $metrics['occupancy_percent'] }}%</span>
                                        <span>{{ $metrics['slotted_reservations'] }}/{{ $metrics['total_slot_capacity'] }}</span>
                                    </div>
                                    <div class="progress" role="progressbar" aria-label="Occupation {{ $pickupPoint->name }}" aria-valuenow="{{ min(100, $metrics['occupancy_percent']) }}" aria-valuemin="0" aria-valuemax="100" style="height:10px">
                                        <div
                                            class="progress-bar {{ $metrics['status'] === 'full' ? 'bg-danger' : ($metrics['status'] === 'nearly_full' ? 'bg-warning' : 'bg-success') }}"
                                            style="width:{{ min(100, $metrics['occupancy_percent']) }}%"
                                        ></div>
                                    </div>
                                @else
                                    <span class="text-muted">Aucun créneau configuré</span>
                                @endif
                            </td>
                            <td>
                                @if($metrics['full_slots'])
                                    <span class="badge bg-danger">{{ $metrics['full_slots'] }} complet(s)</span>
                                @endif
                                @if($metrics['nearly_full_slots'])
                                    <span class="badge bg-warning text-dark">{{ $metrics['nearly_full_slots'] }} presque complet(s)</span>
                                @endif
                                @if(!$metrics['full_slots'] && !$metrics['nearly_full_slots'])
                                    <span class="text-muted">—</span>
                                @endif
                                @if($metrics['unslotted_reservations'])
                                    <span class="badge bg-secondary d-block mt-1">{{ $metrics['unslotted_reservations'] }} hors créneau</span>
                                @endif
                            </td>
                            <td>
                                @if($metrics['closed_for_date'])
                                    <span class="badge bg-secondary">Fermé exceptionnellement</span>
                                @elseif($metrics['inactive'])
                                    <span class="badge bg-secondary">Point inactif</span>
                                @elseif($metrics['status'] === 'full')
                                    <span class="badge bg-danger">Complet</span>
                                @elseif($metrics['status'] === 'nearly_full')
                                    <span class="badge bg-warning text-dark">Presque complet</span>
                                @elseif(!$metrics['has_schedule'])
                                    <span class="badge bg-secondary">Horaires à configurer</span>
                                @else
                                    <span class="badge bg-success">Disponible</span>
                                @endif
                            </td>
                            <td class="text-end px-4">
                                <a class="btn btn-sm btn-outline-success" href="{{ route('admin.pickup-points.show', ['pickup_point' => $pickupPoint, 'date' => $date->format('Y-m-d')]) }}">
                                    <i class="fa fa-eye"></i><span class="visually-hidden">Voir {{ $pickupPoint->name }}</span>
                                </a>
                            </td>
                        </tr>
                        <tr class="bg-light">
                            <td colspan="8" class="px-4 py-2">
                                @if($metrics['slots'])
                                    <details>
                                        <summary class="small text-success" style="cursor:pointer">Voir les créneaux et réservations</summary>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @foreach($metrics['slots'] as $slot)
                                                <span class="badge {{ $slot['status'] === 'full' ? 'bg-danger' : ($slot['status'] === 'nearly_full' ? 'bg-warning text-dark' : 'bg-success') }}">
                                                    {{ $slot['start'] }}–{{ $slot['end'] }} · {{ $slot['reserved'] }}/{{ $slot['capacity'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </details>
                                @elseif(!$metrics['closed_for_date'])
                                    <small class="text-muted">Aucun horaire structuré n’est défini pour ce jour. Les anciennes valeurs textuelles sont conservées, mais ne permettent pas de calculer des créneaux.</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
