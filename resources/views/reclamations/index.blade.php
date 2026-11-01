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
    <form method="GET" action="{{ route('reclamations.index') }}" class="row g-2 mb-4" data-reclamation-filters data-results="#client-reclamation-results">
        <div class="col-md-5"><input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Rechercher dans vos réclamations" aria-label="Rechercher"></div>
        <div class="col-md-3"><select class="form-select" name="type"><option value="">Tous les services</option>@foreach(\App\Models\Reclamation::TYPES as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">Tous les statuts</option>@foreach(\App\Models\Reclamation::STATUSES as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="sort" aria-label="Trier"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Plus récentes</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Plus anciennes</option><option value="subject" @selected(($filters['sort'] ?? '') === 'subject')>Sujet A-Z</option></select></div>
    </form>
    <div class="surface p-4" id="client-reclamation-results">@include('reclamations._results')</div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('front/js/reclamations.js') }}"></script>
@endsection
