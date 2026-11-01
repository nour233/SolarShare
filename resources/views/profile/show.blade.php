@extends($user->isAdmin() ? 'layouts.back' : 'layouts.front')
@section('title', 'Mon profil')
@section('content')
<link href="{{ asset('front/css/profile.css') }}" rel="stylesheet">
<div class="{{ $user->isAdmin() ? '' : 'container py-5' }} profile-page">
@unless($user->isAdmin())
<div class="text-center mx-auto mb-5" style="max-width:600px"><h6 class="text-primary">Votre espace</h6><h1>Mon profil</h1><p class="mb-0">Gérez vos informations, votre mot de passe et la sécurité de votre compte.</p></div>
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@endunless

<div class="profile-grid">
<nav class="profile-nav" aria-label="Sections du profil">
<div class="profile-identity">
@if($user->avatarUrl())<img class="profile-avatar" src="{{ $user->avatarUrl() }}" alt="Photo de profil">@else<span class="profile-avatar is-initials" aria-hidden="true">{{ $user->initials() }}</span>@endif
<strong>{{ $user->name }}</strong><small>{{ $user->email }}</small>
<span class="profile-pill">{{ $user->isAdmin() ? 'Administrateur' : 'Client' }}</span>
</div>
<a href="#informations"><i class="far fa-user" aria-hidden="true"></i>Informations</a>
<a href="#mot-de-passe"><i class="fas fa-key" aria-hidden="true"></i>Mot de passe</a>
@unless($user->isAdmin())<a href="#preferences-retrait"><i class="fas fa-map-marker-alt" aria-hidden="true"></i>Préférences de retrait</a>@endunless
<a href="#visage"><i class="fas fa-camera" aria-hidden="true"></i>Connexion par visage</a>
<a href="#securite"><i class="fas fa-shield-alt" aria-hidden="true"></i>Double authentification @if($user->hasTwoFactorEnabled())<span class="profile-dot" title="Activée"></span>@endif</a>
@unless($user->isAdmin())<a href="#suppression" class="text-danger"><i class="far fa-trash-alt" aria-hidden="true"></i>Supprimer le compte</a>@endunless
</nav>

<div class="d-grid gap-4">
{{-- Informations --}}
<section class="profile-card" id="informations">
<h2>Informations personnelles</h2>
@php($errs = $errors->profile)
<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="profile-photo-row">
@if($user->avatarUrl())<img class="profile-avatar is-large" src="{{ $user->avatarUrl() }}" alt="">@else<span class="profile-avatar is-large is-initials" aria-hidden="true">{{ $user->initials() }}</span>@endif
<div><label class="form-label" for="avatar">Photo de profil</label><input class="form-control @if($errs->has('avatar')) is-invalid @endif" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
<small class="text-muted">JPG, PNG ou WebP, 2 Mo maximum.</small>@if($errs->has('avatar'))<div class="invalid-feedback">{{ $errs->first('avatar') }}</div>@endif
@if($user->avatar)<div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar" value="1"><label class="form-check-label" for="remove_avatar">Supprimer la photo</label></div>@endif
</div></div>
<div class="row g-3 mt-1">
<div class="col-md-6"><label class="form-label" for="name">Nom</label><input class="form-control @if($errs->has('name')) is-invalid @endif" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">@if($errs->has('name'))<div class="invalid-feedback">{{ $errs->first('name') }}</div>@endif</div>
<div class="col-md-6"><label class="form-label" for="email">Adresse e-mail</label><input class="form-control @if($errs->has('email')) is-invalid @endif" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">@if($errs->has('email'))<div class="invalid-feedback">{{ $errs->first('email') }}</div>@endif</div>
<div class="col-md-6"><label class="form-label" for="profile_current_password">Mot de passe actuel <span class="fw-normal text-muted">(seulement si vous changez d’e-mail)</span></label><input class="form-control @if($errs->has('current_password')) is-invalid @endif" type="password" id="profile_current_password" name="current_password" autocomplete="current-password">@if($errs->has('current_password'))<div class="invalid-feedback">{{ $errs->first('current_password') }}</div>@endif</div>
</div>
<button class="btn btn-primary mt-4" type="submit">Enregistrer</button>
</form>
</section>

