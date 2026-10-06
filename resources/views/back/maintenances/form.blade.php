@extends('layouts.back')
@section('title', $maintenance->exists ? 'Modifier une maintenance' : 'Ajouter une maintenance')
@section('content')
<a href="{{ route('admin.maintenances.index') }}" class="d-inline-block mb-4 text-primary">← Retour à la liste</a>
<div class="surface"><form method="POST" action="{{ $maintenance->exists ? route('admin.maintenances.update', $maintenance) : route('admin.maintenances.store') }}">@csrf @if($maintenance->exists) @method('PUT') @endif
<div class="row g-4">
<div class="col-12"><label class="form-label" for="equipment_id">Équipement</label><select class="form-select" id="equipment_id" name="equipment_id" required><option value="">Choisir un équipement</option>@foreach($equipments as $equipment)<option value="{{ $equipment->id }}" @selected((string) old('equipment_id', $maintenance->equipment_id) === (string) $equipment->id)>{{ $equipment->title }}</option>@endforeach</select>@if($equipments->isEmpty())<a href="{{ route('admin.equipments.create') }}">Créez d’abord un équipement</a>@endif</div>
<div class="col-md-4"><label class="form-label" for="type">Type d’intervention</label><select class="form-select" id="type" name="type" required>@foreach(\App\Models\Maintenance::TYPES as $value => $label)<option value="{{ $value }}" @selected(old('type', $maintenance->type) === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="date">Date</label><input class="form-control" type="date" id="date" name="date" max="{{ today()->format('Y-m-d') }}" value="{{ old('date', $maintenance->date?->format('Y-m-d')) }}" required></div>
<div class="col-md-4"><label class="form-label" for="cost">Coût</label><input class="form-control" type="number" min="0" step="0.01" id="cost" name="cost" value="{{ old('cost', $maintenance->cost) }}" required></div>
<div class="col-12"><label class="form-label" for="notes">Notes <span class="fw-normal text-muted">(facultatif)</span></label><textarea class="form-control" id="notes" name="notes" rows="4" maxlength="2000" placeholder="Pièces changées, observations…">{{ old('notes', $maintenance->notes) }}</textarea></div>
</div><div class="d-flex gap-3 mt-4"><button class="btn btn-primary px-4">{{ $maintenance->exists ? 'Enregistrer les modifications' : 'Créer' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.maintenances.index') }}">Annuler</a></div></form></div>
@endsection
