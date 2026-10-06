@extends('layouts.front')
@section('title', 'Ma réclamation')
@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">
    <a href="{{ route('reclamations.index') }}" class="text-primary">← Retour à mes réclamations</a>
    <div class="surface p-4 p-lg-5 mt-3"><div class="d-flex justify-content-between align-items-start gap-3 mb-4"><div><h6 class="text-primary">{{ $reclamation->typeLabel() }}</h6><h1>{{ $reclamation->subject }}</h1></div><span class="badge text-bg-light">{{ $reclamation->statusLabel() }}</span></div>
        <p class="text-muted">Déposée le {{ $reclamation->reclamation_date->format('d/m/Y') }}</p><hr><p class="mb-0" style="white-space:pre-line">{{ $reclamation->description }}</p>
    </div>
</div></div></div>
@endsection
