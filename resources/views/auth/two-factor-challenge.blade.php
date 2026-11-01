@extends('layouts.auth')
@section('title', 'Double authentification')
@section('content')
<link href="{{ asset('front/css/profile.css') }}" rel="stylesheet">
<p class="auth-eyebrow">DOUBLE AUTHENTIFICATION</p>
<h1>Entrez votre code</h1>
<p class="auth-intro">Ouvrez votre application d’authentification et saisissez le code à 6 chiffres affiché pour SolarShare.</p>
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('two-factor.verify') }}">
@csrf
<div class="register-fields">
<div><label for="code">Code à 6 chiffres</label><input class="auth-input profile-otp" id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" placeholder="123456" autofocus></div>
</div>
<details class="mt-3"><summary class="small">Téléphone perdu ? Utiliser un code de récupération</summary>
<div class="register-fields mt-2"><div><label for="recovery_code">Code de récupération</label><input class="auth-input" id="recovery_code" name="recovery_code" placeholder="abcde-fghij" autocomplete="off"></div></div>
</details>
<button class="auth-submit mt-3" type="submit">Vérifier <span aria-hidden="true">↗</span></button>
<p class="change-email mt-3"><a href="{{ route('login') }}">← Retour à la connexion</a></p>
</form>
@endsection
