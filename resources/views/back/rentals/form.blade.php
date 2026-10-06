@extends('layouts.back')
@section('title', $rental->exists ? 'Modifier la location #'.str_pad($rental->id,4,'0',STR_PAD_LEFT) : 'Nouvelle location')

@section('content')
<a href="{{ route('admin.rentals.index') }}" class="d-inline-block mb-4 text-primary">← Retour aux locations</a>

<div class="surface" style="max-width:760px">
    <h3 class="mb-4">{{ $rental->exists ? 'Modifier la location' : 'Créer une location manuellement' }}</h3>

    <form method="POST"
          action="{{ $rental->exists ? route('admin.rentals.update', $rental) : route('admin.rentals.store') }}">
        @csrf
        @if($rental->exists) @method('PUT') @endif

        <div class="row g-4">

            {{-- Equipment --}}
            <div class="col-md-6">
                <label class="form-label" for="equipment_id">Équipement</label>
                <select class="form-select" id="equipment_id" name="equipment_id" required>
                    <option value="">Choisir un équipement</option>
                    @foreach($equipments as $eq)
                    <option value="{{ $eq->id }}"
                        data-price="{{ $eq->price_per_day }}"
                        @selected(old('equipment_id', $rental->equipment_id) == $eq->id)>
                        {{ $eq->title }} ({{ $eq->category->name }} · {{ number_format($eq->price_per_day, 2, ',', ' ') }} €/j)
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Renter --}}
            <div class="col-md-6">
                <label class="form-label" for="renter_id">Locataire</label>
                <select class="form-select" id="renter_id" name="renter_id" required>
                    <option value="">Choisir un utilisateur</option>
                    @foreach($renters as $user)
                    <option value="{{ $user->id }}" @selected(old('renter_id', $rental->renter_id) == $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Dates --}}
            <div class="col-md-5">
                <label class="form-label" for="start_date">Date de début</label>
                <input type="date" class="form-control" id="start_date" name="start_date"
                       value="{{ old('start_date', $rental->start_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="end_date">Date de fin</label>
                <input type="date" class="form-control" id="end_date" name="end_date"
                       value="{{ old('end_date', $rental->end_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="w-100 text-center p-2" style="background:#f0f9f4;border-radius:10px">
                    <small class="text-muted d-block" style="font-size:11px">JOURS</small>
                    <strong id="form-days">–</strong>
                </div>
            </div>

            {{-- Price preview --}}
            <div class="col-12">
                <div id="form-estimate" class="p-3" style="background:#f0f9f4;border-radius:10px;display:none">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Prix estimé</span>
                        <strong id="form-total" style="color:#278658">–</strong>
                    </div>
                    <small class="text-muted">Le total est recalculé automatiquement selon le tarif journalier.</small>
                </div>
            </div>

            {{-- Status --}}
            <div class="col-md-6">
                <label class="form-label" for="status">Statut</label>
                <select class="form-select" id="status" name="status" required>
                    @foreach(['pending' => 'En attente', 'accepted' => 'Acceptée', 'ongoing' => 'En cours', 'returned' => 'Retournée', 'cancelled' => 'Annulée'] as $s => $lbl)
                    <option value="{{ $s }}" @selected(old('status', $rental->status ?? 'pending') === $s)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Notes --}}
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $rental->notes) }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-3 mt-4">
            <button class="btn btn-primary px-4">
                {{ $rental->exists ? 'Enregistrer' : 'Créer la location' }}
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.rentals.index') }}">Annuler</a>
        </div>
    </form>
</div>

<script>
const eqSelect    = document.getElementById('equipment_id');
const startInput  = document.getElementById('start_date');
const endInput    = document.getElementById('end_date');
const formDays    = document.getElementById('form-days');
const formTotal   = document.getElementById('form-total');
const formEst     = document.getElementById('form-estimate');

function recalc() {
    const opt = eqSelect.selectedOptions[0];
    if (!opt || !opt.dataset.price || !startInput.value || !endInput.value) {
        formEst.style.display = 'none';
        formDays.textContent = '–';
        return;
    }
    const s    = new Date(startInput.value);
    const e    = new Date(endInput.value);
    if (e < s) { formEst.style.display = 'none'; return; }
    const days  = Math.round((e - s) / 86400000) + 1;
    const total = (days * parseFloat(opt.dataset.price)).toFixed(2).replace('.', ',');
    formDays.textContent  = days;
    formTotal.textContent = total + ' €';
    formEst.style.display = 'block';
    endInput.min = startInput.value;
}

eqSelect.addEventListener('change', recalc);
startInput.addEventListener('change', recalc);
endInput.addEventListener('change', recalc);
recalc();
</script>
@endsection
