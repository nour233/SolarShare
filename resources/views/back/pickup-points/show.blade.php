@extends('layouts.back')

@section('title', 'Détail du point de retrait')

@section('content')
@php
    $scheduleDays = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];
    $hoursByDay = $pickupPoint->openingHours->groupBy('day_of_week');
@endphp

<a href="{{ route('admin.pickup-points.index') }}" class="d-inline-block mb-4 text-primary text-decoration-none">
    <i class="fa fa-arrow-left me-2"></i>Retour aux points de retrait
</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <strong>Le formulaire contient des erreurs.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="catalog-intro">
    <div>
        <span class="section-kicker">POINT DE RETRAIT</span>
        <h2>{{ $pickupPoint->name }}</h2>
        <p>{{ $pickupPoint->address }}, {{ $pickupPoint->city }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.pickup-points.capacity', ['date' => $date]) }}" class="btn btn-outline-success">
            <i class="fa fa-chart-bar me-2"></i>Capacité
        </a>
        <a href="{{ route('admin.pickup-points.edit', $pickupPoint) }}" class="btn btn-primary add-button">
            <i class="far fa-edit me-2"></i>Modifier
        </a>
    </div>
</div>

<div class="surface p-4 mb-4">
    <div class="row g-4">
        <div class="col-md-4">
            <small class="text-muted">Statut</small>
            <div class="mt-1">
                <span class="badge {{ $pickupPoint->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                    {{ $pickupPoint->status === 'active' ? 'Actif' : 'Inactif' }}
                </span>
                <span class="badge {{ $isOpenNow ? 'bg-success' : 'bg-secondary' }}">
                    {{ $isOpenNow ? 'Ouvert maintenant' : 'Fermé maintenant' }}
                </span>
            </div>
        </div>
        <div class="col-md-4">
            <small class="text-muted">Capacité par créneau</small>
            <div class="fw-semibold mt-1">{{ $pickupPoint->capacity }} rendez-vous / {{ $pickupPoint->slot_duration_minutes }} min</div>
        </div>
        <div class="col-md-4">
            <small class="text-muted">Livraisons associées (historique total)</small>
            <div class="fw-semibold mt-1">{{ $pickupPoint->deliveries_count }}</div>
        </div>
        <div class="col-md-6">
            <small class="text-muted">Adresse</small>
            <div class="fw-semibold mt-1">{{ $pickupPoint->address }}, {{ $pickupPoint->city }}</div>
        </div>
        <div class="col-md-6">
            <small class="text-muted">Ancien champ horaires (compatibilité)</small>
            <div class="fw-semibold mt-1">{{ $pickupPoint->opening_hours }}</div>
        </div>
        <div class="col-md-6">
            <small class="text-muted">Latitude</small>
            <div class="fw-semibold mt-1">{{ $pickupPoint->latitude ?? 'Non renseignée' }}</div>
        </div>
        <div class="col-md-6">
            <small class="text-muted">Longitude</small>
            <div class="fw-semibold mt-1">{{ $pickupPoint->longitude ?? 'Non renseignée' }}</div>
        </div>
        <div class="col-md-6">
            <small class="text-muted">Créé le</small>
            <div class="fw-semibold mt-1">{{ $pickupPoint->created_at->format('d/m/Y H:i') }}</div>
        </div>
        @if($pickupPoint->latitude !== null && $pickupPoint->longitude !== null)
            <div class="col-12">
                <a
                    class="btn btn-sm btn-outline-secondary"
                    target="_blank"
                    rel="noopener noreferrer"
                    href="https://www.openstreetmap.org/?mlat={{ $pickupPoint->latitude }}&mlon={{ $pickupPoint->longitude }}#map=16/{{ $pickupPoint->latitude }}/{{ $pickupPoint->longitude }}"
                >
                    <i class="fa fa-map me-1"></i>Ouvrir la position sur OpenStreetMap
                </a>
            </div>
        @endif
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="surface h-100">
            <span class="section-kicker">PLANNING HEBDOMADAIRE</span>
            <h4 class="mt-1 mb-3">Horaires structurés</h4>
            @foreach($scheduleDays as $day => $dayLabel)
                @php($dayIntervals = $hoursByDay->get($day, collect()))
                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                    <strong>{{ $dayLabel }}</strong>
                    <span class="text-end">
                        @forelse($dayIntervals as $interval)
                            {{ substr($interval->opens_at, 0, 5) }}–{{ substr($interval->closes_at, 0, 5) }}@if(!$loop->last), @endif
                        @empty
                            <span class="text-muted">Fermé / non configuré</span>
                        @endforelse
                    </span>
                </div>
            @endforeach
            @if($pickupPoint->openingHours->isEmpty())
                <div class="alert alert-warning mt-3 mb-0">
                    Aucun horaire structuré n’est encore configuré. L’ancien texte est conservé mais n’est pas utilisé pour les contrôles d’ouverture.
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-6">
        <div class="surface h-100">
            <span class="section-kicker">ACTIVITÉ PLANIFIÉE</span>
            <h4 class="mt-1 mb-3">Utilisation de capacité</h4>
            <form method="GET" action="{{ route('admin.pickup-points.show', $pickupPoint) }}" class="d-flex gap-2 mb-3">
                <input type="date" name="date" class="form-control" value="{{ $date }}">
                <button class="btn btn-outline-success" type="submit">Voir</button>
            </form>

            @if($capacity['closed_for_date'])
                <div class="alert alert-secondary">Ce point est exceptionnellement fermé le {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}.</div>
            @elseif($capacity['total_slot_capacity'] > 0)
                <div class="row g-3 mb-3">
                    <div class="col-6"><small class="text-muted d-block">Rendez-vous planifiés</small><strong>{{ $capacity['reservation_count'] }}</strong></div>
                    <div class="col-6"><small class="text-muted d-block">Places disponibles</small><strong>{{ $capacity['available_capacity'] }}</strong></div>
                    <div class="col-6"><small class="text-muted d-block">Créneaux complets</small><strong>{{ $capacity['full_slots'] }}</strong></div>
                    <div class="col-6"><small class="text-muted d-block">Presque complets</small><strong>{{ $capacity['nearly_full_slots'] }}</strong></div>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Occupation totale des créneaux</span>
                    <strong>{{ $capacity['occupancy_percent'] }}%</strong>
                </div>
                <div class="progress mb-3" role="progressbar" aria-label="Occupation" aria-valuenow="{{ min(100, $capacity['occupancy_percent']) }}" aria-valuemin="0" aria-valuemax="100" style="height:12px">
                    <div class="progress-bar {{ $capacity['status'] === 'full' ? 'bg-danger' : ($capacity['status'] === 'nearly_full' ? 'bg-warning' : 'bg-success') }}" style="width:{{ min(100, $capacity['occupancy_percent']) }}%"></div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($capacity['slots'] as $slot)
                        <span class="badge {{ $slot['status'] === 'full' ? 'bg-danger' : ($slot['status'] === 'nearly_full' ? 'bg-warning text-dark' : 'bg-success') }}">
                            {{ $slot['start'] }}–{{ $slot['end'] }} · {{ $slot['reserved'] }}/{{ $slot['capacity'] }}
                        </span>
                    @endforeach
                </div>
                @if($capacity['unslotted_reservations'])
                    <div class="alert alert-warning mt-3 mb-0">
                        {{ $capacity['unslotted_reservations'] }} réservation(s) ne correspondent à aucun créneau configuré.
                    </div>
                @endif
            @else
                <p class="text-muted mb-0">Aucun créneau calculable pour cette date. Vérifiez le planning hebdomadaire.</p>
            @endif
        </div>
    </div>
</div>

<div class="surface mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <span class="section-kicker">FERMETURES EXCEPTIONNELLES</span>
            <h4 class="mt-1 mb-3">Prochaines fermetures</h4>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.pickup-points.closures.store', $pickupPoint) }}" class="row g-2 align-items-end mb-4">
        @csrf
        <div class="col-md-3">
            <label class="form-label" for="new-closure-date">Date</label>
            <input id="new-closure-date" type="date" name="closure_date" min="{{ today()->format('Y-m-d') }}" class="form-control" value="{{ old('closure_date') }}" required>
        </div>
        <div class="col-md-7">
            <label class="form-label" for="new-closure-reason">Motif</label>
            <input id="new-closure-reason" type="text" name="reason" maxlength="255" class="form-control" value="{{ old('reason') }}" placeholder="Ex. Jour férié, maintenance du site" required>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary"><i class="fa fa-plus me-1"></i>Ajouter</button>
        </div>
    </form>

    @forelse($pickupPoint->closures as $closure)
        <div class="border rounded-3 p-3 mb-3">
            <form method="POST" action="{{ route('admin.pickup-points.closures.update', [$pickupPoint, $closure]) }}" class="row g-2 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-3">
                    <label class="form-label small">Date</label>
                    <input type="date" name="closure_date" min="{{ today()->format('Y-m-d') }}" class="form-control" value="{{ $closure->closure_date->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label small">Motif</label>
                    <input type="text" name="reason" maxlength="255" class="form-control" value="{{ $closure->reason }}" required>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-success" title="Enregistrer"><i class="fa fa-save"></i><span class="visually-hidden">Enregistrer</span></button>
                    <button
                        type="submit"
                        form="delete-closure-{{ $closure->id }}"
                        class="btn btn-sm btn-outline-danger"
                        title="Supprimer"
                        onclick="return confirm('Supprimer cette fermeture ?')"
                    ><i class="fa fa-trash"></i><span class="visually-hidden">Supprimer</span></button>
                </div>
            </form>
            <form id="delete-closure-{{ $closure->id }}" method="POST" action="{{ route('admin.pickup-points.closures.destroy', [$pickupPoint, $closure]) }}">
                @csrf
                @method('DELETE')
            </form>
            <small class="text-muted d-block mt-2">Fermé le {{ $closure->closure_date->format('d/m/Y') }} — {{ $closure->reason }}</small>
        </div>
    @empty
        <p class="text-muted mb-0">Aucune fermeture exceptionnelle à venir.</p>
    @endforelse
</div>

<div class="d-flex justify-content-end">
    <form method="POST" action="{{ route('admin.pickup-points.destroy', $pickupPoint) }}" onsubmit="return confirm('Supprimer ce point de retrait définitivement ?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger"><i class="far fa-trash-alt me-2"></i>Supprimer le point</button>
    </form>
</div>
@endsection
