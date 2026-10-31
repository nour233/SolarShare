@extends('layouts.back')
@section('title', 'Tarification dynamique')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <span class="section-kicker">CONFIGURATION</span>
        <h2 class="mb-0">Règles de tarification</h2>
        <p class="text-muted mb-0">Personnalisez les suppléments et réductions appliqués automatiquement.</p>
    </div>
    <a href="{{ route('admin.rentals.statistics') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa fa-chart-bar me-1"></i> Voir les statistiques
    </a>
</div>

<form method="POST" action="{{ route('admin.pricing.update') }}">
@csrf @method('PUT')

{{-- Weekend surcharge --}}
<div class="surface mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:40px;height:40px;background:#fff3e0;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa fa-sun" style="color:#e65100"></i>
        </div>
        <div>
            <h5 class="mb-0">Supplément Week-end</h5>
            <small class="text-muted">Appliqué sur chaque samedi et dimanche dans la période de location.</small>
        </div>
    </div>

    @foreach($groups->get('weekend', collect()) as $rule)
    @if($rule->unit !== 'months' && $rule->unit !== 'flag')
    <div class="row align-items-center g-3 mb-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold">{{ $rule->label }}</label>
            @if($rule->description)
            <div class="text-muted small">{{ $rule->description }}</div>
            @endif
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <input type="number"
                       class="form-control"
                       name="rules[{{ $rule->id }}][value]"
                       value="{{ round($rule->value * 100, 2) }}"
                       min="0" max="100" step="0.5" required>
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="preview-badge" id="preview-{{ $rule->id }}">
                Aperçu : +{{ round($rule->value * 100, 1) }}%
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>

{{-- Peak season --}}
<div class="surface mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:40px;height:40px;background:#e8f5e9;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa fa-thermometer-half" style="color:#278658"></i>
        </div>
        <div>
            <h5 class="mb-0">Haute Saison</h5>
            <small class="text-muted">Supplément appliqué sur les jours tombant dans les mois sélectionnés.</small>
        </div>
    </div>

    @foreach($groups->get('peak', collect()) as $rule)
    @if($rule->unit === 'percent')
    <div class="row align-items-center g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">{{ $rule->label }}</label>
            @if($rule->description)
            <div class="text-muted small">{{ $rule->description }}</div>
            @endif
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <input type="number"
                       class="form-control"
                       name="rules[{{ $rule->id }}][value]"
                       value="{{ round($rule->value * 100, 2) }}"
                       min="0" max="100" step="0.5" required>
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="preview-badge" id="preview-{{ $rule->id }}">
                Aperçu : +{{ round($rule->value * 100, 1) }}%
            </div>
        </div>
    </div>
    @endif
    @endforeach

    {{-- Month checkboxes --}}
    <label class="form-label fw-semibold mt-2">Mois de haute saison</label>
    @php
        $activeMonths = \App\Models\PricingRule::where('group','peak')
            ->where('unit','flag')
            ->where('value', '>', 0)
            ->pluck('value','key')
            ->keys()
            ->map(fn($k) => (int) str_replace('peak_month_','',$k))
            ->toArray();
        $monthNames = ['','Janvier','Février','Mars','Avril','Mai','Juin',
                       'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    @endphp
    <div class="d-flex flex-wrap gap-2 mt-2">
        @for($m = 1; $m <= 12; $m++)
        <label class="month-toggle {{ in_array($m, $activeMonths) ? 'active' : '' }}">
            <input type="checkbox"
                   name="peak_months[]"
                   value="{{ $m }}"
                   {{ in_array($m, $activeMonths) ? 'checked' : '' }}
                   style="display:none">
            {{ $monthNames[$m] }}
        </label>
        @endfor
    </div>
    <small class="text-muted mt-2 d-block">
        <i class="fa fa-info-circle me-1"></i>
        Si aucun mois n'est sélectionné, Juin, Juillet et Août sont utilisés par défaut.
    </small>
</div>

{{-- Duration discounts --}}
<div class="surface mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:40px;height:40px;background:#e3f2fd;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa fa-tag" style="color:#1565c0"></i>
        </div>
        <div>
            <h5 class="mb-0">Réductions Longue Durée</h5>
            <small class="text-muted">Réductions automatiques selon la durée de la location. Le palier le plus élevé atteint s'applique.</small>
        </div>
    </div>

    <div class="row g-3">
        @foreach($groups->get('discount', collect()) as $rule)
        <div class="col-md-4">
            <div class="p-3" style="border:1px solid #e5ebe3;border-radius:12px">
                <label class="form-label fw-semibold mb-1">{{ $rule->label }}</label>
                <div class="text-muted small mb-2">{{ $rule->description }}</div>
                <div class="input-group input-group-sm">
                    <input type="number"
                           class="form-control"
                           name="rules[{{ $rule->id }}][value]"
                           value="{{ round($rule->value * 100, 2) }}"
                           min="0" max="50" step="0.5" required>
                    <span class="input-group-text">%</span>
                </div>
                <div class="preview-badge mt-2" id="preview-{{ $rule->id }}" style="font-size:.8rem">
                    Économie : −{{ round($rule->value * 100, 1) }}%
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Live preview --}}
<div class="surface mb-4" style="border:2px solid #278658">
    <h5 class="mb-3"><i class="fa fa-calculator me-2" style="color:#278658"></i>Simulateur de prix</h5>
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Prix / jour (€)</label>
            <input type="number" id="sim-price" class="form-control" value="50" min="0" step="1">
        </div>
        <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" id="sim-start" class="form-control" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" id="sim-end" class="form-control" value="{{ date('Y-m-d', strtotime('+6 days')) }}">
        </div>
        <div class="col-md-3">
            <div class="p-3 text-center" style="background:#f0f9f4;border-radius:10px">
                <small class="text-muted d-block">Prix simulé</small>
                <strong id="sim-total" style="font-size:1.4rem;color:#278658">–</strong>
            </div>
        </div>
    </div>
    <div id="sim-breakdown" class="mt-3" style="display:none;font-size:.88rem;background:#f9fbf9;border-radius:8px;padding:12px"></div>
