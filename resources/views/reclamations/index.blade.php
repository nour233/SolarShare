@extends('layouts.front')
@section('title', 'Mes réclamations')
@section('content')
<div class="container py-5">
    <div class="text-center mx-auto mb-5" style="max-width:650px">
        <h6 class="text-primary">Votre espace</h6>
        <h1>Mes réclamations</h1>
        <p>Suivez vos demandes et leur traitement par l’équipe SolarShare.</p>
        <a href="{{ route('reclamations.create') }}" class="btn btn-primary rounded-pill px-4">Déposer une réclamation <i class="fa fa-plus ms-2"></i></a>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="surface p-4">
        @if($reclamations->isEmpty())
            <div class="text-center py-5"><i class="fa fa-comments fa-3x text-primary mb-3"></i><h4>Aucune réclamation</h4><p>Vous n’avez encore envoyé aucune réclamation.</p></div>
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
    </div>
    <div class="mt-4">{{ $reclamations->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
