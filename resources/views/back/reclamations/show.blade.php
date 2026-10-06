@extends('layouts.back')
@section('title', 'Réclamation #'.$reclamation->id)
@section('content')
<a href="{{ route('admin.reclamations.index') }}" class="text-primary">← Retour aux réclamations</a>
<div class="surface p-4 mt-3"><div class="d-flex justify-content-between"><div><span class="section-kicker">{{ $reclamation->typeLabel() }}</span><h2>{{ $reclamation->subject }}</h2><p class="text-muted">Par {{ $reclamation->user->name }} ({{ $reclamation->user->email }}) · {{ $reclamation->reclamation_date->format('d/m/Y') }}</p></div></div>
<hr><p style="white-space:pre-line">{{ $reclamation->description }}</p>
<form method="POST" action="{{ route('admin.reclamations.status', $reclamation) }}" class="row g-3 align-items-end mt-4">@csrf @method('PATCH')<div class="col-md-6"><label class="form-label fw-bold" for="status">Statut</label><select id="status" name="status" class="form-select">@foreach(\App\Models\Reclamation::STATUSES as $value => $label)<option value="{{ $value }}" @selected($reclamation->status === $value)>{{ $label }}</option>@endforeach</select></div><div class="col-md-6"><button class="btn btn-primary">Mettre à jour le statut</button></div></form>
</div>
@endsection
