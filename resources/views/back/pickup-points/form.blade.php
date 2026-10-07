@extends('layouts.back')

@section(
    'title',
    $pickupPoint->exists
        ? 'Modifier le point de retrait'
        : 'Nouveau point de retrait'
)

@section('content')

{{-- Leaflet CSS --}}
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<style>
    #pickup-map {
        width: 100%;
        height: 420px;
        border-radius: 12px;
        border: 1px solid #dce4dc;
        z-index: 1;
    }

    .location-search-box {
        display: flex;
        gap: 10px;
    }

    .location-search-box .form-control {
        flex: 1;
    }

    .coordinates-box {
        background: #f7f9f7;
        border: 1px solid #dce4dc;
        border-radius: 10px;
        padding: 14px 16px;
    }

    .coordinates-values {
        font-family: monospace;
        font-size: 14px;
    }

    .map-help {
        color: #6c757d;
        font-size: 14px;
        margin-top: 10px;
    }

    .field-error {
        display: block;
        color: #dc3545;
        font-size: 13px;
        margin-top: 6px;
    }

    .location-result {
        display: none;
        margin-top: 10px;
    }
</style>


<a
    href="{{ route('admin.pickup-points.index') }}"
    class="d-inline-block mb-4 text-primary text-decoration-none"
>
    <i class="fa fa-arrow-left me-2"></i>
    Retour aux points de retrait
</a>


<div class="catalog-intro">

    <div>

        <span class="section-kicker">
            GESTION DES LIVRAISONS
        </span>

        <h2>
            {{ $pickupPoint->exists
                ? 'Modifier le point de retrait'
                : 'Ajouter un point de retrait'
            }}
        </h2>

        <p>
            Renseignez les informations du point de retrait
            et sélectionnez sa position sur la carte.
        </p>

    </div>

</div>


@if($errors->any())

    <div class="alert alert-danger">

        <i class="fa fa-exclamation-circle me-2"></i>

        <strong>
            Le formulaire contient des erreurs.
        </strong>

        <div class="mt-1">
            Vérifiez les champs indiqués ci-dessous.
        </div>

    </div>

@endif


