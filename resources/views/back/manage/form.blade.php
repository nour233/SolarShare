@extends('layouts.back')
@section('title', ($item->exists ? 'Modifier ' : 'Ajouter ').($kind === 'categories' ? 'une catégorie' : 'un équipement'))
@section('content')
<a href="{{ route('admin.'.$kind.'.index') }}" class="d-inline-block mb-4 text-primary">← Retour à la liste</a>
<div class="surface"><form method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.'.$kind.'.update',$item) : route('admin.'.$kind.'.store') }}">@csrf @if($item->exists) @method('PUT') @endif
<div class="row g-4">
@if($kind === 'categories')
<div class="col-md-8"><label class="form-label" for="name">Nom de la catégorie</label><input class="form-control" id="name" name="name" value="{{ old('name',$item->name) }}" required maxlength="255"></div>
<div class="col-md-4"><label class="form-label" for="icon">Icône</label><select class="form-select" id="icon" name="icon">@foreach(['fa-solar-panel'=>'Panneau solaire','fa-battery-full'=>'Batterie','fa-wind'=>'Éolienne','fa-bolt'=>'Énergie'] as $value=>$label)<option value="{{ $value }}" @selected(old('icon',$item->icon)===$value)>{{ $label }}</option>@endforeach</select></div>
@else
<div class="col-12"><label class="form-label" for="title">Nom de l’équipement</label><input class="form-control" id="title" name="title" value="{{ old('title',$item->title) }}" required maxlength="255"></div>
<div class="col-12"><label class="form-label" for="category_id">Catégorie</label><select class="form-select" name="category_id" id="category_id" required><option value="">Choisir une catégorie</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('category_id',$item->category_id)===(string)$category->id)>{{ $category->name }}</option>@endforeach</select>@if($categories->isEmpty())<a href="{{ route('admin.categories.create') }}">Créez d’abord une catégorie</a>@endif</div>
<div class="col-md-4"><label class="form-label" for="power_capacity">Puissance / capacité</label><input class="form-control" type="number" min="0" step="0.01" name="power_capacity" id="power_capacity" value="{{ old('power_capacity',$item->power_capacity) }}"></div>
<div class="col-md-4"><label class="form-label" for="power_unit">Unité</label><select class="form-select" name="power_unit" id="power_unit">@foreach(['W','Wh'] as $unit)<option @selected(old('power_unit',$item->power_unit)===$unit)>{{ $unit }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="condition">État</label><select class="form-select" name="condition" id="condition">@foreach(['Neuf','Très bon état','Bon état','Usagé'] as $condition)<option @selected(old('condition',$item->condition)===$condition)>{{ $condition }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="price_per_day">Prix par jour</label><input class="form-control" type="number" min="0" step="0.01" name="price_per_day" id="price_per_day" value="{{ old('price_per_day',$item->price_per_day) }}" required></div>
<div class="col-md-6"><label class="form-label" for="deposit">Caution</label><input class="form-control" type="number" min="0" step="0.01" name="deposit" id="deposit" value="{{ old('deposit',$item->deposit ?? 0) }}" required></div>
@endif
<div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4" @if($kind==='equipments') required @endif>{{ old('description',$item->description) }}</textarea></div>
@if($kind==='equipments')<div class="col-12"><label class="form-label" for="uploads">Ajouter des photos</label><input type="file" class="form-control" name="uploads[]" id="uploads" accept="image/jpeg,image/png,image/webp" multiple><small class="text-muted">JPG, PNG ou WebP · 5 Mo par image · 8 nouvelles photos maximum</small><div class="gallery mt-3">@foreach($item->photos ?? [] as $photo)<label><img src="{{ asset($photo) }}" alt="Photo actuelle"><span class="d-block mt-2"><input type="checkbox" name="remove_photos[]" value="{{ $photo }}"> Retirer</span></label>@endforeach</div></div>@endif
</div><div class="d-flex gap-3 mt-4"><button class="btn btn-primary px-4">{{ $item->exists ? 'Enregistrer les modifications' : 'Créer' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.'.$kind.'.index') }}">Annuler</a></div></form></div>
@endsection
