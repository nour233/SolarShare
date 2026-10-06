@extends('layouts.front')
@section('title', 'Mes signalements')
@section('content')
<div class="container py-5">
<div class="text-center mx-auto mb-5" style="max-width:600px"><h6 class="text-primary">Votre espace</h6><h1>Mes signalements</h1><p class="mb-0">Suivez vos signalements et échangez avec notre équipe.</p></div>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
<div class="incident-box">
@if($incidents->isEmpty())
<div class="incident-empty"><i class="fa fa-check-circle fa-3x text-primary mb-3" aria-hidden="true"></i><h4>Aucun signalement pour le moment</h4><p>Un souci avec un équipement ? Signalez-le depuis sa fiche.</p><a href="{{ route('equipments.index') }}" class="btn btn-primary rounded-pill px-4">Voir les équipements</a></div>
@else
<div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Équipement</th><th class="d-none d-md-table-cell">Signalé le</th><th class="d-none d-md-table-cell">Gravité</th><th>Statut</th><th class="d-none d-md-table-cell">Messages</th><th class="d-none d-sm-table-cell"><span class="visually-hidden">Actions</span></th></tr></thead>
<tbody>
@foreach($incidents as $incident)
<tr>
<td><a href="{{ route('incidents.show', $incident) }}" class="fw-bold text-dark">{{ $incident->equipment->title }}</a>@if($incident->unread_count)<span class="unread-badge d-md-none" title="Messages non lus">{{ $incident->unread_count }}</span>@endif<small class="d-block text-muted d-md-none">{{ $incident->created_at->format('d/m/Y') }} · {{ $incident->severityLabel() }}</small></td>
<td class="text-nowrap d-none d-md-table-cell">{{ $incident->created_at->format('d/m/Y') }}</td>
<td class="d-none d-md-table-cell"><span class="incident-badge severity-{{ $incident->severity }}">{{ $incident->severityLabel() }}</span></td>
<td><span class="incident-badge status-{{ $incident->status }}">{{ $incident->statusLabel() }}</span></td>
<td class="text-nowrap d-none d-md-table-cell">@if($incident->unread_count)<span class="unread-badge" title="Messages non lus">{{ $incident->unread_count }}</span> non lu{{ $incident->unread_count > 1 ? 's' : '' }}@else<span class="text-muted">—</span>@endif</td>
<td class="text-end d-none d-sm-table-cell"><a href="{{ route('incidents.show', $incident) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 text-nowrap">Ouvrir <i class="fa fa-arrow-right ms-1" aria-hidden="true"></i></a></td>
</tr>
@endforeach
</tbody></table></div>
@endif
</div>
<div class="mt-4">{{ $incidents->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
