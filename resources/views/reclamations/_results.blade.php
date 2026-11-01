@if($reclamations->isEmpty())
    <div class="text-center py-5"><i class="fa fa-comments fa-3x text-primary mb-3"></i><h4>Aucune réclamation</h4><p>Aucune réclamation ne correspond à vos critères.</p></div>
@else
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Sujet</th><th>Service concerné</th><th>Date</th><th>Statut</th><th></th></tr></thead>
        <tbody>@foreach($reclamations as $reclamation)<tr>
            <td class="fw-bold">{{ $reclamation->subject }}</td>
            <td>{{ $reclamation->typeLabel() }}</td>
            <td>{{ $reclamation->reclamation_date->format('d/m/Y') }}</td>
            <td><span class="reclamation-status {{ $reclamation->statusClass() }}">{{ $reclamation->statusLabel() }}</span></td>
            <td class="text-end"><a href="{{ route('reclamations.show', $reclamation) }}" class="btn btn-outline-primary btn-sm rounded-pill">Consulter</a></td>
        </tr>@endforeach</tbody>
    </table></div>
@endif
<div class="mt-4">{{ $reclamations->links('pagination::bootstrap-5') }}</div>
