@extends('layouts.front')
@section('title', 'Signaler un problème')
@section('content')
<div class="container py-5"><a href="{{ route('equipments.show', $equipment) }}" class="text-primary">← Retour à l’équipement</a>
<div class="row justify-content-center mt-3"><div class="col-lg-8"><div class="incident-box">
<h6 class="text-primary">Signaler un problème</h6>
<h1 class="mb-3">{{ $equipment->title }}</h1>
<p>Décrivez ce qui ne va pas avec cet équipement. Notre équipe vous répondra directement dans « Mes signalements ».</p>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ route('incidents.store', $equipment) }}">@csrf
<div class="mb-4"><label class="form-label fw-bold text-dark" for="description">Que se passe-t-il ?</label><textarea class="form-control" id="description" name="description" rows="5" minlength="10" maxlength="2000" required placeholder="Ex. : le panneau ne charge plus depuis hier, même en plein soleil.">{{ old('description') }}</textarea><small class="text-muted">Entre 10 et 2 000 caractères.</small></div>
<fieldset class="mb-4"><legend class="form-label fw-bold text-dark fs-6">Gravité</legend><div class="d-flex flex-wrap gap-4">
@foreach(['mineur' => 'Gêne légère', 'moyen' => 'Fonctionne mal', 'grave' => 'Inutilisable ou dangereux'] as $value => $hint)
<div class="form-check"><input class="form-check-input" type="radio" name="severity" id="severity-{{ $value }}" value="{{ $value }}" @checked(old('severity', 'mineur') === $value) required><label class="form-check-label" for="severity-{{ $value }}"><span class="incident-badge severity-{{ $value }}">{{ \App\Models\Incident::SEVERITIES[$value] }}</span><small class="d-block text-muted mt-1">{{ $hint }}</small></label></div>
@endforeach
</div></fieldset>
<div class="mb-4"><label class="form-label fw-bold text-dark" for="photos">Photos <span class="fw-normal text-muted">(facultatif)</span></label><input type="file" class="form-control" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple><small class="text-muted">JPG, PNG ou WebP · 5 Mo par image · 4 photos maximum</small></div>
<button class="btn btn-primary rounded-pill py-3 px-5" type="submit">Envoyer le signalement <i class="fa fa-arrow-right ms-2" aria-hidden="true"></i></button>
</form>
</div></div></div></div>
@endsection
