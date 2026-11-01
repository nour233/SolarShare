@extends($isAdmin ? 'layouts.back' : 'layouts.front')

@section('title', 'SolarShare SmartPickup')

@section('content')
<div class="container{{ $isAdmin ? '-fluid' : '' }} py-4" style="max-width:1180px">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <span class="text-uppercase small fw-bold" style="letter-spacing:.12em;color:#278658">
                SolarShare SmartPickup
            </span>
            <h1 class="h2 mt-1 mb-2">Trouvez le meilleur point de retrait</h1>
            <p class="text-muted mb-0">
                Des recommandations explicables selon la distance à vol d’oiseau, la capacité disponible et votre horaire préféré.
                Il s’agit d’un classement algorithmique, pas d’un modèle d’IA entraîné.
            </p>
        </div>
        <a
            href="{{ $isAdmin ? route('admin.deliveries.index') : route('rentals.show', $rental) }}"
            class="btn btn-outline-secondary"
        >
            Retour
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Vérifiez les informations saisies.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Votre recherche</h2>
            <form method="GET" action="{{ $formRoute }}" id="smart-pickup-search">
                <div class="row g-3">
                    @if($isAdmin)
                        <div class="col-12">
                            <label for="rental_id" class="form-label fw-semibold">Location</label>
                            <select class="form-select" id="rental_id" name="rental_id" required>
                                <option value="">Sélectionner une location</option>
                                @foreach($rentals as $option)
                                    <option
                                        value="{{ $option->id }}"
                                        @selected((string) request('rental_id') === (string) $option->id)
                                    >
                                        #{{ str_pad($option->id, 4, '0', STR_PAD_LEFT) }}
                                        — {{ $option->equipment?->title ?? 'Équipement' }}
                                        ({{ $option->statusLabel() }})
                                    </option>
                                @endforeach
                            </select>
                            @if($rentals->isEmpty())
                                <div class="form-text text-warning">Aucune location active disponible.</div>
                            @endif
                        </div>
                    @else
                        <div class="col-12">
                            <label class="form-label fw-semibold">Location</label>
                            <div class="form-control bg-light">
                                #{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}
                                — {{ $rental->equipment?->title ?? 'Équipement' }}
                                <span class="text-muted">({{ $rental->start_date->format('d/m/Y') }} – {{ $rental->end_date->format('d/m/Y') }})</span>
                            </div>
                        </div>
                    @endif

                    <div class="{{ $isAdmin ? 'col-md-4' : 'col-md-6' }}">
                        <label for="date" class="form-label fw-semibold">Date de retrait</label>
                        <input
                            class="form-control"
                            id="date"
                            type="date"
                            name="date"
                            value="{{ $selectedDate }}"
                            min="{{ max(today()->format('Y-m-d'), $rental?->start_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}"
                            max="{{ $rental?->end_date?->format('Y-m-d') }}"
                            required
                        >
                    </div>
                    @if($isAdmin)
                    <div class="col-md-4">
                        <label for="preferred_time" class="form-label fw-semibold">Heure souhaitée <span class="text-muted fw-normal">(facultative)</span></label>
                        <input class="form-control" id="preferred_time" type="time" name="preferred_time" value="{{ $preferredTime }}">
                    </div>
                    @else
                    <div class="col-md-6">
                        <label for="favorite_pickup_point_id" class="form-label fw-semibold">Point favori à privilégier</label>
                        <select class="form-select" id="favorite_pickup_point_id" name="favorite_pickup_point_id">
                            <option value="">Aucun favori pour cette recherche</option>
                            @foreach($pickupPoints as $point)
                                <option value="{{ $point->id }}" @selected((string) $preferences['favorite_pickup_point_id'] === (string) $point->id)>
                                    {{ $point->name }} — {{ $point->city }}{{ $point->status === 'active' ? '' : ' — indisponible' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="preferred_time" class="form-label fw-semibold">Heure souhaitée cette fois-ci <span class="text-muted fw-normal">(facultative)</span></label>
                        <input class="form-control" id="preferred_time" type="time" name="preferred_time" value="{{ $preferredTime }}">
                        <div class="form-text">Si renseignée, cette heure remplace votre plage préférée pour le classement de cette recherche.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="preferred_start_time" class="form-label fw-semibold">Début de plage préférée</label>
                        <input class="form-control" id="preferred_start_time" type="time" name="preferred_start_time" value="{{ $preferences['preferred_start_time'] }}">
                    </div>
                    <div class="col-md-4">
                        <label for="preferred_end_time" class="form-label fw-semibold">Fin de plage préférée</label>
                        <input class="form-control" id="preferred_end_time" type="time" name="preferred_end_time" value="{{ $preferences['preferred_end_time'] }}">
                    </div>
                    <div class="col-md-4">
                        <label for="preferred_city" class="form-label fw-semibold">Ville préférée</label>
                        <input class="form-control" id="preferred_city" type="text" name="preferred_city" maxlength="100" value="{{ $preferences['preferred_city'] }}" placeholder="Ex. Tunis">
                    </div>
                    @endif
                    <div class="col-md-4">
                        <label for="latitude" class="form-label fw-semibold">Latitude <span class="text-muted fw-normal">(facultative)</span></label>
                        <input class="form-control" id="latitude" type="number" step="any" min="-90" max="90" name="latitude" value="{{ $latitude }}" placeholder="36.8065">
                    </div>
                    <div class="col-md-4">
                        <label for="longitude" class="form-label fw-semibold">Longitude <span class="text-muted fw-normal">(facultative)</span></label>
                        <input class="form-control" id="longitude" type="number" step="any" min="-180" max="180" name="longitude" value="{{ $longitude }}" placeholder="10.1815">
                    </div>
                    <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                        <button class="btn text-white px-4" type="submit" style="background:#278658">
                            <span class="submit-label"><i class="fa fa-search me-2"></i>Rechercher les meilleurs points</span>
                            <span class="submit-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Recherche…</span>
                        </button>
                        <span class="small text-muted">La distance est estimée à vol d’oiseau, sans API externe.</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!$isAdmin && $personalization['enabled'])
        <div class="alert {{ $personalization['favorite_available'] === false ? 'alert-warning' : 'alert-success' }} border-0 shadow-sm mb-4" role="status">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <strong>Vos préférences ont été prises en compte.</strong>
                    <div class="small mt-1">
                        @if($personalization['favorite_name'])
                            Point favori : {{ $personalization['favorite_name'] }}.
                            @if($personalization['favorite_available'] === true)
                                Un créneau réservable est disponible pour cette date.
                            @elseif($personalization['favorite_available'] === false)
                                Ce point n’a pas de créneau réservable parmi les résultats pour cette date.
                            @else
                                Choisissez une date pour vérifier sa disponibilité.
                            @endif
                        @else
                            Aucun point favori n’est sélectionné pour cette recherche.
                        @endif
                        @if($personalization['preferred_start_time'] && $personalization['preferred_end_time'])
                            Plage horaire : {{ $personalization['preferred_start_time'] }}–{{ $personalization['preferred_end_time'] }}.
                        @endif
                        @if($personalization['preferred_city'])
                            Ville préférée : {{ $personalization['preferred_city'] }}.
                        @endif
                    </div>
                </div>
                @if($personalization['alternative_name'])
                    <div class="small">
                        <strong>Alternative valide :</strong> {{ $personalization['alternative_name'] }}.
                        Elle figure parmi les points ayant un créneau conforme aux règles de disponibilité et de capacité.
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($rental)
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h4 mb-1">Points disponibles</h2>
                <p class="text-muted mb-0">
                    {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->translatedFormat('l d F Y') : 'Choisissez une date pour rechercher' }}
                    @if($preferredTime)
                        · Heure souhaitée {{ $preferredTime }}
                    @elseif(!$isAdmin && $preferences['preferred_start_time'] && $preferences['preferred_end_time'])
                        · Plage préférée {{ $preferences['preferred_start_time'] }}–{{ $preferences['preferred_end_time'] }}
                    @endif
                </p>
            </div>
            @if($results)
                <span class="badge rounded-pill bg-success">{{ count($results) }} recommandation(s)</span>
            @endif
        </div>

        @forelse($results as $index => $result)
            <article class="card border-0 shadow-sm mb-3" style="border-radius:16px;overflow:hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <h3 class="h5 mb-0">{{ $result['name'] }}</h3>
                                @if($index === 0)
                                    <span class="badge rounded-pill text-bg-success">Recommandé</span>
                                @endif
                                <span class="badge rounded-pill bg-light text-dark">{{ $result['score'] }}/100</span>
                            </div>
                            <div class="text-muted">{{ $result['address'] }}, {{ $result['city'] }}</div>
                            <div class="small text-muted mt-1">
                                @if($result['distance_km'] !== null)
                                    {{ number_format($result['distance_km'], 2, ',', ' ') }} km à vol d’oiseau ·
                                @endif
                                {{ $result['remaining_capacity'] }} place(s) restante(s) sur les créneaux futurs
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Score de recommandation</div>
                            <div class="progress mt-1" role="progressbar" aria-label="Score de recommandation" aria-valuenow="{{ $result['score'] }}" aria-valuemin="0" aria-valuemax="100" style="width:160px;height:9px">
                                <div class="progress-bar" style="width:{{ $result['score'] }}%;background:#278658"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-lg-5">
                            <h4 class="small fw-bold text-uppercase text-muted">Pourquoi ce point ?</h4>
                            <ul class="small mb-0 ps-3">
                                @foreach($result['explanation'] as $reason)
                                    <li class="mb-1">{{ $reason }}</li>
                                @endforeach
                            </ul>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                @foreach($result['score_breakdown'] as $factor => $component)
                                    <span class="badge rounded-pill bg-light text-dark border">
                                        @php
                                            $factorLabels = [
                                                'distance' => 'Distance',
                                                'capacity' => 'Capacité',
                                                'time' => 'Horaire',
                                                'personalization' => 'Préférences',
                                            ];
                                        @endphp
                                        {{ $factorLabels[$factor] ?? $factor }} :
                                        {{ number_format($component['score'], 0) }}/100
                                        · poids {{ number_format($component['weight_percent'], 0) }}%
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <h4 class="small fw-bold text-uppercase text-muted">Créneaux disponibles</h4>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($result['slots'] as $slot)
                                    <a
                                        class="btn btn-sm btn-outline-success"
                                        href="{{ $confirmRoute }}?{{ http_build_query([
                                            'rental_id' => $rental->id,
                                            'pickup_point_id' => $result['pickup_point_id'],
                                            'date' => $selectedDate,
                                            'scheduled_time' => $slot['start'],
                                            'preferred_time' => $preferredTime,
                                            'latitude' => $latitude,
                                            'longitude' => $longitude,
                                            'favorite_pickup_point_id' => $preferences['favorite_pickup_point_id'] ?? null,
                                            'preferred_city' => $preferences['preferred_city'] ?? null,
                                            'preferred_start_time' => $preferences['preferred_start_time'] ?? null,
                                            'preferred_end_time' => $preferences['preferred_end_time'] ?? null,
                                        ]) }}"
                                    >
                                        <i class="far fa-clock me-1"></i>{{ $slot['start'] }}–{{ $slot['end'] }}
                                        <span class="badge bg-success ms-1">{{ $slot['remaining'] }} dispo.</span>
                                        <span class="visually-hidden">Choisir ce créneau</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            @if($selectedDate)
                <div class="alert alert-light border text-center py-4">
                    <i class="fa fa-calendar-times fa-2x mb-2 text-muted"></i>
                    <h3 class="h6">Aucun créneau disponible à cette date</h3>
                    <p class="text-muted mb-0">Essayez une autre date ou vérifiez les horaires d’ouverture des points.</p>
                </div>
            @else
                <div class="text-center text-muted py-3">Saisissez une date pour afficher les points et créneaux recommandés.</div>
            @endif
        @endforelse
    @endif
</div>

<script>
document.getElementById('smart-pickup-search')?.addEventListener('submit', function () {
    this.querySelector('.submit-label')?.classList.add('d-none');
    this.querySelector('.submit-loading')?.classList.remove('d-none');
});
</script>
@endsection
