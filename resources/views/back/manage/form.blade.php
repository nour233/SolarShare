@extends('layouts.back')
@section('title', ($item->exists ? 'Modifier ' : 'Ajouter ').($kind === 'categories' ? 'une catégorie' : 'un équipement'))
@section('content')
<a href="{{ route('admin.'.$kind.'.index') }}" class="d-inline-block mb-4 text-primary">← Retour à la liste</a>
<div class="surface {{ $kind === 'equipments' ? 'equipment-editor' : '' }}"><form method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.'.$kind.'.update',$item) : route('admin.'.$kind.'.store') }}">@csrf @if($item->exists) @method('PUT') @endif
<div class="{{ $kind === 'categories' ? 'row g-4' : '' }}">
@if($kind === 'categories')
<div class="col-md-8"><label class="form-label" for="name">Nom de la catégorie</label><input class="form-control" id="name" name="name" value="{{ old('name',$item->name) }}" required maxlength="255"></div>
<div class="col-md-4"><label class="form-label" for="icon">Icône</label><select class="form-select" id="icon" name="icon">@foreach(['fa-solar-panel'=>'Panneau solaire','fa-battery-full'=>'Batterie','fa-wind'=>'Éolienne','fa-bolt'=>'Énergie'] as $value=>$label)<option value="{{ $value }}" @selected(old('icon',$item->icon)===$value)>{{ $label }}</option>@endforeach</select></div>
@else
<div class="editor-heading"><span class="editor-eyebrow">CATALOGUE STUDIO</span><h2>{{ $item->exists ? 'Affinez votre équipement.' : 'De la photo à votre catalogue.' }}</h2><p>Un peu d’intelligence, toute votre expertise.</p></div>
<div class="editor-workspace">
<aside class="editor-photo-panel">
<span class="editor-step">01 <span>VOTRE PHOTO</span></span>
<h3>Tout commence par une image.</h3>
<p>Claude vous aide à rédiger une fiche claire à partir de votre produit.</p>
<label for="uploads" class="editor-upload"><i class="fas fa-cloud-upload-alt" aria-hidden="true"></i> Choisir les photos</label>
<input type="file" class="editor-file" name="uploads[]" id="uploads" accept="image/jpeg,image/png,image/webp" multiple>
<div class="editor-file-info" id="photo-file-info">JPG, PNG ou WebP · 5 Mo par photo · 8 maximum</div>
<div class="editor-preview" id="editor-preview">
<div id="photo-ai-empty"><span aria-hidden="true">▧</span><strong>Votre produit prend place ici</strong><small>Une photo nette fait toute la différence.</small></div>
<img id="photo-ai-preview" alt="Photo à analyser" hidden>
<span class="editor-scan" aria-hidden="true"></span>
</div>
<button type="button" id="analyze-photo" class="editor-ai-button"><span aria-hidden="true">✦</span> Compléter avec l’IA <span aria-hidden="true">↗</span></button>
<div id="photo-ai-status" class="editor-ai-status" role="status" aria-live="polite"></div>
<p class="editor-privacy"><i class="fas fa-shield-alt" aria-hidden="true"></i> La première photo est analysée par Anthropic. Vous gardez le dernier mot sur chaque information.</p>
@if($item->photos)<div class="gallery mt-3">@foreach($item->photos as $photo)<label><img src="{{ asset($photo) }}" alt="Photo actuelle"><span class="d-block mt-2"><input type="checkbox" name="remove_photos[]" value="{{ $photo }}"> Retirer</span></label>@endforeach</div>@endif
</aside>
<section class="editor-fields"><div class="editor-fields-heading"><span class="editor-step">02 <span>VOTRE FICHE</span></span><p>Vérifiez les suggestions. Ajoutez vos tarifs. Publiez.</p></div><div class="row g-4">
<div class="col-12"><label class="form-label" for="title">Nom de l’équipement</label><input class="form-control" id="title" name="title" value="{{ old('title',$item->title) }}" required maxlength="255"></div>
<div class="col-12"><label class="form-label" for="category_id">Catégorie</label><select class="form-select" name="category_id" id="category_id" required><option value="">Choisir une catégorie</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('category_id',$item->category_id)===(string)$category->id)>{{ $category->name }}</option>@endforeach</select>@if($categories->isEmpty())<a href="{{ route('admin.categories.create') }}">Créez d’abord une catégorie</a>@endif</div>
<div class="col-md-4"><label class="form-label" for="power_capacity">Puissance / capacité</label><input class="form-control" type="number" min="0" step="0.01" name="power_capacity" id="power_capacity" value="{{ old('power_capacity',$item->power_capacity) }}"></div>
<div class="col-md-4"><label class="form-label" for="power_unit">Unité</label><select class="form-select" name="power_unit" id="power_unit">@foreach(['W','Wh'] as $unit)<option @selected(old('power_unit',$item->power_unit)===$unit)>{{ $unit }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="condition">État</label><select class="form-select" name="condition" id="condition">@foreach(['Neuf','Très bon état','Bon état','Usagé'] as $condition)<option @selected(old('condition',$item->condition)===$condition)>{{ $condition }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="price_per_day">Prix par jour</label><input class="form-control" type="number" min="0" step="0.01" name="price_per_day" id="price_per_day" value="{{ old('price_per_day',$item->price_per_day) }}" required></div>
<div class="col-md-6"><label class="form-label" for="deposit">Caution</label><input class="form-control" type="number" min="0" step="0.01" name="deposit" id="deposit" value="{{ old('deposit',$item->deposit ?? 0) }}" required></div>
@endif
<div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4" @if($kind==='equipments') required @endif>{{ old('description',$item->description) }}</textarea></div>
</div><div class="d-flex gap-3 mt-4"><button class="btn btn-primary px-4">{{ $item->exists ? 'Enregistrer les modifications' : 'Créer' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.'.$kind.'.index') }}">Annuler</a></div>@if($kind==='equipments')</section></div></div>@endif</form></div>
@if($kind==='equipments')
<script>
(() => {
    const button = document.getElementById('analyze-photo');
    const input = document.getElementById('uploads');
    const status = document.getElementById('photo-ai-status');
    const preview = document.getElementById('photo-ai-preview');
    const empty = document.getElementById('photo-ai-empty');
    const panel = document.getElementById('editor-preview');
    let previewUrl;
    input.addEventListener('change', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        preview.hidden = true;
        empty.hidden = false;
        document.getElementById('photo-file-info').textContent = input.files.length ? input.files.length + ' photo(s) sélectionnée(s) · première photo analysée' : 'JPG, PNG ou WebP · 5 Mo par photo';
        status.textContent = '';
        if (input.files[0] && ['image/jpeg','image/png','image/webp'].includes(input.files[0].type)) {
            previewUrl = URL.createObjectURL(input.files[0]);
            preview.src = previewUrl;
            preview.hidden = false;
            empty.hidden = true;
        }
    });
    button.addEventListener('click', async () => {
        const photo = input.files[0];
        if (!photo) { status.textContent = 'Sélectionnez une photo dans « Ajouter des photos ».'; input.focus(); return; }
        if (photo.size > 5 * 1024 * 1024) { status.textContent = 'La photo doit faire moins de 5 Mo.'; return; }
        if (['title','description','power_capacity','category_id'].some(id => document.getElementById(id).value.trim()) && !confirm('Remplacer les champs proposés par l’IA ? Le prix, la caution et l’état seront conservés.')) return;
        const data = new FormData(); data.append('photo', photo);
        button.disabled = true; input.disabled = true;
        panel.classList.add('is-analyzing');
        status.dataset.state = 'loading';
        status.textContent = 'Claude analyse votre photo…';
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 70000);
        try {
            const response = await fetch(@json(route('admin.equipments.analyze-photo')), {
                method: 'POST', body: data, signal: controller.signal,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': button.closest('form').querySelector('[name="_token"]').value }
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(response.status === 419 ? 'Session expirée. Rechargez la page.' : response.status === 429 ? 'Trop de demandes. Patientez une minute.' : Object.values(result.errors || {}).flat()[0] || result.message || 'Analyse indisponible. Réessayez.');
            for (const id of ['title','description','category_id','power_capacity']) {
                document.getElementById(id).value = result.suggestion[id] ?? '';
            }
            if (result.suggestion.power_unit) document.getElementById('power_unit').value = result.suggestion.power_unit;
            status.dataset.state = 'success';
            status.textContent = 'Proposition ajoutée ! Vérifiez les informations, puis renseignez l’état, le prix et la caution. Une puissance vide signifie qu’elle n’a pas pu être lue.';
        } catch (error) {
            status.dataset.state = 'error';
            status.textContent = error.name === 'AbortError' ? 'Analyse trop longue. Réessayez.' : error.message;
        } finally { clearTimeout(timer); panel.classList.remove('is-analyzing'); button.disabled = false; input.disabled = false; }
    });
})();
</script>
@endif
@endsection
