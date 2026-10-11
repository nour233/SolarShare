@extends('layouts.auth')
@section('title', 'Nouveau mot de passe')
@section('content')
<p class="auth-eyebrow">VOTRE COMPTE, EN SÉCURITÉ</p>
<h1>Choisissez votre nouveau mot de passe.</h1>
<p class="auth-intro">Utilisez au moins 8 caractères et confirmez votre choix.</p>
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<div class="register-fields">
<div><label for="email">Adresse e-mail</label><input class="auth-input" id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email"></div>
<div><label for="password">Nouveau mot de passe</label><input class="auth-input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
<div><label for="password_confirmation">Confirmer le mot de passe</label><input class="auth-input" id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
</div><button class="auth-submit" type="submit">Enregistrer mon mot de passe <span aria-hidden="true">↗</span></button>
</form><p class="change-email mt-3"><a href="{{ route('password.request') }}">Demander un nouveau lien</a></p>
@endsection
