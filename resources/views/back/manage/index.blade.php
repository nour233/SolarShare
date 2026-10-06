@extends('layouts.back')
@section('title', $kind === 'categories' ? 'Catégories' : 'Équipements')
@section('content')
<div class="catalog-intro"><div><span class="section-kicker">{{ $kind === 'categories' ? 'STRUCTURE DU CATALOGUE' : 'VOTRE INVENTAIRE SOLAIRE' }}</span><h2>{{ $kind === 'categories' ? 'Chaque énergie a sa place.' : 'Du potentiel, prêt à partager.' }}</h2><p>{{ $items->total() }} {{ $kind === 'categories' ? 'catégories pour organiser votre communauté.' : 'équipements dans votre catalogue.' }}</p></div><a class="btn btn-primary add-button" href="{{ route('admin.'.$kind.'.create') }}"><i class="fa fa-plus me-2"></i>{{ $kind === 'categories' ? 'Nouvelle catégorie' : 'Nouvel équipement' }}</a></div>
<div class="inventory-grid">
@forelse($items as $item)
<article class="inventory-card">
@if($kind === 'equipments')
<div class="inventory-media">@if(!empty($item->photos[0]))<img src="{{ asset($item->photos[0]) }}" alt="{{ $item->title }}">@else<div class="media-placeholder"><i class="fa {{ $item->category->icon }}"></i><span>{{ $item->category->name }}</span></div>@endif<span class="condition-badge"><span></span>{{ $item->condition }}</span><a class="preview-button" href="{{ route('equipments.show',$item) }}" aria-label="Voir {{ $item->title }}"><i class="fa fa-arrow-up"></i></a></div>
<div class="inventory-body"><div class="item-meta"><span>{{ $item->category->name }}</span><span>#{{ str_pad($item->id,3,'0',STR_PAD_LEFT) }}</span></div><h3>{{ $item->title }}</h3><p class="item-description">{{ Str::limit($item->description,95) }}</p><div class="equipment-spec"><span><i class="fa fa-bolt me-1"></i>{{ $item->power_capacity ?? '—' }} {{ $item->power_capacity ? $item->power_unit : '' }}</span><span title="Propriétaire"><i class="far fa-user me-1"></i>{{ $item->owner->name }}</span></div><div class="price-row"><div><strong>{{ number_format($item->price_per_day,2,',',' ') }}</strong><span> / jour</span></div><small>Caution {{ number_format($item->deposit,2,',',' ') }}</small></div></div>
@else
<div class="category-art"><i class="fa {{ $item->icon }}"></i><span>{{ str_pad($item->equipments_count,2,'0',STR_PAD_LEFT) }}</span></div><div class="inventory-body"><div class="item-meta"><span>CATÉGORIE</span><span>#{{ $item->id }}</span></div><h3>{{ $item->name }}</h3><p class="item-description">{{ $item->description ?: 'Ajoutez une description pour présenter cette catégorie.' }}</p><p class="category-count">{{ $item->equipments_count }} équipements associés</p></div>
@endif
<div class="inventory-actions"><a href="{{ route('admin.'.$kind.'.edit',$item) }}"><i class="far fa-edit me-2"></i>Modifier</a><form method="POST" action="{{ route('admin.'.$kind.'.destroy',$item) }}" onsubmit="return confirm('Supprimer cet élément définitivement ?')">@csrf @method('DELETE')<button aria-label="Supprimer {{ $kind === 'categories' ? $item->name : $item->title }}" title="Supprimer"><i class="far fa-trash-alt"></i></button></form></div>
</article>
@empty<div class="surface text-center py-5"><h3>Votre catalogue commence ici.</h3><p>Ajoutez votre premier élément pour le retrouver dans cet espace.</p></div>@endforelse
</div>
<div class="mt-4">{{ $items->links('pagination::bootstrap-5') }}</div>
@endsection
