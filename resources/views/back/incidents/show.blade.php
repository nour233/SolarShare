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
<div class="surface"><span class="section-kicker">CONVERSATION</span><h2 class="h5 mt-2 mb-3">Échanges avec {{ $incident->user->name }}</h2>@include('incidents.partials.conversation')</div>
</div>
<aside class="d-grid gap-4">
<div class="surface"><form method="POST" action="{{ route('admin.incidents.status', $incident) }}">@csrf @method('PATCH')
<label class="form-label" for="status">Statut de l’incident</label><select class="form-select mb-3" id="status" name="status">@foreach(\App\Models\Incident::STATUSES as $value => $label)<option value="{{ $value }}" @selected($incident->status === $value)>{{ $label }}</option>@endforeach</select>
<button class="btn btn-primary w-100" type="submit">Mettre à jour le statut</button>
<p class="small text-muted mt-2 mb-0">« Résolu » et « Rejeté » ferment la conversation.</p></form></div>
<div class="surface"><dl class="detail-list mb-0">
<dt>Équipement</dt><dd><a href="{{ route('equipments.show', $incident->equipment) }}">{{ $incident->equipment->title }}</a></dd>
<dt>Signalé par</dt><dd>{{ $incident->user->name }}<br><small class="text-muted">{{ $incident->user->email }}</small></dd>
<dt>Location</dt><dd class="mb-0">{{ $incident->rental_id ? '#'.$incident->rental_id : '—' }}</dd>
</dl></div>
<div class="surface d-flex gap-2 flex-wrap"><a class="btn btn-outline-primary flex-fill" href="{{ route('admin.incidents.edit', $incident) }}"><i class="far fa-edit me-2" aria-hidden="true"></i>Modifier</a><form class="flex-fill" method="POST" action="{{ route('admin.incidents.destroy', $incident) }}" onsubmit="return confirm('Supprimer cet incident et sa conversation définitivement ?')">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100" type="submit"><i class="far fa-trash-alt me-2" aria-hidden="true"></i>Supprimer</button></form></div>
</aside>
</div>
@endsection
