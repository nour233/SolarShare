@extends('layouts.back')
@section('title', 'Gestion des réclamations')
@section('content')
<div class="catalog-intro"><div><span class="section-kicker">SERVICE CLIENT</span><h2>Gestion des réclamations</h2><p>{{ $reclamations->total() }} réclamation(s) reçue(s).</p></div></div>
<div class="surface">
<form method="GET" class="filter-bar mb-4"><select class="form-select" name="status"><option value="">Tous les statuts</option>@foreach(\App\Models\Reclamation::STATUSES as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-primary">Filtrer</button></form>
@if($reclamations->isEmpty())<div class="empty-state"><i class="fa fa-check-circle d-block"></i><h3>Aucune réclamation</h3><p>Les réclamations des clients apparaîtront ici.</p></div>
@else<div class="table-responsive"><table class="table office-table"><thead><tr><th>#</th><th>Client</th><th>Objet</th><th>Service</th><th>Date</th><th>Statut</th><th></th></tr></thead><tbody>
@foreach($reclamations as $reclamation)<tr><td>#{{ $reclamation->id }}</td><td><strong>{{ $reclamation->user->name }}</strong><small class="d-block text-muted">{{ $reclamation->user->email }}</small></td><td>{{ $reclamation->subject }}@if($reclamation->type === 'avis')<small class="d-block text-muted">Avis de : {{ $reclamation->review_author_name }}</small>@endif</td><td>{{ $reclamation->typeLabel() }}</td><td>{{ $reclamation->reclamation_date->format('d/m/Y') }}</td><td>{{ $reclamation->statusLabel() }}</td><td><div class="row-actions"><a class="btn btn-sm btn-primary" href="{{ route('admin.reclamations.show', $reclamation) }}">Ouvrir</a><form method="POST" action="{{ route('admin.reclamations.destroy', $reclamation) }}" onsubmit="return confirm('Supprimer cette réclamation définitivement ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="far fa-trash-alt"></i></button></form></div></td></tr>@endforeach
</tbody></table></div>@endif</div><div class="mt-4">{{ $reclamations->links('pagination::bootstrap-5') }}</div>
@endsection
