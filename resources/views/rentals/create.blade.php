@extends('layouts.front')
@section('title', 'Réserver – '.$equipment->title)

@section('content')
<div class="container py-5">
    <a href="{{ route('equipments.show', $equipment) }}" class="d-inline-block mb-4 text-decoration-none" style="color:#278658">
        ← Retour à l'équipement
    </a>

    {{-- AI conflict alert --}}
    @if(session('availability_conflict'))
    <div class="alert" role="alert" style="background:#fff3cd;border:1px solid #ffc107;border-radius:12px;padding:20px 24px;margin-bottom:24px">
        <h5 style="color:#856404"><i class="fa fa-exclamation-triangle me-2"></i>Cet équipement est déjà réservé pour les dates choisies</h5>

        @if(session('next_date'))
        <p class="mb-2">
            <strong>Prochaine disponibilité :</strong>
            Le même équipement est libre à partir du
            <strong>{{ \Carbon\Carbon::parse(session('next_date'))->translatedFormat('d F Y') }}</strong>.
            <a href="{{ route('rentals.create', $equipment) }}?start={{ session('next_date') }}" class="ms-2 btn btn-sm" style="background:#278658;color:white">
                Réserver à cette date
            </a>
        </p>
        @endif

        @if(session('alternatives') && session('alternatives')->isNotEmpty())
        <p class="mb-2"><strong>Équipements similaires disponibles pour vos dates :</strong></p>
        <div class="row g-3">
            @foreach(session('alternatives') as $alt)
            <div class="col-md-4">
                <div class="card h-100 border-0" style="border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08)">
                    @if(!empty($alt->photos[0]))
                    <img src="{{ asset($alt->photos[0]) }}" class="card-img-top" alt="{{ $alt->title }}" style="height:140px;object-fit:cover;border-radius:12px 12px 0 0">
                    @endif
                    <div class="card-body">
                        <h6 class="card-title mb-1">{{ $alt->title }}</h6>
                        <small class="text-muted">{{ $alt->category->name }}</small>
                        <div class="mt-2">
                            <strong style="color:#278658">{{ number_format($alt->price_per_day, 2, ',', ' ') }} €</strong>
                            <span class="text-muted"> / jour</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('rentals.create', $alt) }}" class="btn btn-sm w-100" style="background:#278658;color:white">
                            Réserver cet équipement
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <div class="row g-4 align-items-start">

        {{-- Equipment recap --}}
        <div class="col-lg-4">
            <div class="card border-0" style="border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
                @if(!empty($equipment->photos[0]))
                <img src="{{ asset($equipment->photos[0]) }}" alt="{{ $equipment->title }}" style="height:200px;object-fit:cover;width:100%">
                @endif
                <div class="p-4">
                    <span class="badge mb-2" style="background:#e8f5e9;color:#278658">{{ $equipment->category->name }}</span>
                    <h4>{{ $equipment->title }}</h4>
                    <p class="text-muted small mb-3">{{ Str::limit($equipment->description, 120) }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong style="font-size:1.3rem;color:#278658">{{ number_format($equipment->price_per_day, 2, ',', ' ') }} €</strong>
                            <span class="text-muted"> / jour</span>
                        </div>
                        <span class="text-muted small">Caution : {{ number_format($equipment->deposit, 2, ',', ' ') }} €</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Booking form --}}
        <div class="col-lg-8">
            <div class="card border-0" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08)">
                <div class="card-body p-4">
                    <h3 class="mb-1">Réserver cet équipement</h3>
                    <p class="text-muted mb-4">Choisissez vos dates. Votre demande sera examinée par l'administrateur.</p>

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('rentals.store', $equipment) }}" id="booking-form">
                        @csrf

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold" for="start_date">Date de début</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="start_date"
                                    name="start_date"
                                    value="{{ old('start_date', request('start', '')) }}"
                                    min="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold" for="end_date">Date de fin</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="end_date"
                                    name="end_date"
                                    value="{{ old('end_date', '') }}"
                                    min="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                        </div>

                        {{-- Live price estimate --}}
                        <div id="price-estimate" class="mb-3 p-3" style="background:#f0f9f4;border-radius:10px;display:none">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Durée</span>
                                <span id="est-days">–</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Location (<span id="est-rate">{{ number_format($equipment->price_per_day, 2, ',', ' ') }} € / j</span>)</span>
                                <strong id="est-total">–</strong>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted">Caution (à la remise)</span>
                                <span>{{ number_format($equipment->deposit, 2, ',', ' ') }} €</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="notes">Notes (optionnel)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Précisez vos besoins, votre adresse de livraison, etc.">{{ old('notes') }}</textarea>
                        </div>

                        {{-- Booked ranges for JS calendar hint --}}
                        <script>
                            const bookedRanges = @json($bookedRanges);
                            const pricePerDay  = {{ $equipment->price_per_day }};
                        </script>

                        <button type="submit" class="btn w-100 py-3 fw-bold" style="background:#278658;color:white;border-radius:10px;font-size:1rem">
                            <i class="fa fa-paper-plane me-2"></i>Envoyer ma demande de réservation
                        </button>
                        <p class="text-muted text-center mt-2 small">Aucun paiement n'est débité avant validation.</p>
                    </form>
                </div>
            </div>

            {{-- Booked dates info --}}
            @if(!empty($bookedRanges))
            <div class="mt-3 p-3" style="background:#fff8f0;border:1px solid #ffe0b2;border-radius:12px">
                <h6 class="mb-2"><i class="fa fa-calendar-times me-2" style="color:#e65100"></i>Dates déjà réservées</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($bookedRanges as $range)
                    <span class="badge" style="background:#fbe9e7;color:#bf360c;font-size:.8rem">
                        {{ \Carbon\Carbon::parse($range['start'])->format('d/m/Y') }}
                        →
                        {{ \Carbon\Carbon::parse($range['end'])->format('d/m/Y') }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
// Live price estimator
const startInput = document.getElementById('start_date');
const endInput   = document.getElementById('end_date');
const estimate   = document.getElementById('price-estimate');
const estDays    = document.getElementById('est-days');
const estTotal   = document.getElementById('est-total');

function updateEstimate() {
    if (!startInput.value || !endInput.value) { estimate.style.display = 'none'; return; }
    const s = new Date(startInput.value);
    const e = new Date(endInput.value);
    if (e < s) { estimate.style.display = 'none'; return; }
    const days  = Math.round((e - s) / 86400000) + 1;
    const total = (days * pricePerDay).toFixed(2).replace('.', ',');
    estDays.textContent  = days + ' jour' + (days > 1 ? 's' : '');
    estTotal.textContent = total + ' €';
    estimate.style.display = 'block';
    // Also push min date on end field
    endInput.min = startInput.value;
}

startInput.addEventListener('change', updateEstimate);
endInput.addEventListener('change', updateEstimate);
updateEstimate();
</script>
@endsection
