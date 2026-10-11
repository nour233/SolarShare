@extends('layouts.auth')
@section('title', 'Mot de passe oublié')
@section('content')

<p class="auth-eyebrow">RETROUVEZ VOTRE ESPACE</p>
<h1>Un nouveau départ.</h1>
<p class="auth-intro">Indiquez votre adresse e-mail. Nous vous enverrons un code à 6 chiffres pour vérifier votre identité.</p>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.email') }}">@csrf
<div class="register-fields"><div><label for="email">Adresse e-mail</label><input class="auth-input" id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required maxlength="255" autofocus></div></div>
<button class="auth-submit" type="submit">Recevoir le code <span aria-hidden="true">↗</span></button>
</form><p class="change-email mt-3"><a href="{{ route('login') }}">← Retour à la connexion</a></p>
@endsection
