@php($filterRoute = request()->routeIs('home') ? 'home' : 'equipments.index')
<div class="container-fluid equipment-section" id="equipments">
<div class="text-center mx-auto equipment-heading"><h6 class="text-primary">Nos équipements</h6><h2>Une énergie à partager, un équipement à découvrir</h2></div>
<div class="d-flex flex-wrap justify-content-center gap-3 equipment-filters">
<a href="{{ route($filterRoute) }}" class="btn {{ !request('category') ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill">Tous les équipements</a>
@foreach($categories as $category)
<a href="{{ route($filterRoute, ['category'=>$category->id]) }}" class="btn {{ (string)request('category') === (string)$category->id ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill"><i class="fa {{ $category->icon }} me-2" aria-hidden="true"></i>{{ $category->name }} ({{ $category->equipments_count }})</a>
@endforeach
</div>
<div class="row g-4">
@forelse($equipments as $equipment)
<div class="col-md-6 col-lg-4"><article class="equipment-card rounded overflow-hidden h-100">
@if(!empty($equipment->photos[0]))<img src="{{ asset($equipment->photos[0]) }}" alt="{{ $equipment->title }}" class="w-100 equipment-photo">
@else<div class="d-flex align-items-center justify-content-center text-primary equipment-placeholder"><i class="fa {{ $equipment->category->icon }} fa-4x" aria-hidden="true"></i></div>@endif
<div class="equipment-content"><small class="text-primary">{{ $equipment->category->name }}</small><h4 class="mt-2">{{ $equipment->title }}</h4><p>{{ Str::limit($equipment->description, 100) }}</p>
<p class="mb-2">État : {{ $equipment->condition }} @if($equipment->power_capacity) · {{ $equipment->power_capacity }} {{ $equipment->power_unit }} @endif</p>
<p class="fw-bold text-dark equipment-price">{{ number_format($equipment->price_per_day, 2, ',', ' ') }} / jour</p><a href="{{ route('equipments.show', $equipment) }}" class="btn btn-primary rounded-pill">Voir l’équipement <i class="fa fa-arrow-right ms-2"></i></a></div></article></div>
@empty
<div class="col-12 text-center py-5"><i class="fa fa-solar-panel fa-3x text-primary mb-3"></i><h4>Aucun équipement pour le moment</h4><p>Les équipements publiés apparaîtront ici.</p></div>
@endforelse
</div>
</div>
