@extends('layouts.front')
@section('title', 'Modifier mon avis')
@section('content')
<div class="container review-compose">
<a href="{{ route('reviews.index') }}" class="compose-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Tous les avis</a>
<header class="compose-heading"><span>LA COMMUNAUTÉ SOLARSHARE</span><h1>Modifier mon avis</h1><p>Vous pouvez modifier votre avis pendant les 5 minutes suivant sa publication.</p></header>
@include('reviews.form', ['action' => route('reviews.update', $review), 'method' => 'PUT', 'review' => $review])
</div>
@endsection
