@extends('layouts.front')
@section('title', $equipment->title)
@section('content')
<div class="container py-5"><a href="{{ route('equipments.index') }}" class="text-primary">← Retour au catalogue</a>
<div class="row g-5 mt-2"><div class="col-lg-6">
@forelse($equipment->photos ?? [] as $photo)<img src="{{ asset($photo) }}" class="img-fluid rounded mb-3" alt="{{ $equipment->title }}">
@empty<div class="bg-light p-5 text-center"><i class="fa {{ $equipment->category->icon }} fa-5x text-primary"></i></div>@endforelse
</div><div class="col-lg-6"><h6 class="text-primary">{{ $equipment->category->name }}</h6><h1>{{ $equipment->title }}</h1><p style="white-space:pre-line">{{ $equipment->description }}</p>
<dl class="row"><dt class="col-5">Puissance / capacité</dt><dd class="col-7">{{ $equipment->power_capacity ?? '—' }} {{ $equipment->power_capacity ? $equipment->power_unit : '' }}</dd><dt class="col-5">État</dt><dd class="col-7">{{ $equipment->condition }}</dd><dt class="col-5">Prix par jour</dt><dd class="col-7">{{ number_format($equipment->price_per_day,2,',',' ') }}</dd><dt class="col-5">Caution</dt><dd class="col-7">{{ number_format($equipment->deposit,2,',',' ') }}</dd><dt class="col-5">Propriétaire</dt><dd class="col-7">{{ $equipment->owner->name }}</dd></dl>
</div></div></div>
@endsection
