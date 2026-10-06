@extends('layouts.front')
@section('title', 'Laisser un avis')
@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-7">
    <div class="text-center mb-4"><h6 class="text-primary">Votre expérience</h6><h1>Laisser un avis</h1><p>Partagez votre expérience avec la communauté SolarShare.</p></div>
    <div class="surface p-4 p-lg-5">@include('reviews.form', ['action' => route('reviews.store'), 'method' => 'POST', 'review' => null])</div>
</div></div></div>
@endsection
