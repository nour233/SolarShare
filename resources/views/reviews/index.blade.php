@extends('layouts.front')
@section('title', 'Avis clients')
@section('content')
<div class="container py-5">
    <div class="text-center mx-auto mb-5" style="max-width:680px">
        <h6 class="text-primary">L’expérience SolarShare</h6>
        <h1>Les avis de nos clients</h1>
        <p>Découvrez ce que notre communauté pense de nos services.</p>
        @auth
            <a href="{{ route('reviews.create') }}" class="btn btn-primary rounded-pill px-4">Laisser un avis <i class="fa fa-star ms-2"></i></a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4">Connectez-vous pour laisser un avis</a>
        @endauth
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form method="GET" action="{{ route('reviews.index') }}" class="surface mb-4 p-3 d-flex flex-wrap align-items-end gap-3" id="reviews-filter-form">
        <div><label for="rating_filter" class="form-label fw-bold mb-1">Filtrer par note</label><select id="rating_filter" name="rating_filter" class="form-select" onchange="this.form.submit()"><option value="">Toutes les notes</option>@for($filterRating = 5; $filterRating >= 1; $filterRating--)<option value="{{ $filterRating }}" @selected((int) $rating === $filterRating)>{{ $filterRating }} étoile{{ $filterRating > 1 ? 's' : '' }}</option>@endfor</select></div>
        <div><label for="user_search" class="form-label fw-bold mb-1">Rechercher un client</label><input id="user_search" name="user_search" value="{{ $userSearch }}" class="form-control" type="search" maxlength="255" placeholder="Nom du client" autocomplete="off"></div>
        @if($rating || $userSearch)<a class="btn btn-outline-secondary" href="{{ route('reviews.index') }}">Réinitialiser</a>@endif
    </form>
    <div class="row g-4">
        @forelse($reviews as $review)
            <div class="col-md-6 col-lg-4"><article class="surface review-card h-100 p-4">
                <div class="text-warning mb-2" aria-label="{{ $review->rating }} étoiles">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= $review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor</div>
                <div class="small text-primary mb-2">{{ $review->serviceLabel() }}</div>
                <h4>{{ $review->title }}</h4><p class="text-muted" style="white-space:pre-line">{{ $review->comment }}</p>
                <div class="small text-muted mt-auto">Par {{ $review->user->name }} · {{ $review->created_at->format('d/m/Y') }}</div>
                @auth
                    @if($review->canBeModifiedBy(auth()->user()))
                        <a href="{{ route('reviews.edit', $review) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
                    @endif
                    @if($review->canBeDeletedBy(auth()->user()))
                        <form class="d-inline" method="POST" action="{{ route('reviews.destroy', $review) }}" onsubmit="return confirm('Supprimer cet avis définitivement ?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Supprimer l’avis" title="Supprimer l’avis"><i class="far fa-trash-alt" aria-hidden="true"></i></button></form>
                    @endif
                @endauth
            </article></div>
        @empty
            <div class="col-12"><div class="surface text-center py-5"><h4>Aucun avis trouvé</h4><p>{{ $rating ? 'Aucun avis ne correspond à cette note.' : 'Soyez le premier à partager votre expérience.' }}</p></div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $reviews->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
@section('scripts')
<script>
const userSearch = document.getElementById('user_search');
let searchTimer;
userSearch.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => document.getElementById('reviews-filter-form').submit(), 400);
});
</script>
@endsection
