@extends('layouts.back')
@section('title', 'Maintenance')
@section('content')
<div class="catalog-intro"><div><span class="section-kicker">SUIVI TECHNIQUE</span><h2>Des équipements toujours prêts.</h2><p>{{ $maintenances->total() }} interventions enregistrées.</p></div><a class="btn btn-primary add-button" href="{{ route('admin.maintenances.create', $equipmentId ? ['equipment' => $equipmentId] : []) }}"><i class="fa fa-plus me-2" aria-hidden="true"></i>Nouvelle maintenance</a></div>
<div class="surface">
<form method="GET" action="{{ route('admin.maintenances.index') }}" class="filter-bar"><label class="visually-hidden" for="equipment">Filtrer par équipement</label><select class="form-select" name="equipment" id="equipment"><option value="">Tous les équipements</option>@foreach($equipments as $equipment)<option value="{{ $equipment->id }}" @selected($equipmentId === $equipment->id)>{{ $equipment->title }}</option>@endforeach</select><button class="btn btn-outline-primary" type="submit">Filtrer</button>@if($equipmentId)<a href="{{ route('admin.maintenances.index') }}" class="small">Effacer le filtre</a>@endif</form>
@if($maintenances->isEmpty())
<div class="empty-state"><i class="fa fa-tools d-block" aria-hidden="true"></i><h3>Aucune maintenance {{ $equipmentId ? 'pour cet équipement' : 'pour le moment' }}.</h3><p>Nettoyages, réparations et inspections apparaîtront ici.</p></div>
@else
<div class="table-responsive"><table class="table office-table">
<thead><tr><th>Équipement</th><th>Type</th><th>Date</th><th class="text-end">Coût</th><th class="d-none d-lg-table-cell">Notes</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@foreach($maintenances as $maintenance)
<tr>
<td class="fw-bold">{{ $maintenance->equipment->title }}</td>
<td><span class="incident-badge type-{{ $maintenance->type }}">{{ $maintenance->typeLabel() }}</span></td>
<td class="text-nowrap">{{ $maintenance->date->format('d/m/Y') }}</td>
<td class="text-end text-nowrap">{{ number_format($maintenance->cost, 2, ',', ' ') }}</td>
<td class="notes d-none d-lg-table-cell">{{ Str::limit($maintenance->notes, 80) ?: '—' }}</td>
<td><div class="row-actions"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.maintenances.edit', $maintenance) }}"><i class="far fa-edit me-1" aria-hidden="true"></i>Modifier</a><form method="POST" action="{{ route('admin.maintenances.destroy', $maintenance) }}" onsubmit="return confirm('Supprimer cette maintenance définitivement ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Supprimer la maintenance du {{ $maintenance->date->format('d/m/Y') }}"><i class="far fa-trash-alt" aria-hidden="true"></i></button></form></div></td>
</tr>
@endforeach
</tbody></table></div>
@endif
</div>
<div class="mt-4">{{ $maintenances->links('pagination::bootstrap-5') }}</div>
@endsection
