@extends('layouts.back')

@section(
    'title',
    $delivery->exists
        ? 'Modifier la livraison'
        : 'Nouvelle livraison'
)

@section('content')

<style>
    .field-error {
        display: block;
        color: #dc3545;
        font-size: 13px;
        margin-top: 6px;
    }

    .delivery-type-card {
        border: 1px solid #dce4dc;
        border-radius: 12px;
        padding: 18px;
        cursor: pointer;
        height: 100%;
        transition: 0.2s;
    }

    .delivery-type-card:hover {
        border-color: #278658;
    }

    .delivery-type-card.active {
        border-color: #278658;
        background: #f4faf6;
    }

    .delivery-type-icon {
        font-size: 24px;
        margin-bottom: 8px;
        color: #278658;
    }
</style>


<a
    href="{{ route('admin.deliveries.index') }}"
    class="d-inline-block mb-4 text-primary text-decoration-none"
>
    <i class="fa fa-arrow-left me-2"></i>
    Retour aux livraisons
</a>


<div class="catalog-intro">

    <div>

        <span class="section-kicker">
            GESTION DES LIVRAISONS
        </span>

        <h2>
            {{ $delivery->exists
                ? 'Modifier la livraison'
                : 'Planifier une livraison'
            }}
        </h2>

        <p>
            Définissez le mode, la date et le lieu
            de livraison de la location.
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
    action="{{ $delivery->exists
        ? route('admin.deliveries.update', $delivery)
        : route('admin.deliveries.store')
    }}"
    novalidate
