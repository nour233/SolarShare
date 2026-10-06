@extends('layouts.back')
@section('title','Tableau de bord')
@section('content')
<section class="dashboard-hero">
<img src="{{ asset('front/img/carousel-2.jpg') }}" alt="Installation solaire" class="dashboard-hero-photo">
<div class="dashboard-hero-content"><span class="hero-tag"><span></span> SOLARSHARE / ESPACE DE GESTION</span><h2>L'énergie circule.<br>Votre communauté grandit.</h2><p>Un seul espace pour organiser les catégories, publier les équipements et faire vivre le partage.</p><a href="{{ route('admin.equipments.create') }}" class="btn hero-cta"><i class="fa fa-plus me-2"></i>Publier un équipement</a></div>
</section>
<div class="dashboard-stats">
<a class="dashboard-stat" href="{{ route('admin.equipments.index') }}"><span class="stat-icon"><i class="fa fa-solar-panel"></i></span><div><span>Équipements au catalogue</span><strong>{{ $equipmentCount }}</strong></div><i class="fa fa-arrow-right stat-arrow"></i></a>
<a class="dashboard-stat" href="{{ route('admin.categories.index') }}"><span class="stat-icon amber"><i class="fa fa-layer-group"></i></span><div><span>Catégories d'énergie</span><strong>{{ $categoryCount }}</strong></div><i class="fa fa-arrow-right stat-arrow"></i></a>
<a class="dashboard-stat" href="{{ route('admin.rentals.index') }}"><span class="stat-icon"><i class="fa fa-calendar-alt"></i></span><div><span>Locations totales</span><strong>{{ $rentalCount }}</strong></div><i class="fa fa-arrow-right stat-arrow"></i></a>
<a class="dashboard-stat" href="{{ route('admin.rentals.index', ['status'=>'pending']) }}"><span class="stat-icon amber"><i class="fa fa-clock"></i></span><div><span>En attente de validation</span><strong>{{ $pendingCount }}</strong></div><i class="fa fa-arrow-right stat-arrow"></i></a>
<a class="dashboard-stat dark" href="{{ route('equipments.index') }}"><span class="stat-icon"><i class="fa fa-globe"></i></span><div><span>Votre vitrine publique</span><strong class="stat-text">Explorer le site</strong></div><i class="fa fa-arrow-right stat-arrow"></i></a>
</div>
<section class="dashboard-inventory">
<header class="dashboard-section-title"><div><span class="section-kicker">LE CATALOGUE EN UN COUP D'ŒIL</span><h2>Derniers équipements</h2></div><a href="{{ route('admin.equipments.index') }}">Tout gérer <i class="fa fa-arrow-right ms-2"></i></a></header>
<div class="inventory-grid">
@forelse($equipments as $equipment)
<article class="inventory-card"><div class="inventory-media">
@if(!empty($equipment->photos[0]))<img src="{{ asset($equipment->photos[0]) }}" alt="{{ $equipment->title }}">@else<div class="media-placeholder"><i class="fa {{ $equipment->category->icon }}"></i><span>{{ $equipment->category->name }}</span></div>@endif
<span class="condition-badge"><span></span>{{ $equipment->condition }}</span><a class="preview-button" href="{{ route('equipments.show',$equipment) }}" aria-label="Voir {{ $equipment->title }}"><i class="fa fa-arrow-up"></i></a>
</div><div class="inventory-body"><div class="item-meta"><span>{{ $equipment->category->name }}</span><span>#{{ str_pad($equipment->id,3,'0',STR_PAD_LEFT) }}</span></div><h3>{{ $equipment->title }}</h3><div class="equipment-spec"><span><i class="fa fa-bolt me-1"></i>{{ $equipment->power_capacity ?? '—' }} {{ $equipment->power_capacity ? $equipment->power_unit : '' }}</span><span><i class="far fa-user me-1"></i>{{ $equipment->owner->name }}</span></div><div class="price-row"><div><strong>{{ number_format($equipment->price_per_day,2,',',' ') }}</strong><span> / jour</span></div><small>Caution {{ number_format($equipment->deposit,2,',',' ') }}</small></div></div><div class="inventory-actions"><a href="{{ route('admin.equipments.edit',$equipment) }}"><i class="far fa-edit me-2"></i>Modifier l'équipement</a><form method="POST" action="{{ route('admin.equipments.destroy', $equipment) }}" onsubmit="return confirm('Supprimer cet équipement définitivement ?')">@csrf @method('DELETE')<button class="dashboard-delete" type="submit" aria-label="Supprimer {{ $equipment->title }}"><i class="far fa-trash-alt me-1" aria-hidden="true"></i> Supprimer</button></form></div></article>
@empty<div class="surface"><h3>Votre première publication vous attend.</h3><p>Ajoutez un équipement pour donner vie au catalogue.</p></div>@endforelse
</div><div class="mt-4">{{ $equipments->links('pagination::bootstrap-5') }}</div>
</section>
@endsection
