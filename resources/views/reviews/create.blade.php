@extends('layouts.front')
@section('title', 'Laisser un avis')
@section('content')
<div class="container review-compose">
<a href="{{ route('reviews.index') }}" class="compose-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Tous les avis</a>
<header class="compose-heading"><span>LA COMMUNAUTÉ SOLARSHARE</span><h1>Laisser un avis</h1><p>Votre expérience compte. Aidez la communauté à faire le bon choix.</p></header>
@include('reviews.form', ['action' => route('reviews.store'), 'method' => 'POST', 'review' => null])
</div>
@endsection
