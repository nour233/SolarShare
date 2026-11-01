@extends('layouts.back')

@section('title', 'Détail de la livraison')

@section('content')

<style>
    .tracking-timeline {
        position: relative;
        margin-left: 10px;
        padding-left: 28px;
        border-left: 2px solid #dce4dc;
    }

    .tracking-event {
        position: relative;
        padding-bottom: 24px;
    }

    .tracking-event:last-child {
        padding-bottom: 0;
    }

    .tracking-event::before {
        position: absolute;
        top: 3px;
        left: -36px;
        width: 14px;
        height: 14px;
        border: 3px solid #278658;
        border-radius: 50%;
        background: #fff;
        content: "";
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

@if(session('success'))
    <div class="alert alert-success mt-4">
        <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mt-4">
        <strong>Le changement de statut n’a pas été effectué.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


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
                <span class="badge {{ \App\Models\Delivery::statusBadgeClass($delivery->status) }}">
                    {{ \App\Models\Delivery::statusLabelFor($delivery->status) }}
                </span>
            </div>

        </div>

        <div class="col-md-6">
            <small class="text-muted">Dernière mise à jour du statut</small>
            <div class="fw-semibold mt-1">{{ $lastStatusUpdate->format('d/m/Y H:i') }}</div>
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

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <div class="surface p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <span class="section-kicker">SUIVI</span>
                    <h3 class="h5 mb-1">Historique de la livraison</h3>
                    <p class="text-muted mb-0">Événements enregistrés dans l’ordre chronologique.</p>
                </div>
                <span class="badge bg-light text-dark">
                    {{ $delivery->statusHistories->count() }} mise(s) à jour
                </span>
            </div>

            <div class="tracking-timeline">
                <div class="tracking-event">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <strong>Livraison créée</strong>
                        <time class="small text-muted">{{ $delivery->created_at->format('d/m/Y H:i') }}</time>
                    </div>
                    <div class="small text-muted mt-1">
                        Planification initiale enregistrée.
                        @if($delivery->statusHistories->isEmpty())
                            Les livraisons antérieures au suivi conservent leur état actuel sans historique rétroactif.
                        @endif
                    </div>
                </div>

                @foreach($delivery->statusHistories as $history)
                    <div class="tracking-event">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <strong>
                                {{ \App\Models\Delivery::statusLabelFor($history->previous_status) }}
                                <i class="fa fa-arrow-right mx-1 text-muted"></i>
                                {{ \App\Models\Delivery::statusLabelFor($history->new_status) }}
                            </strong>
                            <time class="small text-muted">{{ $history->created_at->format('d/m/Y H:i') }}</time>
                        </div>
                        <div class="small text-muted mt-1">
                            Par {{ $history->user?->name ?? 'Utilisateur supprimé ou non renseigné' }}
                        </div>
                        @if($history->comment)
                            <div class="mt-2 p-2 rounded bg-light">{{ $history->comment }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="surface p-4 h-100">
            <span class="section-kicker">ACTIONS ADMIN</span>
            <h3 class="h5 mb-3">Mettre à jour le statut</h3>

            @if($allowedNextStatuses)
                @foreach($allowedNextStatuses as $nextStatus)
                    <form
                        method="POST"
                        action="{{ route('admin.deliveries.status', $delivery) }}"
                        class="mb-3"
                        onsubmit="return confirm('Confirmer le passage au statut « {{ \App\Models\Delivery::statusLabelFor($nextStatus) }} » ?')"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $nextStatus }}">

                        <label class="form-label small fw-semibold" for="comment-{{ $nextStatus }}">
                            Note (facultative)
                        </label>
                        <textarea
                            id="comment-{{ $nextStatus }}"
                            name="comment"
                            class="form-control mb-2"
                            rows="2"
                            maxlength="1000"
                            placeholder="Ajouter un commentaire au suivi"
                        >{{ old('comment') }}</textarea>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-arrow-right me-2"></i>
                            Passer à « {{ \App\Models\Delivery::statusLabelFor($nextStatus) }} »
                        </button>
                    </form>
                @endforeach
            @else
                <div class="alert alert-light border mb-0">
                    Cette livraison a atteint son statut final.
                </div>
            @endif
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