{{-- Password --}}
<section class="profile-card" id="mot-de-passe">
<h2>Mot de passe</h2>
@php($errs = $errors->password)
<form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-4"><label class="form-label" for="current_password">Mot de passe actuel</label><input class="form-control @if($errs->has('current_password')) is-invalid @endif" type="password" id="current_password" name="current_password" required autocomplete="current-password">@if($errs->has('current_password'))<div class="invalid-feedback">{{ $errs->first('current_password') }}</div>@endif</div>
<div class="col-md-4"><label class="form-label" for="password">Nouveau mot de passe</label><input class="form-control @if($errs->has('password')) is-invalid @endif" type="password" id="password" name="password" required minlength="8" autocomplete="new-password">@if($errs->has('password'))<div class="invalid-feedback">{{ $errs->first('password') }}</div>@endif</div>
<div class="col-md-4"><label class="form-label" for="password_confirmation">Confirmation</label><input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
</div>
<button class="btn btn-primary mt-4" type="submit">Changer le mot de passe</button>
</form>
</section>

@unless($user->isAdmin())
<section class="profile-card" id="preferences-retrait">
<h2>Mes préférences de retrait</h2>
<p class="text-muted">Choisissez vos options habituelles. Elles guideront les recommandations SmartPickup, sans réserver de créneau ni empêcher un autre choix.</p>
@php($errs = $errors->logisticsPreferences)
<form method="POST" action="{{ route('profile.logistics-preferences.update') }}">
@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6">
<label class="form-label" for="preferred_pickup_point_id">Point de retrait favori</label>
<select class="form-select @if($errs->has('preferred_pickup_point_id')) is-invalid @endif" id="preferred_pickup_point_id" name="preferred_pickup_point_id">
<option value="">Aucun point préféré</option>
@if($logisticsPreference?->preferredPickupPoint && $logisticsPreference->preferredPickupPoint->status !== 'active')
<option value="{{ $logisticsPreference->preferred_pickup_point_id }}" selected>{{ $logisticsPreference->preferredPickupPoint->name }} — indisponible</option>
@endif
@foreach($pickupPoints as $point)
<option value="{{ $point->id }}" @selected((string) old('preferred_pickup_point_id', $logisticsPreference?->preferred_pickup_point_id) === (string) $point->id)>{{ $point->name }} — {{ $point->city }}</option>
@endforeach
</select>
@if($errs->has('preferred_pickup_point_id'))<div class="invalid-feedback">{{ $errs->first('preferred_pickup_point_id') }}</div>@endif
</div>
<div class="col-md-3">
<label class="form-label" for="preferred_start_time">Heure de début préférée</label>
<input class="form-control @if($errs->has('preferred_start_time')) is-invalid @endif" type="time" id="preferred_start_time" name="preferred_start_time" value="{{ old('preferred_start_time', $logisticsPreference?->preferred_start_time ? substr($logisticsPreference->preferred_start_time, 0, 5) : '') }}">
@if($errs->has('preferred_start_time'))<div class="invalid-feedback">{{ $errs->first('preferred_start_time') }}</div>@endif
</div>
<div class="col-md-3">
<label class="form-label" for="preferred_end_time">Heure de fin préférée</label>
<input class="form-control @if($errs->has('preferred_end_time')) is-invalid @endif" type="time" id="preferred_end_time" name="preferred_end_time" value="{{ old('preferred_end_time', $logisticsPreference?->preferred_end_time ? substr($logisticsPreference->preferred_end_time, 0, 5) : '') }}">
@if($errs->has('preferred_end_time'))<div class="invalid-feedback">{{ $errs->first('preferred_end_time') }}</div>@endif
</div>
<div class="col-12">
<label class="form-label" for="preferred_city">Ville préférée <span class="text-muted fw-normal">(facultative)</span></label>
<input class="form-control @if($errs->has('preferred_city')) is-invalid @endif" type="text" id="preferred_city" name="preferred_city" maxlength="100" value="{{ old('preferred_city', $logisticsPreference?->preferred_city) }}" autocomplete="address-level2">
@if($errs->has('preferred_city'))<div class="invalid-feedback">{{ $errs->first('preferred_city') }}</div>@endif
<div class="form-text">La ville aide à repérer les points correspondants. Aucune coordonnée n’est déduite automatiquement.</div>
</div>
</div>
<div class="d-flex flex-wrap gap-2 mt-4">
<button class="btn btn-primary" type="submit">Enregistrer mes préférences</button>
@if($logisticsPreference)
<button class="btn btn-outline-secondary" type="submit" form="reset-logistics-preferences">Réinitialiser</button>
@endif
</div>
</form>
@if($logisticsPreference)
<form id="reset-logistics-preferences" method="POST" action="{{ route('profile.logistics-preferences.reset') }}" onsubmit="return confirm('Réinitialiser vos préférences de retrait ?')">@csrf @method('DELETE')</form>
@endif
</section>
@endunless

