@extends('layouts.auth')
@section('title', 'Confirmer la connexion')
@section('content')
<p class="auth-eyebrow">VISAGE RECONNU</p>
<h1>Confirmez votre connexion</h1>
<p class="auth-intro">Votre visage a été reconnu. Saisissez votre mot de passe pour terminer la connexion.</p>
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('login.face.password.verify', [], false) }}">
    @csrf
    <div class="register-fields">
        <div><label for="password">Mot de passe</label><input class="auth-input" id="password" type="password" name="password" autocomplete="current-password" required autofocus></div>
    </div>
    <button class="auth-submit" type="submit">Se connecter <span aria-hidden="true">↗</span></button>
</form>
@endsection
