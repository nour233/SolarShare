@extends('layouts.back')
@section('title', 'Gestion des réclamations')
@section('content')
<div class="catalog-intro"><div><span class="section-kicker">SERVICE CLIENT</span><h2>Gestion des réclamations</h2><p>{{ $reclamations->total() }} réclamation(s) reçue(s).</p></div></div>
<form method="GET" action="{{ route('admin.reclamations.index') }}" class="filter-bar mb-4" data-reclamation-filters data-results="#admin-reclamation-results">
    <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Rechercher un client ou un objet" aria-label="Rechercher">
    <select class="form-select" name="type"><option value="">Tous les services</option>@foreach(\App\Models\Reclamation::TYPES as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
    <select class="form-select" name="status"><option value="">Tous les statuts</option>@foreach(\App\Models\Reclamation::STATUSES as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
    <select class="form-select" name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Plus récentes</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Plus anciennes</option><option value="subject" @selected(($filters['sort'] ?? '') === 'subject')>Objet A-Z</option><option value="client" @selected(($filters['sort'] ?? '') === 'client')>Client A-Z</option></select>
    <button class="btn btn-outline-primary" type="submit">Filtrer</button>
</form>
<div class="surface" id="admin-reclamation-results">@include('back.reclamations._results')</div>
@endsection
@section('scripts')
<script src="{{ asset('front/js/reclamations.js') }}"></script>
@endsection