</div>

<div class="d-flex gap-3">
    <button type="submit" class="btn btn-primary px-5">
        <i class="fa fa-save me-2"></i>Enregistrer les règles
    </button>
    <a href="{{ route('admin.rentals.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>

</form>

<style>
.preview-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #f0f9f4;
    border-radius: 20px;
    font-size: .82rem;
    font-weight: 600;
    color: #278658;
}
.month-toggle {
    cursor: pointer;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid #dfe7dc;
    font-size: .85rem;
    background: white;
    color: #555;
    user-select: none;
    transition: all .15s;
}
.month-toggle.active, .month-toggle:has(input:checked) {
    background: #278658;
    color: white;
    border-color: #278658;
}
</style>

<script>
// Live preview badges on input change
document.querySelectorAll('input[name^="rules"]').forEach(input => {
    const id    = input.closest('[class*="row"], .col-md-4').querySelector('[id^="preview-"]')?.id;
    const badge = id ? document.getElementById(id) : null;
    if (!badge) return;

    const isDiscount = badge.textContent.includes('Économie');
    input.addEventListener('input', () => {
        const v = parseFloat(input.value) || 0;
        badge.textContent = isDiscount ? `Économie : −${v.toFixed(1)}%` : `Aperçu : +${v.toFixed(1)}%`;
    });
});

// Month toggle click
document.querySelectorAll('.month-toggle').forEach(label => {
    label.addEventListener('click', () => {
        const cb = label.querySelector('input');
        setTimeout(() => label.classList.toggle('active', cb.checked), 0);
    });
});

