@extends('layouts.auth')
@section('title', 'Se connecter')
@section('content')
<p class="auth-eyebrow">CONTENT DE VOUS REVOIR</p>
<h1>Connectez-vous à SolarShare</h1>
<p class="auth-intro">Saisissez votre adresse e-mail et votre mot de passe pour accéder à votre compte.</p>
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('login', [], false) }}">
@csrf
<div class="register-fields">
<div><label for="email">Adresse e-mail</label><input class="auth-input" id="email" type="email" name="email" autocomplete="email" value="{{ old('email') }}" required maxlength="255" autofocus></div>
<div><label for="password">Mot de passe</label><input class="auth-input" id="password" type="password" name="password" autocomplete="current-password" required></div>
</div>
<div class="d-flex justify-content-between align-items-center mt-3"><label class="form-check-label small"><input class="form-check-input me-2" type="checkbox" name="remember" value="1" @checked(old('remember'))>Se souvenir de moi</label><a href="{{ route('password.request') }}">Mot de passe oublié ?</a></div>
<button class="auth-submit" type="submit">Se connecter <span aria-hidden="true">↗</span></button>
<p class="change-email mt-3">Pas encore de compte ? <a href="{{ route('register', [], false) }}">Créer mon compte</a></p>
</form>
@endsection