{{-- Face login --}}
<section class="profile-card" id="visage">
<h2>Connexion par visage</h2>
<p class="text-muted">Votre caméra est utilisée uniquement dans votre navigateur. SolarShare conserve un modèle chiffré du visage, jamais votre photo.</p>
@if($faceDescriptorAvailable)
<div class="alert alert-success">La connexion par visage est activée pour ce compte.</div>
<form method="POST" action="{{ route('face-login.destroy') }}" onsubmit="return confirm('Désactiver la connexion par visage ?')">@csrf @method('DELETE')
<div class="row g-2 align-items-end"><div class="col-md-6"><label class="form-label" for="remove_face_password">Mot de passe actuel</label><input class="form-control" type="password" id="remove_face_password" name="current_password" required></div><div class="col-md-auto"><button class="btn btn-outline-danger" type="submit">Désactiver</button></div></div>
</form>
@else
<form method="POST" action="{{ route('face-login.store') }}" data-face-enrollment>@csrf
<div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label" for="face_password">Mot de passe actuel</label><input class="form-control" type="password" id="face_password" name="current_password" required autocomplete="current-password"></div><div class="col-md-auto"><button class="btn btn-outline-primary" type="button" data-face-capture><i class="fas fa-camera me-2" aria-hidden="true"></i>Enregistrer mon visage</button></div></div>
<small class="text-muted d-block mt-2" data-face-status>Une seule personne doit être visible et la caméra doit être autorisée.</small>
</form>
@endif
</section>

{{-- Two-factor authentication --}}
<section class="profile-card" id="securite">
@php($errs = $errors->twoFactor)
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><h2 class="mb-0">Double authentification (2FA)</h2>
@if($user->hasTwoFactorEnabled())<span class="profile-status is-on"><i class="fas fa-check-circle me-1" aria-hidden="true"></i>Activée</span>@elseif($qrCode)<span class="profile-status is-pending">Configuration en cours</span>@else<span class="profile-status">Désactivée</span>@endif</div>
<p class="text-muted">En plus du mot de passe, un code à 6 chiffres généré par une application (Google Authenticator, Microsoft Authenticator, Authy…) est demandé à chaque connexion.</p>

@if($user->hasTwoFactorEnabled())
@if(session('showRecoveryCodes'))
<div class="profile-codes"><strong><i class="fas fa-life-ring me-2" aria-hidden="true"></i>Codes de récupération</strong><p class="small mb-2">Notez-les dans un endroit sûr : chacun permet de se connecter <strong>une seule fois</strong> si vous perdez votre téléphone.</p>
<div class="profile-code-list">@foreach($user->two_factor_recovery_codes ?? [] as $code)<code>{{ $code }}</code>@endforeach</div></div>
@else
<p class="small">Il vous reste <strong>{{ count($user->two_factor_recovery_codes ?? []) }}</strong> code(s) de récupération.</p>
@endif
<div class="row g-3 mt-1">
<form class="col-md-6" method="POST" action="{{ route('two-factor.recovery-codes') }}">@csrf
<label class="form-label" for="regen_password">Nouveaux codes de récupération</label>
<div class="input-group"><input class="form-control" type="password" id="regen_password" name="current_password" placeholder="Mot de passe" required autocomplete="current-password"><button class="btn btn-outline-primary" type="submit">Régénérer</button></div>
</form>
<form class="col-md-6" method="POST" action="{{ route('two-factor.disable') }}">@csrf @method('DELETE')
<label class="form-label" for="disable_password">Désactiver la 2FA</label>
<div class="input-group"><input class="form-control" type="password" id="disable_password" name="current_password" placeholder="Mot de passe" required autocomplete="current-password"><button class="btn btn-outline-danger" type="submit">Désactiver</button></div>
</form>
</div>
@if($errs->has('current_password'))<div class="text-danger small mt-2">{{ $errs->first('current_password') }}</div>@endif

