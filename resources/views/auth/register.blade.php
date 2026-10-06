@extends('layouts.auth')
@section('title', 'Créer mon compte')
@section('content')
<div class="auth-step"><span class="step-mark">1</span> Votre compte <span class="step-line"></span><span class="step-mark">2</span> Vérification</div>
<p class="auth-eyebrow">BIENVENUE DANS LA COMMUNAUTÉ</p>
<h1>Le soleil se partage.<br>Rejoignez-nous.</h1>
<p class="auth-intro">Créez votre compte pour commencer votre aventure avec SolarShare.</p>
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('register', [], false) }}">
@csrf
<div class="register-fields">
<div><label for="name">Votre nom</label><input class="auth-input" id="name" name="name" autocomplete="name" value="{{ old('name') }}" required maxlength="255"></div>
<div><label for="email">Adresse e-mail</label><input class="auth-input" id="email" type="email" name="email" autocomplete="email" value="{{ old('email') }}" required maxlength="255"></div>
<div><label for="password">Mot de passe</label><input class="auth-input" id="password" type="password" name="password" autocomplete="new-password" minlength="8" required></div>
<div><label for="password_confirmation">Confirmer le mot de passe</label><input class="auth-input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></div>
</div>
<button class="auth-submit" type="submit">Créer mon compte <span aria-hidden="true">↗</span></button>
<p class="field-hint text-center mt-3">Nous vous enverrons un code pour vérifier votre adresse.</p>
</form>
@endsection
