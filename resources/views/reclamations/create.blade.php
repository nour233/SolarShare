@extends('layouts.front')
@section('title', 'Déposer une réclamation')
@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">
    <div class="text-center mb-4"><h6 class="text-primary">Service client</h6><h1>Déposer une réclamation</h1><p>Décrivez votre demande, notre équipe vous répondra dans les meilleurs délais.</p></div>
    <div class="surface p-4 p-lg-5">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('reclamations.store') }}">@csrf
            <div class="mb-4"><label for="type" class="form-label fw-bold">Service concerné</label><select id="type" name="type" class="form-select" required><option value="">Sélectionnez un service</option>@foreach(\App\Models\Reclamation::TYPES as $value => $label)<option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="mb-4"><label for="subject" class="form-label fw-bold">Objet de la réclamation</label><input id="subject" name="subject" value="{{ old('subject') }}" class="form-control" maxlength="255" required placeholder="Ex. : Problème lors de ma location"></div>
            <div class="mb-4"><label for="reclamation_date" class="form-label fw-bold">Date du problème</label><input id="reclamation_date" type="date" name="reclamation_date" value="{{ old('reclamation_date', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" class="form-control" required></div>
            <div class="mb-4"><label for="description" class="form-label fw-bold">Description</label><textarea id="description" name="description" rows="7" minlength="10" maxlength="5000" class="form-control" required placeholder="Expliquez votre réclamation avec le plus de détails possible...">{{ old('description') }}</textarea><small class="text-muted">Entre 10 et 5 000 caractères.</small></div>
            <button class="btn btn-primary rounded-pill py-3 px-5" type="submit">Envoyer la réclamation <i class="fa fa-arrow-right ms-2"></i></button>
        </form>
    </div>
</div></div></div>
@endsection
