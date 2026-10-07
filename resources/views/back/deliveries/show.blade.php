@extends('layouts.back')

@section('title', 'Détail de la livraison')

@section('content')

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
            LIVRAISON
        </span>

        <h2>
            Livraison
            #{{ str_pad(
                $delivery->id,
                4,
                '0',
                STR_PAD_LEFT
            ) }}
        </h2>

        <p>
            Informations détaillées de la livraison.
        </p>

    </div>


    <a
        href="{{ route('admin.deliveries.edit', $delivery) }}"
        class="btn btn-primary add-button"
    >
        <i class="far fa-edit me-2"></i>
        Modifier
    </a>

</div>


<div class="surface p-4">

    <div class="row g-4">


        <div class="col-md-6">

            <small class="text-muted">
                Location
            </small>

            <div class="fw-semibold mt-1">

                #{{ str_pad(
                    $delivery->rental_id,
                    4,
                    '0',
                    STR_PAD_LEFT
                ) }}

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Équipement
            </small>

            <div class="fw-semibold mt-1">

                {{ $delivery->rental?->equipment?->title
                    ?? 'Indisponible'
                }}

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Client
            </small>

            <div class="fw-semibold mt-1">

                {{ $delivery->rental?->renter?->name
                    ?? 'Indisponible'
                }}

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Type
            </small>

            <div class="mt-1">

                @if($delivery->type === 'pickup')

                    <span class="badge bg-success">
                        Retrait en point
                    </span>

                @else

                    <span class="badge bg-primary">
                        Livraison à domicile
                    </span>

                @endif

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Date prévue
            </small>

            <div class="fw-semibold mt-1">

                {{ $delivery->scheduled_date->format('d/m/Y') }}

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Heure prévue
            </small>

            <div class="fw-semibold mt-1">

                {{ substr(
                    $delivery->scheduled_time,
                    0,
                    5
                ) }}

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Point de retrait
            </small>

            <div class="fw-semibold mt-1">

                @if($delivery->pickupPoint)

                    {{ $delivery->pickupPoint->name }}

                    <br>

                    <small class="text-muted">
                        {{ $delivery->pickupPoint->address }},
                        {{ $delivery->pickupPoint->city }}
                    </small>

                @else

                    Livraison à domicile

                @endif

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Frais
            </small>

            <div
                class="fw-semibold mt-1"
                style="color:#278658"
            >

                {{ number_format(
                    $delivery->delivery_fee,
                    2,
                    ',',
                    ' '
                ) }}

                TND

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Statut
            </small>

            <div class="mt-1">

                @switch($delivery->status)

                    @case('planned')
                        <span class="badge bg-secondary">
                            Planifiée
                        </span>
                        @break

                    @case('in_transit')
                        <span class="badge bg-primary">
                            En transit
                        </span>
                        @break

                    @case('delivered')
                        <span class="badge bg-success">
                            Livrée
                        </span>
                        @break

                    @case('returned')
                        <span class="badge bg-dark">
                            Retournée
                        </span>
                        @break

                @endswitch

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Créée le
            </small>

            <div class="fw-semibold mt-1">

                {{ $delivery->created_at->format(
                    'd/m/Y H:i'
                ) }}

            </div>

        </div>

    </div>

</div>


<div class="d-flex justify-content-end mt-4">

    <form
        method="POST"
        action="{{ route('admin.deliveries.destroy', $delivery) }}"
        onsubmit="return confirm('Supprimer cette livraison définitivement ?')"
    >

        @csrf
        @method('DELETE')

        <button
            type="submit"
            class="btn btn-outline-danger"
        >
            <i class="far fa-trash-alt me-2"></i>
            Supprimer
        </button>

    </form>

</div>

@endsection