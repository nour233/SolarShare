@extends('layouts.front')
@section('title', 'Équipements')
@section('content')
@include('front.partials.equipments')
<div class="container pb-4">{{ $equipments->links('pagination::bootstrap-5') }}</div>
@endsection