// Simulator
function simulate() {
    const price = parseFloat(document.getElementById('sim-price').value) || 0;
    const start = document.getElementById('sim-start').value;
    const end   = document.getElementById('sim-end').value;
    if (!start || !end || !price) return;

    const s = new Date(start), e = new Date(end);
    if (e < s) return;

    // Read current form values
    const rules = {};
    document.querySelectorAll('input[name^="rules"]').forEach(inp => {
        const match = inp.name.match(/rules\[(\d+)\]/);
        // We don't have keys here, so build from DOM order — skip for now
    });

    // Simple client-side simulation using current form values
    const weekendRate = parseFloat(
        document.querySelector('input[name^="rules"][name*="weekend"]')?.value || 15
    ) / 100;

    // Count days
    let days = Math.round((e - s) / 86400000) + 1;
    let weekendDays = 0;
    for (let d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
        if (d.getDay() === 0 || d.getDay() === 6) weekendDays++;
    }

    // Active peak months from checkboxes
    const peakMonths = [...document.querySelectorAll('input[name="peak_months[]"]:checked')]
        .map(c => parseInt(c.value));
    const effectivePeak = peakMonths.length ? peakMonths : [6, 7, 8];

    let peakDays = 0;
    for (let d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
        if (effectivePeak.includes(d.getMonth() + 1)) peakDays++;
    }

    const peakRate = parseFloat(
        document.querySelector('input[name^="rules"][name*="peak_surcharge"]')?.value || 10
    ) / 100;

    const weekdayDays = days - weekendDays;
    let subtotal = weekdayDays * price + weekendDays * price * (1 + weekendRate) + peakDays * price * peakRate;

    // Discount
    const d7  = parseFloat(document.querySelector('input[name*="7_days"]')?.value  || 10) / 100;
    const d14 = parseFloat(document.querySelector('input[name*="14_days"]')?.value || 15) / 100;
    const d30 = parseFloat(document.querySelector('input[name*="30_days"]')?.value || 20) / 100;

    let discountRate = 0;
    if (days >= 30 && d30 > 0)      discountRate = d30;
    else if (days >= 14 && d14 > 0) discountRate = d14;
    else if (days >= 7  && d7 > 0)  discountRate = d7;

    const discount = subtotal * discountRate;
    const total    = subtotal - discount;

    document.getElementById('sim-total').textContent = total.toFixed(2).replace('.', ',') + ' €';

    const bd = document.getElementById('sim-breakdown');
    bd.style.display = 'block';
    let html = '';
    if (weekdayDays > 0)  html += `<div class="d-flex justify-content-between"><span class="text-muted">${weekdayDays} jour(s) semaine</span><span>${(weekdayDays*price).toFixed(2).replace('.',',')} €</span></div>`;
    if (weekendDays > 0)  html += `<div class="d-flex justify-content-between"><span class="text-muted">${weekendDays} jour(s) week-end (+${(weekendRate*100).toFixed(0)}%)</span><span style="color:#e65100">${(weekendDays*price*(1+weekendRate)).toFixed(2).replace('.',',')} €</span></div>`;
    if (peakDays > 0)     html += `<div class="d-flex justify-content-between"><span class="text-muted">Supplément haute saison (${peakDays} j)</span><span style="color:#e65100">+${(peakDays*price*peakRate).toFixed(2).replace('.',',')} €</span></div>`;
    if (discountRate > 0) html += `<div class="d-flex justify-content-between"><span class="text-muted">Réduction −${(discountRate*100).toFixed(0)}%</span><span style="color:#2e7d32">−${discount.toFixed(2).replace('.',',')} €</span></div>`;
    html += `<hr class="my-1"><div class="d-flex justify-content-between fw-bold"><span>Total</span><span style="color:#278658">${total.toFixed(2).replace('.',',')} €</span></div>`;
    bd.innerHTML = html;
}

['sim-price','sim-start','sim-end'].forEach(id =>
    document.getElementById(id).addEventListener('input', simulate)
);
document.querySelectorAll('input[name^="rules"], input[name="peak_months[]"]').forEach(el =>
    el.addEventListener('change', simulate)
);
simulate();
</script>
@endsection
