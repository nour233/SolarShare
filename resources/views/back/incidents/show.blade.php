@extends('layouts.back')
@section('title', 'Incident #'.$incident->id)
@section('content')
<a href="{{ route('admin.incidents.index') }}" class="d-inline-block mb-4 text-primary">← Retour aux incidents</a>
<div class="detail-grid">
<div class="d-grid gap-4">
<div class="surface">
<div class="d-flex flex-wrap gap-2 mb-3"><span class="incident-badge status-{{ $incident->status }}">{{ $incident->statusLabel() }}</span><span class="incident-badge severity-{{ $incident->severity }}">Gravité : {{ $incident->severityLabel() }}</span></div>
<h2 class="h4">{{ $incident->equipment->title }}</h2>
<p class="small text-muted">Signalé par {{ $incident->user->name }} le {{ $incident->created_at->format('d/m/Y à H:i') }}</p>
<p style="white-space:pre-line">{{ $incident->description }}</p>
@if(!empty($incident->photos))<div class="gallery">@foreach($incident->photos as $photo)<a href="{{ asset($photo) }}" target="_blank" rel="noopener"><img src="{{ asset($photo) }}" alt="Photo du signalement"></a>@endforeach</div>@endif
</div>
@php($ai = $incident->ai_diagnosis)
<div class="surface ai-card">
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><span class="section-kicker"><i class="fas fa-robot me-1" aria-hidden="true"></i> DIAGNOSTIC IA</span>
@if($ai)<span class="small text-muted">{{ $ai['source'] === 'ia' ? 'Analyse IA (texte + photos)' : 'Analyse par règles (IA indisponible)' }} · confiance {{ ['faible' => 'faible', 'moyenne' => 'moyenne', 'elevee' => 'élevée'][$ai['confidence']] ?? $ai['confidence'] }}</span>@endif</div>
@if($ai)
@if($ai['severity'] !== $incident->severity)
<div class="ai-alert {{ $incident->severityUnderestimated() ? 'is-danger' : '' }} d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
<span><i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>Gravité {{ $incident->severityUnderestimated() ? 'sous-estimée' : 'différente' }} : {{ $incident->severityLabel() }} → <strong>{{ \App\Models\Incident::SEVERITIES[$ai['severity']] }}</strong></span>
<form method="POST" action="{{ route('admin.incidents.severity', $incident) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-dark" type="submit">Appliquer</button></form>
</div>
@endif
<p class="mb-3">{{ $ai['summary'] }}</p>
<dl class="detail-list ai-facts">
<dt>Cause probable</dt><dd>{{ $ai['cause'] }}</dd>
<dt>Intervention</dt><dd>{{ \App\Models\Maintenance::TYPES[$ai['action']] ?? $ai['action'] }}</dd>
<dt>Coût estimé</dt><dd>{{ number_format($ai['cost_min'], 0, ',', ' ') }} – {{ number_format($ai['cost_max'], 0, ',', ' ') }}</dd>
<dt>Retirer de la location</dt><dd>@if($ai['block_equipment'])<span class="incident-badge severity-grave">Oui, jusqu’à l’intervention</span>@else<span class="incident-badge severity-mineur">Non</span>@endif</dd>
<dt>Conseil au client</dt><dd class="mb-0">{{ $ai['client_advice'] }}</dd>
</dl>
<div class="d-flex flex-wrap gap-2 mt-3">
<a class="btn btn-primary" href="{{ route('admin.maintenances.create', ['incident' => $incident->id]) }}"><i class="fas fa-tools me-2" aria-hidden="true"></i>Créer la maintenance</a>
<form method="POST" action="{{ route('admin.incidents.diagnose', $incident) }}">@csrf<button class="btn btn-outline-secondary" type="submit"><i class="fas fa-sync-alt me-2" aria-hidden="true"></i>Relancer l’analyse</button></form>
</div>
@else
<p class="text-muted">Aucun diagnostic pour cet incident.</p>
<form method="POST" action="{{ route('admin.incidents.diagnose', $incident) }}">@csrf<button class="btn btn-primary" type="submit"><i class="fas fa-robot me-2" aria-hidden="true"></i>Analyser avec l’IA</button></form>
@endif
</div>
<div class="surface"><span class="section-kicker">CONVERSATION</span><h2 class="h5 mt-2 mb-3">Échanges avec {{ $incident->user->name }}</h2>@include('incidents.partials.conversation')</div>
</div>
<aside class="d-grid gap-4">
<div class="surface"><form method="POST" action="{{ route('admin.incidents.status', $incident) }}">@csrf @method('PATCH')
<label class="form-label" for="status">Statut de l’incident</label><select class="form-select mb-3" id="status" name="status">@foreach(\App\Models\Incident::STATUSES as $value => $label)<option value="{{ $value }}" @selected($incident->status === $value)>{{ $label }}</option>@endforeach</select>
<button class="btn btn-primary w-100" type="submit">Mettre à jour le statut</button>
<p class="small text-muted mt-2 mb-0">« Résolu » et « Rejeté » ferment la conversation.</p></form></div>
@if($estimate = $incident->repair_estimate)
<div class="surface"><span class="section-kicker"><i class="fa fa-brain me-1" aria-hidden="true"></i> DÉLAI ESTIMÉ (ML)</span>
<p class="ml-estimate mb-1">{{ $estimate['days'] }} {{ $estimate['days'] > 1 ? 'jours' : 'jour' }}</p>
<p class="small text-muted mb-2">Entre {{ $estimate['min'] }} et {{ $estimate['max'] }} jours · prêt vers le {{ $incident->estimatedReadyOn()->format('d/m/Y') }}</p>
<p class="small text-muted mb-0">{{ $estimate['source'] === 'ml' ? 'Random Forest hors ligne' : 'Moyenne par type (modèle indisponible)' }} · <a href="{{ route('admin.repair-model') }}">performance du modèle</a></p>
</div>
@endif
<div class="surface"><dl class="detail-list mb-0">
<dt>Équipement</dt><dd><a href="{{ route('equipments.show', $incident->equipment) }}">{{ $incident->equipment->title }}</a></dd>
<dt>Signalé par</dt><dd>{{ $incident->user->name }}<br><small class="text-muted">{{ $incident->user->email }}</small></dd>
<dt>Location</dt><dd class="mb-0">{{ $incident->rental_id ? '#'.$incident->rental_id : '—' }}</dd>
</dl></div>
<div class="surface d-flex gap-2 flex-wrap"><a class="btn btn-outline-primary flex-fill" href="{{ route('admin.incidents.edit', $incident) }}"><i class="far fa-edit me-2" aria-hidden="true"></i>Modifier</a><form class="flex-fill" method="POST" action="{{ route('admin.incidents.destroy', $incident) }}" onsubmit="return confirm('Supprimer cet incident et sa conversation définitivement ?')">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100" type="submit"><i class="far fa-trash-alt me-2" aria-hidden="true"></i>Supprimer</button></form></div>
</aside>
</div>
@endsection