<div class="surface p-4">

    <form
        method="POST"
        action="{{ $pickupPoint->exists
            ? route('admin.pickup-points.update', $pickupPoint)
            : route('admin.pickup-points.store')
        }}"
        novalidate
    >

        @csrf

        @if($pickupPoint->exists)
            @method('PUT')
        @endif


        <div class="row g-4">

            {{-- ===================================================== --}}
            {{-- NAME --}}
            {{-- ===================================================== --}}

            <div class="col-md-6">

                <label class="form-label fw-semibold">
                    Nom *
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $pickupPoint->name) }}"
                    placeholder="Ex : SolarShare Tunis Centre"
                >

                @error('name')
                    <div class="field-error">
                        <i class="fa fa-exclamation-circle me-1"></i>
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- ===================================================== --}}
            {{-- CITY --}}
            {{-- ===================================================== --}}

            <div class="col-md-6">

                <label class="form-label fw-semibold">
                    Ville *
                </label>

                <input
                    type="text"
                    id="city"
                    name="city"
                    class="form-control @error('city') is-invalid @enderror"
                    value="{{ old('city', $pickupPoint->city) }}"
                    placeholder="Ex : Tunis"
                >

                @error('city')
                    <div class="field-error">
                        <i class="fa fa-exclamation-circle me-1"></i>
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- ===================================================== --}}
            {{-- ADDRESS --}}
            {{-- ===================================================== --}}

            <div class="col-12">

                <label class="form-label fw-semibold">
                    Adresse *
                </label>

                <input
                    type="text"
                    id="address"
                    name="address"
                    class="form-control @error('address') is-invalid @enderror"
                    value="{{ old('address', $pickupPoint->address) }}"
                    placeholder="Ex : 15 Avenue Habib Bourguiba"
                >

                @error('address')
                    <div class="field-error">
                        <i class="fa fa-exclamation-circle me-1"></i>
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- ===================================================== --}}
            {{-- MAP LOCATION --}}
            {{-- ===================================================== --}}

            <div class="col-12">

                <hr class="my-2">

                <div class="mb-3">

                    <span class="section-kicker">
                        LOCALISATION GPS
                    </span>

                    <h5 class="mt-1 mb-1">
                        Sélectionner l'emplacement
                    </h5>

                    <p class="text-muted mb-0">
                        Recherchez une adresse ou cliquez directement
                        sur la carte.
                    </p>

                </div>


                {{-- Search location --}}

                <div class="location-search-box">

                    <input
                        type="text"
                        id="map-search"
                        class="form-control"
                        placeholder="Ex : Avenue Habib Bourguiba, Tunis"
                    >

                    <button
                        type="button"
                        id="search-location-button"
                        class="btn btn-outline-success"
                    >
                        <i class="fa fa-search me-2"></i>
                        Rechercher
                    </button>

                </div>


                <div
                    id="location-search-message"
                    class="alert location-result"
                ></div>


                {{-- Map --}}

                <div class="mt-3">

                    <div id="pickup-map"></div>

                    <div class="map-help">

                        <i class="fa fa-map-marker-alt me-1"></i>

                        Cliquez sur la carte pour sélectionner
                        l'emplacement.

                        Vous pouvez ensuite déplacer le marqueur
                        pour ajuster précisément la position.

                    </div>

                </div>


                {{-- Hidden coordinates sent to Laravel --}}

                <input
                    type="hidden"
                    name="latitude"
                    id="latitude"
                    value="{{ old('latitude', $pickupPoint->latitude) }}"
                >

                <input
                    type="hidden"
                    name="longitude"
                    id="longitude"
                    value="{{ old('longitude', $pickupPoint->longitude) }}"
                >


                @error('latitude')

                    <div class="field-error mt-2">

                        <i class="fa fa-exclamation-circle me-1"></i>

                        {{ $message }}

                    </div>

                @enderror


                @error('longitude')

                    @if(!$errors->has('latitude'))

                        <div class="field-error mt-2">

                            <i class="fa fa-exclamation-circle me-1"></i>

                            {{ $message }}

                        </div>

                    @endif

                @enderror


                {{-- Selected coordinates display --}}

                <div class="coordinates-box mt-3">

                    <div class="d-flex align-items-center gap-2 mb-2">

                        <i class="fa fa-map-marker-alt text-success"></i>

                        <strong>
                            Emplacement sélectionné
                        </strong>

                    </div>


                    <div
                        id="coordinates-empty"
                        class="text-muted"
                    >
                        Aucun emplacement sélectionné.
                    </div>


                    <div
                        id="coordinates-selected"
                        style="display:none"
                    >

                        <div class="row">

                            <div class="col-md-6">

                                <small class="text-muted">
                                    Latitude
                                </small>

                                <div
                                    id="latitude-display"
                                    class="coordinates-values"
                                ></div>

                            </div>


                            <div class="col-md-6">

                                <small class="text-muted">
                                    Longitude
                                </small>

                                <div
                                    id="longitude-display"
                                    class="coordinates-values"
                                ></div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- OPENING HOURS --}}
            {{-- ===================================================== --}}

            <div class="col-md-6">

                <label class="form-label fw-semibold">
                    Horaires d'ouverture *
                </label>

                <input
                    type="text"
                    name="opening_hours"
                    class="form-control @error('opening_hours') is-invalid @enderror"
                    value="{{ old('opening_hours', $pickupPoint->opening_hours) }}"
                    placeholder="Ex : Lun-Sam 08:00 - 18:00"
                >

                @error('opening_hours')

                    <div class="field-error">

                        <i class="fa fa-exclamation-circle me-1"></i>

                        {{ $message }}

                    </div>

                @enderror

            </div>


            {{-- ===================================================== --}}
            {{-- CAPACITY --}}
            {{-- ===================================================== --}}

            <div class="col-md-3">

                <label class="form-label fw-semibold">
                    Capacité *
                </label>

                <input
                    type="number"
                    name="capacity"
                    class="form-control @error('capacity') is-invalid @enderror"
                    value="{{ old('capacity', $pickupPoint->capacity) }}"
                    placeholder="50"
                >

                @error('capacity')

                    <div class="field-error">

                        <i class="fa fa-exclamation-circle me-1"></i>

                        {{ $message }}

                    </div>

                @enderror

            </div>


            {{-- ===================================================== --}}
            {{-- STATUS --}}
            {{-- ===================================================== --}}

            <div class="col-md-3">

                <label class="form-label fw-semibold">
                    Statut *
                </label>

                <select
                    name="status"
                    class="form-select @error('status') is-invalid @enderror"
                >

                    <option value="">
                        -- Choisir --
                    </option>


                    <option
                        value="active"
                        @selected(
                            old(
                                'status',
                                $pickupPoint->status ?? 'active'
                            ) === 'active'
                        )
                    >
                        Actif
                    </option>


                    <option
                        value="inactive"
                        @selected(
                            old(
                                'status',
                                $pickupPoint->status
                            ) === 'inactive'
                        )
                    >
                        Inactif
                    </option>

                </select>


                @error('status')

                    <div class="field-error">

                        <i class="fa fa-exclamation-circle me-1"></i>

                        {{ $message }}

                    </div>

                @enderror

            </div>

        </div>


        <hr class="my-4">


        {{-- ========================================================= --}}
        {{-- ACTIONS --}}
        {{-- ========================================================= --}}

        <div class="d-flex justify-content-end gap-2">

            <a
                href="{{ route('admin.pickup-points.index') }}"
                class="btn btn-outline-secondary"
            >
                Annuler
            </a>


            <button
                type="submit"
                class="btn btn-primary"
            >

                <i class="fa fa-save me-2"></i>

                {{ $pickupPoint->exists
                    ? 'Enregistrer les modifications'
                    : 'Ajouter le point'
                }}

            </button>

        </div>

    </form>