@elseif($qrCode)
<div class="profile-setup">
<div class="profile-qr">{!! $qrCode !!}</div>
<div>
<ol class="small ps-3">
<li>Ouvrez votre application d’authentification et <strong>scannez ce QR code</strong>.</li>
<li>Ou saisissez cette clé manuellement : <code class="profile-secret">{{ trim(chunk_split($user->two_factor_secret, 4, ' ')) }}</code></li>
<li>Entrez le code à 6 chiffres affiché pour confirmer.</li>
</ol>
<form method="POST" action="{{ route('two-factor.confirm') }}">@csrf
<label class="form-label" for="code">Code de l’application</label>
<div class="input-group"><input class="form-control profile-otp @if($errs->has('code')) is-invalid @endif" id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" placeholder="123456" required autofocus><button class="btn btn-primary" type="submit">Confirmer</button></div>
@if($errs->has('code'))<div class="text-danger small mt-1">{{ $errs->first('code') }}</div>@endif
</form>
<form method="POST" action="{{ route('two-factor.disable') }}" class="mt-3">@csrf @method('DELETE')
<details><summary class="small text-muted">Annuler la configuration</summary><div class="input-group mt-2"><input class="form-control" type="password" name="current_password" placeholder="Mot de passe" required autocomplete="current-password"><button class="btn btn-outline-secondary" type="submit">Annuler</button></div></details>
</form>
</div>
</div>

@else
<form method="POST" action="{{ route('two-factor.enable') }}" class="row g-2 align-items-end">@csrf
<div class="col-md-6"><label class="form-label" for="enable_password">Confirmez votre mot de passe</label><input class="form-control @if($errs->has('current_password')) is-invalid @endif" type="password" id="enable_password" name="current_password" required autocomplete="current-password">@if($errs->has('current_password'))<div class="invalid-feedback">{{ $errs->first('current_password') }}</div>@endif</div>
<div class="col-md-6"><button class="btn btn-primary" type="submit"><i class="fas fa-shield-alt me-2" aria-hidden="true"></i>Activer la double authentification</button></div>
</form>
@endif
</section>

{{-- Delete account --}}
@unless($user->isAdmin())
<section class="profile-card is-danger" id="suppression">
<h2>Supprimer mon compte</h2>
@php($errs = $errors->deletion)
<p class="text-muted">Votre compte, vos signalements, réclamations et avis seront définitivement supprimés. Cette action est irréversible.</p>
<form method="POST" action="{{ route('profile.destroy') }}" class="row g-2 align-items-end" onsubmit="return confirm('Supprimer définitivement votre compte ?')">@csrf @method('DELETE')
<div class="col-md-6"><label class="form-label" for="delete_password">Mot de passe</label><input class="form-control @if($errs->has('delete_password')) is-invalid @endif" type="password" id="delete_password" name="delete_password" required autocomplete="current-password">@if($errs->has('delete_password'))<div class="invalid-feedback">{{ $errs->first('delete_password') }}</div>@endif</div>
<div class="col-md-6"><button class="btn btn-danger" type="submit"><i class="far fa-trash-alt me-2" aria-hidden="true"></i>Supprimer définitivement</button></div>
</form>
</section>
@endunless
</div>
</div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="{{ asset('front/js/face-auth.js') }}"></script>
@endsection
