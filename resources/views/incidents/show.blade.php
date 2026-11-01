@extends('layouts.front')
@section('title', 'Signalement #'.$incident->id)
@section('content')
<div class="container py-5"><a href="{{ route('incidents.index') }}" class="text-primary">← Mes signalements</a>
@if(session('status'))<div class="alert alert-success mt-3" role="status">{{ session('status') }}</div>@endif
<div class="row g-4 mt-1">
<div class="col-lg-5"><div class="incident-box h-100">
<h6 class="text-primary">Signalement #{{ $incident->id }}</h6>
<h2 class="h3 mb-3"><a href="{{ route('equipments.show', $incident->equipment) }}" class="text-dark">{{ $incident->equipment->title }}</a></h2>
<div class="d-flex flex-wrap gap-2 mb-3"><span class="incident-badge status-{{ $incident->status }}">{{ $incident->statusLabel() }}</span><span class="incident-badge severity-{{ $incident->severity }}">Gravité : {{ $incident->severityLabel() }}</span></div>
<p class="small text-muted mb-2">Envoyé le {{ $incident->created_at->format('d/m/Y à H:i') }}</p>
<p style="white-space:pre-line">{{ $incident->description }}</p>
@if(!empty($incident->photos))<div class="incident-gallery">@foreach($incident->photos as $photo)<a href="{{ asset($photo) }}" target="_blank" rel="noopener"><img src="{{ asset($photo) }}" alt="Photo du signalement"></a>@endforeach</div>@endif
@if(!empty($incident->repair_estimate) && !$incident->isClosed())
@php($estimate = $incident->repair_estimate)
<div class="repair-estimate mt-3"><i class="far fa-clock" aria-hidden="true"></i><div><span class="d-block small text-muted">Réparation estimée</span><strong>{{ $estimate['days'] }} {{ $estimate['days'] > 1 ? 'jours' : 'jour' }}</strong>@if($estimate['min'] !== $estimate['max'])<span class="text-muted"> (entre {{ $estimate['min'] }} et {{ $estimate['max'] }})</span>@endif<span class="d-block small">Disponible vers le <strong>{{ $incident->estimatedReadyOn()->translatedFormat('d/m/Y') }}</strong></span></div></div>
@endif
@if(!empty($incident->ai_diagnosis['client_advice']) && !$incident->isClosed())
<div class="ai-advice mt-3"><strong><i class="fas fa-shield-alt me-2" aria-hidden="true"></i>Conseil de sécurité</strong><p class="mb-1 mt-1">{{ $incident->ai_diagnosis['client_advice'] }}</p><small class="text-muted">Généré automatiquement par notre assistant IA en attendant la réponse de l’équipe.</small></div>
@endif
</div></div>
<div class="col-lg-7"><div class="incident-box h-100"><h3 class="h4 mb-3">Échanges avec l’équipe</h3>@include('incidents.partials.conversation')</div></div>
</div></div>
@endsection
