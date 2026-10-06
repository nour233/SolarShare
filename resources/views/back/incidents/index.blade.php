@extends('layouts.back')
@section('title', 'Incidents')
@section('content')
<div class="catalog-intro"><div><span class="section-kicker">À L’ÉCOUTE DE LA COMMUNAUTÉ</span><h2>Chaque signalement compte.</h2><p>{{ $incidents->total() }} incidents {{ $status ? '« '.\App\Models\Incident::STATUSES[$status].' »' : 'au total' }}.</p></div><a class="btn btn-primary add-button" href="{{ route('admin.incidents.create') }}"><i class="fa fa-plus me-2" aria-hidden="true"></i>Nouvel incident</a></div>
<div class="surface">
<form method="GET" action="{{ route('admin.incidents.index') }}" class="filter-bar"><label class="visually-hidden" for="status">Filtrer par statut</label><select class="form-select" name="status" id="status"><option value="">Tous les statuts</option>@foreach(\App\Models\Incident::STATUSES as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-primary" type="submit">Filtrer</button>@if($status)<a href="{{ route('admin.incidents.index') }}" class="small">Effacer le filtre</a>@endif</form>
@if($incidents->isEmpty())
<div class="empty-state"><i class="fa fa-check-circle d-block" aria-hidden="true"></i><h3>{{ $status ? 'Aucun incident avec ce statut.' : 'Aucun incident signalé.' }}</h3><p>Les signalements des utilisateurs apparaîtront ici.</p></div>
@else
<div class="table-responsive"><table class="table office-table">
<thead><tr><th class="d-none d-md-table-cell">#</th><th>Équipement</th><th class="d-none d-md-table-cell">Signalé par</th><th class="d-none d-sm-table-cell">Gravité</th><th>Statut</th><th class="d-none d-md-table-cell">Date</th><th>Messages</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@foreach($incidents as $incident)
<tr>
<td class="text-muted d-none d-md-table-cell">#{{ $incident->id }}</td>
<td><a class="fw-bold text-reset" href="{{ route('admin.incidents.show', $incident) }}">{{ $incident->equipment->title }}</a><small class="d-block text-muted d-md-none">{{ $incident->user->name }} · {{ $incident->created_at->format('d/m/Y') }}</small></td>
<td class="d-none d-md-table-cell">{{ $incident->user->name }}</td>
<td class="d-none d-sm-table-cell"><span class="incident-badge severity-{{ $incident->severity }}">{{ $incident->severityLabel() }}</span></td>
<td><span class="incident-badge status-{{ $incident->status }}">{{ $incident->statusLabel() }}</span></td>
<td class="text-nowrap d-none d-md-table-cell">{{ $incident->created_at->format('d/m/Y') }}</td>
<td>@if($incident->unread_count)<span class="unread-badge ms-0" title="Messages non lus">{{ $incident->unread_count }}</span>@else<span class="text-muted">—</span>@endif</td>
<td><div class="row-actions"><a class="btn btn-sm btn-primary" href="{{ route('admin.incidents.show', $incident) }}">Ouvrir</a><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.incidents.edit', $incident) }}" aria-label="Modifier l’incident #{{ $incident->id }}"><i class="far fa-edit" aria-hidden="true"></i></a><form method="POST" action="{{ route('admin.incidents.destroy', $incident) }}" onsubmit="return confirm('Supprimer cet incident et sa conversation définitivement ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Supprimer l’incident #{{ $incident->id }}"><i class="far fa-trash-alt" aria-hidden="true"></i></button></form></div></td>
</tr>
@endforeach
</tbody></table></div>
@endif
</div>
<div class="mt-4">{{ $incidents->links('pagination::bootstrap-5') }}</div>
@endsection
