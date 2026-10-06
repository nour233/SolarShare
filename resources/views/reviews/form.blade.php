@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $action }}">@csrf @if($method !== 'POST') @method($method) @endif
    <div class="mb-4"><label class="form-label fw-bold" for="rating">Note</label><select id="rating" name="rating" class="form-select" required>@for($rating = 5; $rating >= 1; $rating--)<option value="{{ $rating }}" @selected((int) old('rating', $review?->rating ?? 5) === $rating)>{{ $rating }} étoile{{ $rating > 1 ? 's' : '' }}</option>@endfor</select></div>
    <div class="mb-4"><label class="form-label fw-bold" for="title">Titre</label><input id="title" name="title" value="{{ old('title', $review?->title) }}" class="form-control" maxlength="120" required></div>
    <div class="mb-4"><label class="form-label fw-bold" for="comment">Votre avis</label><textarea id="comment" name="comment" rows="6" minlength="10" maxlength="2000" class="form-control" required>{{ old('comment', $review?->comment) }}</textarea><small class="text-muted">Entre 10 et 2 000 caractères.</small></div>
    <button class="btn btn-primary rounded-pill py-3 px-5" type="submit">{{ $method === 'POST' ? 'Publier mon avis' : 'Enregistrer les modifications' }} <i class="fa fa-arrow-right ms-2"></i></button>
</form>
