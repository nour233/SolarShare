@extends('layouts.front')
@section('title', 'Avis clients')
@section('content')
<div class="container py-5 reviews-page">
    <div class="text-center mx-auto mb-5" style="max-width:680px">
        <span class="reviews-eyebrow">LA COMMUNAUTÉ SOLARSHARE</span>
        <h1>Les avis de nos clients</h1>
        <p>Découvrez ce que notre communauté pense de nos services.</p>
        @auth
            <a href="{{ route('reviews.create') }}" class="btn btn-primary rounded-pill px-4">Laisser un avis <i class="fa fa-star ms-2"></i></a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4">Connectez-vous pour laisser un avis</a>
        @endauth
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form method="GET" action="{{ route('reviews.index') }}" class="reviews-toolbar" id="reviews-filter-form">
        <div class="reviews-filter">
            <span class="reviews-rating-label" id="rating-filter-label">Note attribuée</span>
            <details class="reviews-rating-menu">
                <summary aria-labelledby="rating-filter-label rating-filter-value"><span><i class="fas fa-star" aria-hidden="true"></i> <span id="rating-filter-value">{{ $rating ? $rating.' étoile'.((int) $rating > 1 ? 's' : '') : 'Toutes les notes' }}</span></span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                <div class="reviews-rating-options" role="group" aria-labelledby="rating-filter-label">
                    <label class="reviews-rating-option"><input type="radio" name="rating_filter" value="" @checked(!$rating)><span>Toutes les notes</span><i class="fas fa-check" aria-hidden="true"></i></label>
                    @for($filterRating = 5; $filterRating >= 1; $filterRating--)
                        <label class="reviews-rating-option"><input type="radio" name="rating_filter" value="{{ $filterRating }}" @checked((int) $rating === $filterRating)><span><span class="reviews-option-stars" aria-hidden="true">{{ str_repeat('★', $filterRating) }}<span>{{ str_repeat('☆', 5 - $filterRating) }}</span></span><span class="reviews-option-caption">{{ $filterRating }} étoile{{ $filterRating > 1 ? 's' : '' }}</span></span><i class="fas fa-check" aria-hidden="true"></i></label>
                    @endfor
                </div>
            </details>
        </div>
        <div class="reviews-search">
            <label for="user_search">Rechercher un client</label>
            <div class="reviews-search-wrap"><i class="fas fa-search" aria-hidden="true"></i><input id="user_search" name="user_search" value="{{ $userSearch }}" class="form-control" type="search" maxlength="255" placeholder="Saisissez le nom d’un client…" autocomplete="off"></div>
        </div>
        <noscript><button class="btn btn-primary" type="submit">Filtrer</button></noscript>
        <a class="reviews-reset" href="{{ route('reviews.index') }}" @if(!$rating && !$userSearch) hidden @endif>Réinitialiser</a>
    </form>
    <p id="reviews-feedback" role="status" aria-live="polite" class="small mt-3 mb-0"></p>
    <div id="reviews-list">
    <div class="reviews-results"><span><strong>{{ $reviews->total() }}</strong> avis {{ ($rating || $userSearch) ? 'correspondant à votre recherche' : 'partagé'.($reviews->total() > 1 ? 's' : '') }}</span><span>Les plus récents en premier</span></div>
    <div class="row g-4">
        @forelse($reviews as $review)
            <div class="col-md-6 col-lg-4"><article class="surface review-card h-100 p-4 community-review">
                <div class="text-warning mb-2" aria-label="{{ $review->rating }} étoiles">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= $review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor</div>
                <div class="review-service">{{ $review->serviceLabel() }}</div>
                <h4>{{ $review->title }}</h4><p class="text-muted" style="white-space:pre-line">{{ $review->comment }}</p>
                <div class="review-author"><span class="review-avatar" aria-hidden="true">{{ mb_substr($review->user->name, 0, 1) }}</span><div><strong>{{ $review->user->name }}</strong><time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d/m/Y') }}</time></div></div><div class="review-actions">
                @auth
                    @if($review->canBeModifiedBy(auth()->user()))
                        <a href="{{ route('reviews.edit', $review) }}" class="review-action-edit"><i class="far fa-edit" aria-hidden="true"></i> Modifier</a>
                    @endif
                    @if($review->canBeDeletedBy(auth()->user()))
                        <form class="d-inline" method="POST" action="{{ route('reviews.destroy', $review) }}" onsubmit="return confirm('Supprimer cet avis définitivement ?')">@csrf @method('DELETE')<button class="review-action-delete" type="submit" aria-label="Supprimer l’avis" title="Supprimer l’avis"><i class="far fa-trash-alt" aria-hidden="true"></i><span>Supprimer</span></button></form>
                    @endif
                @endauth
            </div></article></div>
        @empty
            <div class="col-12"><div class="surface text-center py-5"><h4>Aucun avis trouvé</h4><p>{{ ($rating || $userSearch) ? 'Aucun avis ne correspond à ces critères.' : 'Soyez le premier à partager votre expérience.' }}</p></div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $reviews->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
