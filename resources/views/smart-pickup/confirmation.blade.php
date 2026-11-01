@extends($isAdmin ? 'layouts.back' : 'layouts.front')

@section('title', 'Confirmer le retrait SmartPickup')

@section('content')
<div class="container{{ $isAdmin ? '-fluid' : '' }} py-4" style="max-width:900px">
    <a
        href="{{ $isAdmin ? route('admin.deliveries.smart-pickup', ['rental_id' => $rental->id, 'date' => $selection['date']]) : route('rentals.smart-pickup', ['rental' => $rental, 'date' => $selection['date']]) }}"
        class="btn btn-link px-0 mb-3 text-decoration-none"
        style="color:#278658"
    >
        <i class="fa fa-arrow-left me-1"></i>Retour aux recommandations
    </a>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4">
        <span class="text-uppercase small fw-bold" style="letter-spacing:.12em;color:#278658">Confirmation</span>
        <h1 class="h2">Vérifiez votre rendez-vous</h1>
        <p class="text-muted mb-0">Le créneau sera revalidé avant l’enregistrement afin d’éviter toute réservation en conflit.</p>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <small class="text-muted d-block">Location / équipement</small>
                    <strong>#{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }} — {{ $rental->equipment?->title ?? 'Équipement' }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Point de retrait</small>
                    <strong>{{ $recommendation['name'] }}</strong>
                    <div class="small text-muted">{{ $recommendation['address'] }}, {{ $recommendation['city'] }}</div>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Date et heure</small>
                    <strong>{{ \Carbon\Carbon::parse($selection['date'])->translatedFormat('l d F Y') }} à {{ $selectedTime }}</strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Frais de livraison</small>
                    <strong>{{ number_format((float) $deliveryFee, 2, ',', ' ') }} TND</strong>
                    <div class="small text-muted">Aucun frais SmartPickup supplémentaire n’est appliqué.</div>
                </div>
                <div class="col-12">
                    <small class="text-muted d-block">Pourquoi ce point est recommandé ?</small>
                    <ul class="mb-0 mt-1">
                        @foreach($recommendation['explanation'] as $reason)<li>{{ $reason }}</li>@endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ $bookRoute }}">
        @csrf
        @foreach($selection as $field => $value)
            @if($value !== null && $value !== '')
                <input type="hidden" name="{{ $field }}" value="{{ $value }}">
            @endif
        @endforeach
        <input type="hidden" name="confirmed" value="1">
        <div class="d-flex justify-content-end gap-2 flex-wrap">
            <a
                class="btn btn-outline-secondary"
                href="{{ $isAdmin ? route('admin.deliveries.smart-pickup', ['rental_id' => $rental->id]) : route('rentals.smart-pickup', $rental) }}"
            >Annuler</a>
            <button type="submit" class="btn text-white px-4" style="background:#278658">
                <i class="fa fa-check me-2"></i>Confirmer le retrait
            </button>
        </div>
    </form>
</div>
@endsection
