@extends('layouts.back')

@section('title', 'Détail du point de retrait')

@section('content')

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
            POINT DE RETRAIT
        </span>

        <h2>
            {{ $pickupPoint->name }}
        </h2>

        <p>
            Informations détaillées du point de retrait.
        </p>

    </div>


    <a
        href="{{ route('admin.pickup-points.edit', $pickupPoint) }}"
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
                Nom
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->name }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Statut
            </small>

            <div class="mt-1">

                @if($pickupPoint->status === 'active')

                    <span class="badge bg-success">
                        Actif
                    </span>

                @else

                    <span class="badge bg-secondary">
                        Inactif
                    </span>

                @endif

            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Ville
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->city }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Adresse
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->address }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Horaires d'ouverture
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->opening_hours }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Capacité
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->capacity }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Latitude
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->latitude ?? 'Non renseignée' }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Longitude
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->longitude ?? 'Non renseignée' }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Livraisons associées
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->deliveries_count }}
            </div>

        </div>


        <div class="col-md-6">

            <small class="text-muted">
                Créé le
            </small>

            <div class="fw-semibold mt-1">
                {{ $pickupPoint->created_at->format('d/m/Y H:i') }}
            </div>

        </div>

    </div>

</div>


<div class="d-flex justify-content-end mt-4">

    <form
        method="POST"
        action="{{ route('admin.pickup-points.destroy', $pickupPoint) }}"
        onsubmit="return confirm('Supprimer ce point de retrait définitivement ?')"
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