<script>
(() => {
    const menu = document.querySelector('.reviews-rating-menu');
    if (!menu) return;
    const form = document.getElementById('reviews-filter-form');
    const search = document.getElementById('user_search');
    const list = document.getElementById('reviews-list');
    const feedback = document.getElementById('reviews-feedback');
    const reset = form.querySelector('.reviews-reset');
    let timer, controller, sequence = 0;
    function invalidate() {
        clearTimeout(timer);
        sequence++;
        if (controller) controller.abort();
    }
    function filterUrl() {
        const url = new URL(form.action);
        for (const [key, value] of new FormData(form)) if (value.trim()) url.searchParams.set(key, value.trim());
        return url;
    }
    async function load(url) {
        invalidate();
        const request = sequence;
        controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        list.setAttribute('aria-busy', 'true');
        feedback.textContent = 'Recherche des avis…';
        try {
            const response = await fetch(url, {signal: controller.signal, headers: {'X-Requested-With': 'XMLHttpRequest'}});
            if (!response.ok) throw new Error('request');
            const documentResult = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updated = documentResult.getElementById('reviews-list');
            if (!updated) throw new Error('response');
            if (request !== sequence) return;
            list.replaceChildren(...updated.childNodes);
            history.replaceState(null, '', url);
            reset.hidden = !search.value.trim() && !form.querySelector('input[name="rating_filter"]:checked').value;
            feedback.textContent = 'Avis mis à jour.';
        } catch (error) {
            if (request === sequence) feedback.textContent = 'Impossible de charger les avis. Modifiez un filtre pour réessayer.';
        } finally {
            clearTimeout(timeout);
            if (request === sequence) list.removeAttribute('aria-busy');
        }
    }
    search.addEventListener('input', () => { invalidate(); timer = setTimeout(() => load(filterUrl()), 350); });
    form.addEventListener('submit', event => { event.preventDefault(); load(filterUrl()); });
    reset.addEventListener('click', event => {
        event.preventDefault(); search.value = '';
        form.querySelector('input[name="rating_filter"][value=""]').checked = true;
        document.getElementById('rating-filter-value').textContent = 'Toutes les notes';
        menu.open = false; load(filterUrl());
    });
    list.addEventListener('click', event => {
        const link = event.target.closest('.pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault(); load(new URL(link.href));
    });
    menu.addEventListener('change', (event) => {
        if (!event.target.matches('input[name="rating_filter"]')) return;
        const rating = event.target.value;
        document.getElementById('rating-filter-value').textContent = rating ? rating + ' étoile' + (Number(rating) > 1 ? 's' : '') : 'Toutes les notes';
        menu.open = false;
        menu.querySelector('summary').focus();
        load(filterUrl());
    });
    document.addEventListener('click', (event) => { if (!menu.contains(event.target)) menu.open = false; });
    menu.addEventListener('keydown', (event) => { if (event.key === 'Escape') { menu.open = false; menu.querySelector('summary').focus(); } });
})();
</script>
@endsection
