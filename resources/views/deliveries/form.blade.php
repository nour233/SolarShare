@extends('layouts.front')

@section(
    'title',
    isset($delivery) ? 'Modifier la livraison' : 'Choisir la livraison'
)

@section('content')
<div class="container py-5" style="max-width:760px">

    <a
        href="{{ route('rentals.show', $rental) }}"
        class="d-inline-block mb-4 text-decoration-none"
        style="color:#278658"
    >
        ← Retour à ma réservation
    </a>

    <div class="mb-4">
        <h2 class="mb-1">
            {{ isset($delivery) ? 'Modifier la livraison' : 'Choisir la livraison' }}
        </h2>

        <p class="text-muted mb-0">
            Réservation #{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}
            — {{ $rental->equipment->title }}
        </p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Veuillez corriger les erreurs suivantes :</strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div
        class="card border-0"
        style="border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.08)"
    >
        <div class="card-body p-4">

            <form
                method="POST"
                action="{{
                    isset($delivery)
                        ? route('deliveries.update', $delivery)
                        : route('deliveries.store', $rental)
                }}"
                novalidate
            >
                @csrf

                @if(isset($delivery))
                    @method('PUT')
                @endif

                {{-- Type --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Mode de livraison
                    </label>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label
                                class="delivery-option w-100 p-3"
                                for="typePickup"
                            >
                                <input
                                    class="form-check-input me-2"
                                    type="radio"
                                    name="type"
                                    id="typePickup"
                                    value="pickup"
                                    {{
                                        old(
                                            'type',
                                            $delivery->type ?? 'pickup'
                                        ) === 'pickup'
                                            ? 'checked'
                                            : ''
                                    }}
                                >

                                <strong>
                                    <i class="fa fa-map-marker-alt me-2"></i>
                                    Retrait en point
                                </strong>

                                <small class="d-block text-muted mt-2">
                                    Récupérez votre équipement dans un
                                    point SolarShare.
                                </small>
                            </label>
                        </div>

                        <div class="col-md-6">
                            <label
                                class="delivery-option w-100 p-3"
                                for="typeHome"
                            >
                                <input
                                    class="form-check-input me-2"
                                    type="radio"
                                    name="type"
                                    id="typeHome"
                                    value="home_delivery"
                                    {{
                                        old(
                                            'type',
                                            $delivery->type ?? ''
                                        ) === 'home_delivery'
                                            ? 'checked'
                                            : ''
                                    }}
                                >

                                <strong>
                                    <i class="fa fa-truck me-2"></i>
                                    Livraison à domicile
                                </strong>

                                <small class="d-block text-muted mt-2">
                                    Livraison de l'équipement directement
                                    à domicile.
                                </small>
                            </label>
                        </div>

                    </div>

                    @error('type')
                        <div class="text-danger small mt-2">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Pickup point --}}
                <div
                    class="mb-4"
                    id="pickupPointSection"
                >
                    <label
                        for="pickup_point_id"
                        class="form-label fw-bold"
                    >
                        Point de retrait
                    </label>

                    <select
                        name="pickup_point_id"
                        id="pickup_point_id"
                        class="form-select @error('pickup_point_id') is-invalid @enderror"
                    >
                        <option value="">
                            -- Choisir un point de retrait --
                        </option>

                        @foreach($pickupPoints as $point)
                            <option
                                value="{{ $point->id }}"
                                {{
                                    (string) old(
                                        'pickup_point_id',
                                        $delivery->pickup_point_id ?? ''
                                    ) === (string) $point->id
                                        ? 'selected'
                                        : ''
                                }}
                            >
                                {{ $point->name }}
                                @if($point->city)
                                    — {{ $point->city }}
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('pickup_point_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Seuls les points de retrait actifs sont affichés.
                    </small>
                </div>

                <div class="row g-3">

                    {{-- Date --}}
                    <div class="col-md-6">
                        <label
                            for="scheduled_date"
                            class="form-label fw-bold"
                        >
                            Date
                        </label>

                        <input
                            type="date"
                            name="scheduled_date"
                            id="scheduled_date"
                            class="form-control @error('scheduled_date') is-invalid @enderror"
                            value="{{
                                old(
                                    'scheduled_date',
                                    isset($delivery) && $delivery->scheduled_date
                                        ? $delivery->scheduled_date->format('Y-m-d')
                                        : ''
                                )
                            }}"
                        >

                        @error('scheduled_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Time --}}
                    <div class="col-md-6">
                        <label
                            for="scheduled_time"
                            class="form-label fw-bold"
                        >
                            Heure
                        </label>

                        <input
                            type="time"
                            name="scheduled_time"
                            id="scheduled_time"
                            class="form-control @error('scheduled_time') is-invalid @enderror"
                            value="{{
                                old(
                                    'scheduled_time',
                                    isset($delivery)
                                        ? substr($delivery->scheduled_time, 0, 5)
                                        : ''
                                )
                            }}"
                        >

                        @error('scheduled_time')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </div>

                <div
                    class="mt-4 p-3"
                    style="background:#f0f9f4;border-radius:12px"
                >
                    <div class="d-flex gap-3 align-items-center">
                        <i
                            class="fa fa-solar-panel"
                            style="font-size:1.7rem;color:#278658"
                        ></i>

                        <div>
                            <strong>{{ $rental->equipment->title }}</strong>

                            <small class="text-muted d-block">
                                Location du
                                {{ $rental->start_date->format('d/m/Y') }}
                                au
                                {{ $rental->end_date->format('d/m/Y') }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">

                    <button
                        type="submit"
                        class="btn"
                        style="background:#278658;color:white"
                    >
                        <i class="fa fa-check me-2"></i>

                        {{
                            isset($delivery)
                                ? 'Enregistrer les modifications'
                                : 'Confirmer la livraison'
                        }}
                    </button>

                    <a
                        href="{{ route('rentals.show', $rental) }}"
                        class="btn btn-outline-secondary"
                    >
                        Annuler
                    </a>

                </div>

            </form>

        </div>
    </div>
</div>

<style>
.delivery-option {
    display: block;
    border: 2px solid #e5ebe3;
    border-radius: 14px;
    cursor: pointer;
    transition: .2s;
}

.delivery-option:hover {
    border-color: #278658;
    background: #f7fcf9;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pickup = document.getElementById('typePickup');
    const home = document.getElementById('typeHome');
    const section = document.getElementById('pickupPointSection');

    function updatePickupPoint() {
        if (home.checked) {
            section.style.display = 'none';
        } else {
            section.style.display = 'block';
        }
    }

    pickup.addEventListener('change', updatePickupPoint);
    home.addEventListener('change', updatePickupPoint);

    updatePickupPoint();
});
</script>
@endsection