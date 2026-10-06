@extends('layouts.back')
@section('title', 'Réclamation #'.$reclamation->id)
@section('content')
<a href="{{ route('admin.reclamations.index') }}" class="text-primary">← Retour aux réclamations</a>
<div class="surface p-4 mt-3"><div class="d-flex justify-content-between"><div><span class="section-kicker">{{ $reclamation->typeLabel() }}</span><h2>{{ $reclamation->subject }}</h2><p class="text-muted">Par {{ $reclamation->user->name }} ({{ $reclamation->user->email }}) · {{ $reclamation->reclamation_date->format('d/m/Y') }}</p></div><span class="reclamation-status {{ $reclamation->statusClass() }}">{{ $reclamation->statusLabel() }}</span></div>
<hr><p style="white-space:pre-line">{{ $reclamation->description }}</p>
<form method="POST" action="{{ route('admin.reclamations.status', $reclamation) }}" class="mt-4">@csrf @method('PATCH')<div class="mb-3"><label class="form-label fw-bold" for="status">Statut</label><select id="status" name="status" class="form-select">@foreach(\App\Models\Reclamation::STATUSES as $value => $label)<option value="{{ $value }}" @selected($reclamation->status === $value)>{{ $label }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label fw-bold" for="admin_response">Réponse au client <span class="fw-normal text-muted">(facultatif)</span></label><textarea id="admin_response" name="admin_response" rows="5" maxlength="5000" class="form-control" placeholder="Écrivez une réponse visible par le client...">{{ old('admin_response', $reclamation->admin_response) }}</textarea></div><button class="btn btn-primary">Enregistrer le statut et la réponse</button></form>
</div>
@endsection