>

    @csrf

    @if($delivery->exists)
        @method('PUT')
    @endif


    <div class="row g-4">


        {{-- RENTAL --}}

        <div class="col-12">

            <label class="form-label fw-semibold">
                Location *
            </label>

            <select
                name="rental_id"
                class="form-select @error('rental_id') is-invalid @enderror"
            >

                <option value="">
                    -- Sélectionner une location --
                </option>


                @foreach($rentals as $rental)

                    <option
                        value="{{ $rental->id }}"
                        @selected(
                            old(
                                'rental_id',
                                $delivery->rental_id
                            ) == $rental->id
                        )
                    >

                        #{{ str_pad(
                            $rental->id,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ) }}

                        —

                        {{ $rental->equipment?->title
                            ?? 'Équipement'
                        }}

                        —

                        {{ $rental->renter?->name
                            ?? 'Client'
                        }}

                    </option>

                @endforeach

            </select>


            @error('rental_id')

                <div class="field-error">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    {{ $message }}
                </div>

            @enderror


            @if($rentals->isEmpty())

                <div class="alert alert-warning mt-3 mb-0">

                    <i class="fa fa-exclamation-triangle me-2"></i>

                    Aucune location n'existe actuellement.

                    Vous devez créer une location avant
                    de pouvoir planifier une livraison.

                </div>

            @endif

        </div>


        {{-- TYPE --}}

        <div class="col-12">

            <label class="form-label fw-semibold mb-3">
                Type de livraison *
            </label>


            <div class="row g-3">


                <div class="col-md-6">

                    <div
                        class="delivery-type-card"
                        id="pickup-card"
                        onclick="selectDeliveryType('pickup')"
                    >

                        <div class="delivery-type-icon">
                            <i class="fa fa-map-marker-alt"></i>
                        </div>

                        <strong>
                            Retrait en point
                        </strong>

                        <div class="text-muted mt-1">
                            Le client récupère l'équipement
                            dans un point SolarShare.
                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div
                        class="delivery-type-card"
                        id="home-card"
                        onclick="selectDeliveryType('home_delivery')"
                    >

                        <div class="delivery-type-icon">
                            <i class="fa fa-truck"></i>
                        </div>

                        <strong>
                            Livraison à domicile
                        </strong>

                        <div class="text-muted mt-1">
                            L'équipement est livré directement
                            au client.
                        </div>

                    </div>

                </div>

            </div>


            <input
                type="hidden"
                name="type"
                id="delivery-type"
                value="{{ old('type', $delivery->type) }}"
            >


            @error('type')

                <div class="field-error">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    {{ $message }}
                </div>

            @enderror

        </div>


        {{-- PICKUP POINT --}}

        <div
            class="col-12"
            id="pickup-point-container"
            style="display:none"
        >

            <label class="form-label fw-semibold">
                Point de retrait *
            </label>


            <select
                name="pickup_point_id"
                id="pickup-point"
                class="form-select @error('pickup_point_id') is-invalid @enderror"
            >

                <option value="">
                    -- Sélectionner un point de retrait --
                </option>


                @foreach($pickupPoints as $point)

                    <option
                        value="{{ $point->id }}"
                        @selected(
                            old(
                                'pickup_point_id',
                                $delivery->pickup_point_id
                            ) == $point->id
                        )
                    >

                        {{ $point->name }}

                        —

                        {{ $point->city }}

                        —

                        Capacité : {{ $point->capacity }}

                    </option>

                @endforeach

            </select>


            @error('pickup_point_id')

                <div class="field-error">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    {{ $message }}
                </div>

            @enderror

        </div>


        {{-- DATE --}}

        <div class="col-md-6">

            <label class="form-label fw-semibold">
                Date prévue *
            </label>

            <input
                type="date"
                name="scheduled_date"
                class="form-control @error('scheduled_date') is-invalid @enderror"
                value="{{ old(
                    'scheduled_date',
                    $delivery->scheduled_date
                        ? $delivery->scheduled_date->format('Y-m-d')
                        : ''
                ) }}"
            >

            @error('scheduled_date')

                <div class="field-error">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    {{ $message }}
                </div>

            @enderror

        </div>


        {{-- TIME --}}

        <div class="col-md-6">

            <label class="form-label fw-semibold">
                Heure prévue *
            </label>

            <input
                type="time"
                name="scheduled_time"
                class="form-control @error('scheduled_time') is-invalid @enderror"
                value="{{ old(
                    'scheduled_time',
                    $delivery->scheduled_time
                        ? substr($delivery->scheduled_time, 0, 5)
                        : ''
                ) }}"
            >

            @error('scheduled_time')

                <div class="field-error">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    {{ $message }}
                </div>

            @enderror

        </div>


        {{-- DELIVERY FEE --}}

        <div class="col-md-6">

            <label class="form-label fw-semibold">
                Frais de livraison (TND) *
            </label>

            <input
                type="number"
                step="0.01"
                name="delivery_fee"
                class="form-control @error('delivery_fee') is-invalid @enderror"
                value="{{ old(
                    'delivery_fee',
                    $delivery->delivery_fee ?? 0
                ) }}"
                placeholder="0.00"
            >

            @error('delivery_fee')

                <div class="field-error">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    {{ $message }}
                </div>

            @enderror

        </div>


        {{-- STATUS --}}

        <div class="col-md-6">

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
                    value="planned"
                    @selected(
                        old(
                            'status',
                            $delivery->status ?? 'planned'
                        ) === 'planned'
                    )
                >
                    Planifiée
                </option>

                <option
                    value="in_transit"
                    @selected(
                        old(
                            'status',
                            $delivery->status
                        ) === 'in_transit'
                    )
                >
                    En transit
                </option>

                <option
                    value="delivered"
                    @selected(
                        old(
                            'status',
                            $delivery->status
                        ) === 'delivered'
                    )
                >
                    Livrée
                </option>

                <option
                    value="returned"
                    @selected(
                        old(
                            'status',
                            $delivery->status
                        ) === 'returned'
                    )
                >
                    Retournée
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


    <div class="d-flex justify-content-end gap-2">

        <a
            href="{{ route('admin.deliveries.index') }}"
            class="btn btn-outline-secondary"
        >
            Annuler
        </a>


        <button
            type="submit"
            class="btn btn-primary"
            @if($rentals->isEmpty()) disabled @endif
        >

            <i class="fa fa-save me-2"></i>

            {{ $delivery->exists
                ? 'Enregistrer les modifications'
                : 'Ajouter la livraison'
            }}

        </button>

    </div>

</form>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const currentType =
            document.getElementById('delivery-type').value;

        if (currentType) {
            selectDeliveryType(currentType);
        }

    }
);


function selectDeliveryType(type)
{
    const typeInput =
        document.getElementById('delivery-type');

    const pickupContainer =
        document.getElementById('pickup-point-container');

    const pickupCard =
        document.getElementById('pickup-card');

    const homeCard =
        document.getElementById('home-card');


    typeInput.value = type;


    pickupCard.classList.remove('active');
    homeCard.classList.remove('active');


    if (type === 'pickup') {

        pickupCard.classList.add('active');

        pickupContainer.style.display =
            'block';

    } else {

        homeCard.classList.add('active');

        pickupContainer.style.display =
            'none';

        document.getElementById(
            'pickup-point'
        ).value = '';

    }
}

</script>

@endsection