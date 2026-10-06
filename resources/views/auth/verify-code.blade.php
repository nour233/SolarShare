@extends('layouts.auth')
@section('title', 'Vérifier mon adresse')
@section('content')
<div class="auth-step"><span class="step-mark">✓</span> Votre compte <span class="step-line"></span><span class="step-mark">2</span> Vérification</div>
<div class="mail-symbol" aria-hidden="true">✉</div>

<h1>Vérifiez votre adresse e-mail</h1>
<p class="auth-intro">Un code de confirmation a été envoyé à :<strong class="email-tag">{{ $email }}</strong></p>
@if(session('status'))<div class="auth-alert success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="auth-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('register.verify', [], false) }}" id="verification-form">
@csrf
<label for="code">Votre code de vérification</label>
<input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" class="auth-input code-input" placeholder="000000" aria-describedby="code-hint" required autofocus>
<p class="field-hint" id="code-hint">6 chiffres · Valable 10 minutes</p>
<button class="auth-submit" type="submit">Activer mon compte <span aria-hidden="true">↗</span></button>
</form>
<form method="POST" action="{{ route('register.resend', [], false) }}" class="resend-row">@csrf Vous n’avez rien reçu ? <button class="resend-button" type="submit">Renvoyer le code</button></form>
<p class="change-email"><a href="{{ route('register', [], false) }}">Modifier mon adresse e-mail</a></p>
@endsection
@section('scripts')
<script>
const field=document.getElementById('code');field.addEventListener('input',()=>{field.value=field.value.replace(/\D/g,'').slice(0,6)});
document.getElementById('verification-form').addEventListener('submit',function(){const button=this.querySelector('button');button.disabled=true;button.textContent='Vérification en cours…'});
</script>
@endsection
