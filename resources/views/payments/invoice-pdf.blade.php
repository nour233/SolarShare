<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $payment->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #333; line-height: 1.5; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #278658; padding-bottom: 15px; }
        .logo { font-size: 26px; font-weight: bold; color: #278658; }
        .invoice-title { float: right; text-align: right; }
        .invoice-title h2 { margin: 0; color: #17372c; font-size: 20px; }
        .invoice-title p { margin: 3px 0 0; color: #666; font-size: 11px; }
        .row { width: 100%; margin-bottom: 25px; }
        .col { width: 50%; float: left; }
        .clear { clear: both; }
        .box { background: #f8faf9; padding: 12px; border-radius: 6px; border: 1px solid #e1ebe5; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #278658; color: white; text-align: left; padding: 8px 10px; font-size: 12px; }
        td { padding: 9px 10px; border-bottom: 1px solid #e5eee9; font-size: 12px; }
        .totals { width: 45%; float: right; margin-top: 15px; }
        .totals table td { border: none; padding: 4px 6px; }
        .badge-paid { background: #e8f5e9; color: #2e7d32; font-weight: bold; padding: 4px 8px; border-radius: 4px; display: inline-block; }
        .footer { margin-top: 60px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">☀ SolarShare</div>
        <div class="invoice-title">
            <h2>FACTURE ACQUITTÉE</h2>
            <p>N° {{ $payment->invoice_number }}</p>
            <p>Date : {{ $payment->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="row">
        <div class="col">
            <div class="box" style="margin-right: 10px;">
                <strong>Émetteur :</strong><br>
                SolarShare Plateforme SAS<br>
                123 Avenue de l'Énergie Verte<br>
                Contact: contact@solarshare.test
            </div>
        </div>
        <div class="col">
            <div class="box" style="margin-left: 10px;">
                <strong>Client (Locataire) :</strong><br>
                {{ $rental->renter?->name }}<br>
                Email : {{ $rental->renter?->email }}<br>
                Réservation N° #{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Période</th>
                <th>Type</th>
                <th style="text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $rental->equipment?->title }}</strong><br>
                    <small style="color: #666;">Catégorie : {{ $rental->equipment?->category?->name ?? 'Général' }}</small>
                </td>
                <td>du {{ $rental->start_date?->format('d/m/Y') }} au {{ $rental->end_date?->format('d/m/Y') }}</td>
                <td>{{ $payment->typeLabel() }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($payment->amount, 2, ',', ' ') }} €</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td><strong>Sous-total HT :</strong></td>
                <td style="text-align: right;">{{ number_format($payment->amount / 1.20, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td><strong>TVA (20%) :</strong></td>
                <td style="text-align: right;">{{ number_format($payment->amount - ($payment->amount / 1.20), 2, ',', ' ') }} €</td>
            </tr>
            <tr style="border-top: 1px solid #278658; font-size: 14px;">
                <td><strong>Total TTC Payé :</strong></td>
                <td style="text-align: right; font-weight: bold; color: #278658;">{{ number_format($payment->amount, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td><strong>Statut :</strong></td>
                <td style="text-align: right;"><span class="badge-paid">PAYÉ PAR CARTE</span></td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

    <div style="margin-top: 25px; font-size: 11px; color: #666;">
        <strong>Informations de transaction :</strong><br>
        Méthode : Carte bancaire (Stripe Sandbox)<br>
        Transaction Ref : {{ $payment->stripe_payment_intent ?? $payment->stripe_session_id ?? 'N/A' }}
    </div>

    <div class="footer">
        SolarShare SAS - Plateforme collaborative de partage d'énergie et matériel solaire - Merci pour votre confiance !
    </div>

</body>
</html>

