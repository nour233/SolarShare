<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Contrat de location #{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1a1a1a; background: white; }
    .page { padding: 40px 48px; }

    /* Header */
    .header { display: table; width: 100%; margin-bottom: 32px; }
    .header-left { display: table-cell; vertical-align: top; }
    .header-right { display: table-cell; vertical-align: top; text-align: right; }
    .brand { font-size: 22px; font-weight: bold; color: #278658; }
    .brand small { display: block; font-size: 10px; color: #666; font-weight: normal; letter-spacing: 1px; }
    .contract-title { font-size: 18px; font-weight: bold; color: #17372c; }
    .contract-number { color: #278658; font-size: 13px; }
    .contract-date { color: #666; font-size: 11px; margin-top: 4px; }

    /* Divider */
    .divider { border: none; border-top: 2px solid #e5ebe3; margin: 20px 0; }
    .divider-green { border-top: 3px solid #278658; }

    /* Status badge */
    .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
    .status-pending   { background: #fff3e0; color: #e65100; }
    .status-accepted  { background: #e8f5e9; color: #2e7d32; }
    .status-ongoing   { background: #e3f2fd; color: #1565c0; }
    .status-returned  { background: #f3e5f5; color: #6a1b9a; }
    .status-cancelled { background: #fce4ec; color: #b71c1c; }

    /* Two-column sections */
    .two-col { display: table; width: 100%; margin-bottom: 24px; }
    .col-half { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
    .col-half:last-child { padding-right: 0; padding-left: 16px; }

    .section-title { font-size: 10px; letter-spacing: 1.5px; color: #278658; font-weight: bold;
                     text-transform: uppercase; margin-bottom: 10px; }
    .info-box { background: #f4f6f3; border-radius: 8px; padding: 14px; }
    .info-row { margin-bottom: 6px; }
    .info-label { color: #666; font-size: 11px; }
    .info-value { font-weight: bold; }

    /* Pricing table */
    .pricing-table { width: 100%; border-collapse: collapse; }
    .pricing-table th { background: #17372c; color: white; padding: 8px 12px; text-align: left; font-size: 11px; }
    .pricing-table td { padding: 8px 12px; border-bottom: 1px solid #e5ebe3; font-size: 11px; }
    .pricing-table .amount { text-align: right; }
    .pricing-table .surcharge { color: #e65100; }
    .pricing-table .discount { color: #2e7d32; }
    .pricing-table .total-row td { background: #e8f5e9; font-weight: bold; font-size: 13px; border-bottom: none; }

    /* Payment table */
    .payment-table { width: 100%; border-collapse: collapse; }
    .payment-table th { background: #f4f6f3; padding: 7px 10px; text-align: left; font-size: 11px; color: #17372c; }
    .payment-table td { padding: 7px 10px; border-bottom: 1px solid #e5ebe3; font-size: 11px; }

    /* Terms */
    .terms { background: #f4f6f3; border-radius: 8px; padding: 14px; font-size: 10px; color: #555; line-height: 1.6; }
    .terms strong { color: #17372c; }

    /* Signatures */
    .sig-table { display: table; width: 100%; margin-top: 32px; }
    .sig-cell { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
    .sig-cell:last-child { padding-right: 0; padding-left: 20px; }
    .sig-box { border-top: 1px solid #aaa; padding-top: 8px; min-height: 60px; }
    .sig-label { font-size: 10px; color: #666; }

    /* Footer */
    .footer { text-align: center; margin-top: 40px; font-size: 9px; color: #999; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="brand">☀ SolarShare<small>PLATEFORME DE PARTAGE D'ÉNERGIE SOLAIRE</small></div>
        </div>
        <div class="header-right">
            <div class="contract-title">CONTRAT DE LOCATION</div>
            <div class="contract-number">#{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}</div>
            <div class="contract-date">Émis le {{ now()->format('d/m/Y') }}</div>
            <div style="margin-top:6px">
                <span class="status status-{{ $rental->status }}">{{ $rental->statusLabel() }}</span>
            </div>
        </div>
    </div>

    <hr class="divider divider-green">

    {{-- Parties --}}
    <div class="two-col">
        <div class="col-half">
            <div class="section-title">Bailleur</div>
            <div class="info-box">
                <div class="info-row"><span class="info-value">SolarShare</span></div>
                <div class="info-row"><span class="info-label">Plateforme de partage d'énergie</span></div>
                <div class="info-row"><span class="info-label">Propriétaire équipement :</span><br>
                    <span class="info-value">{{ $rental->equipment->owner->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        <div class="col-half">
            <div class="section-title">Locataire</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-value">{{ $rental->renter->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email : </span>
                    <span>{{ $rental->renter->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Compte créé le : </span>
                    <span>{{ $rental->renter->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Equipment --}}
    <div class="section-title">Équipement loué</div>
    <div class="info-box" style="margin-bottom:20px">
        <div class="two-col" style="margin-bottom:0">
            <div class="col-half">
                <div class="info-row">
                    <span class="info-label">Désignation</span><br>
                    <span class="info-value">{{ $rental->equipment->title }}</span>
                </div>
                <div class="info-row" style="margin-top:8px">
                    <span class="info-label">Catégorie</span><br>
                    <span>{{ $rental->equipment->category->name }}</span>
                </div>
                <div class="info-row" style="margin-top:8px">
                    <span class="info-label">État</span><br>
                    <span>{{ $rental->equipment->condition }}</span>
                </div>
            </div>
            <div class="col-half">
                <div class="info-row">
                    <span class="info-label">Date de début</span><br>
                    <span class="info-value">{{ $rental->start_date->format('d/m/Y') }}</span>
                </div>
                <div class="info-row" style="margin-top:8px">
                    <span class="info-label">Date de fin</span><br>
                    <span class="info-value">{{ $rental->end_date->format('d/m/Y') }}</span>
                </div>
                <div class="info-row" style="margin-top:8px">
                    <span class="info-label">Durée</span><br>
                    <span class="info-value">{{ $rental->durationDays() }} jour(s)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Pricing breakdown --}}
    <div class="section-title">Détail du prix</div>
    <table class="pricing-table" style="margin-bottom:20px">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pricingBreakdown['breakdown'] as $line)
            @if($line['type'] !== 'total')
            <tr>
                <td class="{{ $line['type'] === 'surcharge' ? 'surcharge' : ($line['type'] === 'discount' ? 'discount' : '') }}">
                    {{ $line['label'] }}
                </td>
                <td class="amount {{ $line['type'] === 'surcharge' ? 'surcharge' : ($line['type'] === 'discount' ? 'discount' : '') }}">
                    @if($line['amount'] < 0)− {{ number_format(abs($line['amount']), 2, ',', ' ') }} €
                    @else{{ number_format($line['amount'], 2, ',', ' ') }} €
                    @endif
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>TOTAL LOCATION</td>
                <td class="amount" style="color:#278658">{{ number_format($rental->total_price, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td style="font-size:11px;color:#666">Caution (remboursable à la restitution)</td>
                <td class="amount" style="font-size:11px;color:#666">{{ number_format($rental->equipment->deposit, 2, ',', ' ') }} €</td>
            </tr>
        </tfoot>
    </table>

    {{-- Payments --}}
    @if($rental->payments->isNotEmpty())
    <div class="section-title">Paiements enregistrés</div>
    <table class="payment-table" style="margin-bottom:20px">
        <thead>
            <tr><th>Type</th><th>Montant</th><th>Méthode</th><th>Statut</th><th>Date</th></tr>
        </thead>
        <tbody>
            @foreach($rental->payments as $p)
            <tr>
                <td>{{ $p->typeLabel() }}</td>
                <td>{{ number_format($p->amount, 2, ',', ' ') }} €</td>
                <td>{{ $p->methodLabel() }}</td>
                <td>{{ $p->statusLabel() }}</td>
                <td>{{ $p->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Notes --}}
    @if($rental->notes)
    <div class="section-title">Notes</div>
    <div class="info-box" style="margin-bottom:20px;font-size:11px;color:#555">{{ $rental->notes }}</div>
    @endif

    {{-- Terms --}}
    <div class="section-title">Conditions générales</div>
    <div class="terms">
        <strong>1. Objet</strong> — Le présent contrat régit la location de l'équipement désigné ci-dessus entre SolarShare et le locataire.<br>
        <strong>2. Durée</strong> — La location prend effet à la date de début et se termine à la date de fin indiquées. Tout dépassement sera facturé au tarif journalier en vigueur.<br>
        <strong>3. Caution</strong> — Une caution est versée à la remise de l'équipement. Elle est restituée intégralement après vérification de l'état de l'équipement, déduction faite des éventuelles réparations.<br>
        <strong>4. Utilisation</strong> — Le locataire s'engage à utiliser l'équipement conformément à sa destination et à le restituer en bon état de fonctionnement.<br>
        <strong>5. Responsabilité</strong> — Le locataire est responsable de tout dommage survenu pendant la période de location.
    </div>

    {{-- Signatures --}}
    <div class="sig-table">
        <div class="sig-cell">
            <div class="sig-label">Le bailleur (SolarShare)</div>
            <div class="sig-box"></div>
            <div class="sig-label" style="margin-top:4px">Signature :</div>
        </div>
        <div class="sig-cell">
            <div class="sig-label">Le locataire ({{ $rental->renter->name }})</div>
            <div class="sig-box"></div>
            <div class="sig-label" style="margin-top:4px">Signature :</div>
        </div>
    </div>

    <div class="footer">
        SolarShare — Contrat généré automatiquement le {{ now()->format('d/m/Y à H:i') }}
        · Réf. #{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}
    </div>
</div>
</body>
</html>