</div>


{{-- Leaflet JavaScript --}}
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const latitudeInput =
        document.getElementById('latitude');

    const longitudeInput =
        document.getElementById('longitude');

    const latitudeDisplay =
        document.getElementById('latitude-display');

    const longitudeDisplay =
        document.getElementById('longitude-display');

    const coordinatesEmpty =
        document.getElementById('coordinates-empty');

    const coordinatesSelected =
        document.getElementById('coordinates-selected');

    const searchInput =
        document.getElementById('map-search');

    const searchButton =
        document.getElementById('search-location-button');

    const searchMessage =
        document.getElementById('location-search-message');

    const addressInput =
        document.getElementById('address');

    const cityInput =
        document.getElementById('city');


    /*
    |--------------------------------------------------------------------------
    | Default map position
    |--------------------------------------------------------------------------
    |
    | Tunis is used only as the initial map view when a new pickup point
    | does not yet have coordinates.
    |
    */

    let initialLatitude = 36.8065;
    let initialLongitude = 10.1815;

    let initialZoom = 11;


    /*
    |--------------------------------------------------------------------------
    | Existing coordinates
    |--------------------------------------------------------------------------
    |
    | When editing a pickup point, show its saved location.
    | Also works when Laravel redirects back after validation.
    |
    */

    const savedLatitude =
        parseFloat(latitudeInput.value);

    const savedLongitude =
        parseFloat(longitudeInput.value);

    const hasSavedLocation =
        !Number.isNaN(savedLatitude) &&
        !Number.isNaN(savedLongitude);


    if (hasSavedLocation) {

        initialLatitude = savedLatitude;
        initialLongitude = savedLongitude;
        initialZoom = 15;

    }


    /*
    |--------------------------------------------------------------------------
    | Create map
    |--------------------------------------------------------------------------
    */

    const map = L.map('pickup-map').setView(
        [initialLatitude, initialLongitude],
        initialZoom
    );


    /*
    |--------------------------------------------------------------------------
    | OpenStreetMap layer
    |--------------------------------------------------------------------------
    */

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,

            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    /*
    |--------------------------------------------------------------------------
    | Marker
    |--------------------------------------------------------------------------
    */

    let marker = null;


    /*
    |--------------------------------------------------------------------------
    | Update selected location
    |--------------------------------------------------------------------------
    */

    function setLocation(latitude, longitude) {

        const lat =
            parseFloat(latitude).toFixed(7);

        const lng =
            parseFloat(longitude).toFixed(7);


        latitudeInput.value = lat;
        longitudeInput.value = lng;


        latitudeDisplay.textContent = lat;
        longitudeDisplay.textContent = lng;


        coordinatesEmpty.style.display = 'none';
        coordinatesSelected.style.display = 'block';


        if (marker === null) {

            marker = L.marker(
                [latitude, longitude],
                {
                    draggable: true
                }
            ).addTo(map);


            /*
            |--------------------------------------------------------------------------
            | Drag marker
            |--------------------------------------------------------------------------
            */

            marker.on('dragend', function () {

                const position =
                    marker.getLatLng();

                setLocation(
                    position.lat,
                    position.lng
                );

            });

        } else {

            marker.setLatLng([
                latitude,
                longitude
            ]);

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Existing marker
    |--------------------------------------------------------------------------
    */

    if (hasSavedLocation) {

        setLocation(
            savedLatitude,
            savedLongitude
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Click map
    |--------------------------------------------------------------------------
    */

    map.on('click', function (event) {

        setLocation(
            event.latlng.lat,
            event.latlng.lng
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Search location
    |--------------------------------------------------------------------------
    */

    async function searchLocation() {

        let query =
            searchInput.value.trim();


        /*
        | If map search is empty, use the address and city fields.
        */

        if (!query) {

            const address =
                addressInput.value.trim();

            const city =
                cityInput.value.trim();

            query = [
                address,
                city,
                'Tunisia'
            ]
                .filter(Boolean)
                .join(', ');

        }


        if (!query) {

            showSearchMessage(
                'Veuillez saisir une adresse à rechercher.',
                'danger'
            );

            return;

        }


        searchButton.disabled = true;

        searchButton.innerHTML =
            '<i class="fa fa-spinner fa-spin me-2"></i>Recherche...';


        try {

            /*
            |--------------------------------------------------------------------------
            | OpenStreetMap Nominatim geocoding
            |--------------------------------------------------------------------------
            */

            const url =
                'https://nominatim.openstreetmap.org/search' +
                '?format=json' +
                '&limit=1' +
                '&countrycodes=tn' +
                '&q=' +
                encodeURIComponent(query);


            const response =
                await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });


            if (!response.ok) {
                throw new Error('Search failed');
            }


            const results =
                await response.json();


            if (results.length === 0) {

                showSearchMessage(
                    'Aucun emplacement trouvé. Essayez une adresse plus précise.',
                    'warning'
                );

                return;

            }


            const result =
                results[0];

            const latitude =
                parseFloat(result.lat);

            const longitude =
                parseFloat(result.lon);


            /*
            |--------------------------------------------------------------------------
            | Move map
            |--------------------------------------------------------------------------
            */

            map.setView(
                [latitude, longitude],
                16
            );


            setLocation(
                latitude,
                longitude
            );


            showSearchMessage(
                'Emplacement trouvé : ' +
                result.display_name,
                'success'
            );

        } catch (error) {

            showSearchMessage(
                'Impossible de rechercher l’adresse pour le moment. Vous pouvez sélectionner manuellement la position sur la carte.',
                'danger'
            );

        } finally {

            searchButton.disabled = false;

            searchButton.innerHTML =
                '<i class="fa fa-search me-2"></i>Rechercher';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Search button
    |--------------------------------------------------------------------------
    */

    searchButton.addEventListener(
        'click',
        searchLocation
    );


    /*
    |--------------------------------------------------------------------------
    | Press Enter in search
    |--------------------------------------------------------------------------
    */

    searchInput.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Enter') {

                event.preventDefault();

                searchLocation();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Search message
    |--------------------------------------------------------------------------
    */

    function showSearchMessage(message, type) {

        searchMessage.className =
            'alert alert-' +
            type +
            ' location-result';

        searchMessage.textContent =
            message;

        searchMessage.style.display =
            'block';

    }


    /*
    |--------------------------------------------------------------------------
    | Leaflet rendering fix
    |--------------------------------------------------------------------------
    */

    setTimeout(function () {
        map.invalidateSize();
    }, 200);

});
</script>

@endsection