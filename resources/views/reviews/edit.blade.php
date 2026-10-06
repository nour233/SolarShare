@extends('layouts.front')
@section('title', 'Modifier mon avis')
@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-7">
    <div class="text-center mb-4"><h6 class="text-primary">Votre expérience</h6><h1>Modifier mon avis</h1><p>La modification est possible pendant 5 minutes après publication.</p></div>
    <div class="surface p-4 p-lg-5">@include('reviews.form', ['action' => route('reviews.update', $review), 'method' => 'PUT', 'review' => $review])</div>
</div></div></div>
@endsection
