@extends('layouts.auth')
@section('title', 'Vérifier votre code')
@section('content')
<p class="auth-eyebrow">RÉINITIALISATION · ÉTAPE 2 SUR 3</p>
<h1>Saisissez votre code.</h1>
<p class="auth-intro">Si un compte existe pour <strong>{{ $email }}</strong>, un code à 6 chiffres a été envoyé. Vérifiez aussi les courriers indésirables.</p>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.code.verify') }}">@csrf
<div class="register-fields"><div><label for="code">Code reçu par e-mail</label><input class="auth-input" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus style="text-align:center;letter-spacing:8px;font-size:24px"></div></div>
<p class="field-hint mt-2">Valable 10 minutes · 5 essais maximum</p>
<button class="auth-submit" type="submit">Vérifier le code <span aria-hidden="true">↗</span></button>
</form>
<form method="POST" action="{{ route('password.email') }}" class="mt-3">@csrf<input type="hidden" name="email" value="{{ $email }}"><button type="submit" class="btn btn-link w-100">Renvoyer un code</button></form>
<p class="change-email mt-3"><a href="{{ route('password.request') }}">Utiliser une autre adresse</a></p>
@endsection
