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
    <div class="row g-4">
        @forelse($reviews as $review)
            <div class="col-md-6 col-lg-4"><article class="surface h-100 p-4">
                <div class="text-warning mb-2" aria-label="{{ $review->rating }} étoiles">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= $review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor</div>
                <h4>{{ $review->title }}</h4><p class="text-muted" style="white-space:pre-line">{{ $review->comment }}</p>
                <div class="small text-muted mt-auto">Par {{ $review->user->name }} · {{ $review->created_at->format('d/m/Y') }}</div>
                @auth
                    @if($review->canBeModifiedBy(auth()->user()))
                        <div class="d-flex gap-2 mt-3"><a href="{{ route('reviews.edit', $review) }}" class="btn btn-outline-primary btn-sm">Modifier</a><form method="POST" action="{{ route('reviews.destroy', $review) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button></form></div>
                    @endif
                @endauth
            </article></div>
        @empty
            <div class="col-12"><div class="surface text-center py-5"><h4>Aucun avis pour le moment</h4><p>Soyez le premier à partager votre expérience.</p></div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $reviews->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
