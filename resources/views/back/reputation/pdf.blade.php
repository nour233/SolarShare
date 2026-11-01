<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de réputation SolarShare</title>
    <style>
        @page { margin: 28px 32px; }
        body { color: #243447; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { color: #0b5ed7; font-size: 22px; margin: 0 0 4px; }
        h2 { border-bottom: 1px solid #d9e2ec; color: #243447; font-size: 14px; margin: 22px 0 8px; padding-bottom: 5px; }
        .muted { color: #6c757d; }
        .summary { border-collapse: collapse; margin: 16px 0; width: 100%; }
        .summary td { background: #f1f6fb; border: 1px solid #d9e2ec; padding: 9px; width: 25%; }
        .value { display: block; font-size: 16px; font-weight: bold; margin-top: 4px; }
        table { border-collapse: collapse; margin-bottom: 12px; width: 100%; }
        th { background: #0b5ed7; color: #fff; font-weight: bold; }
        th, td { border: 1px solid #d9e2ec; padding: 6px; text-align: left; }
        tr:nth-child(even) td { background: #f8fafc; }
        .columns { width: 100%; }
        .columns td { border: 0; padding: 0 8px 0 0; vertical-align: top; width: 50%; }
        .columns td + td { padding: 0 0 0 8px; }
        .footer { color: #6c757d; font-size: 9px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Rapport de réputation SolarShare</h1>
    <div class="muted">Généré le {{ now()->format('d/m/Y à H:i') }}</div>

    <table class="summary">
        <tr>
            <td>Réputation globale<span class="value">{{ $averageRating !== null ? number_format($averageRating, 1, ',', ' ') . '/5' : '—' }}</span><span class="muted">{{ $companyLabel }}</span></td>
            <td>Avis analysés<span class="value">{{ $totalReviews }}</span></td>
            <td>Avis positifs<span class="value">{{ $positiveCount }}</span><span class="muted">Notes de 4 ou 5</span></td>
            <td>Clients participants<span class="value">{{ $clientCount }}</span></td>
        </tr>
    </table>

    <h2>Analyse par service</h2>
    <table>
        <thead><tr><th>Service</th><th>Avis</th><th>Moyenne étoiles</th><th>Score texte</th><th>Sentiment</th></tr></thead>
        <tbody>
        @foreach($services as $service)
            <tr><td>{{ $service['label'] }}</td><td>{{ $service['count'] }}</td><td>{{ $service['average'] !== null ? number_format($service['average'], 1, ',', ' ') . '/5' : '—' }}</td><td>{{ $service['sentiment_average'] !== null ? number_format($service['sentiment_average'], 2, ',', ' ') : '—' }}</td><td>{{ $service['sentiment_positive'] }} positif(s), {{ $service['sentiment_negative'] }} négatif(s)</td></tr>
        @endforeach
        </tbody>
    </table>

    <h2>Sentiment des avis et réclamations</h2>
    <table>
        <thead><tr><th>Catégorie</th><th>Positif</th><th>Neutre</th><th>Négatif</th></tr></thead>
        <tbody>
            <tr><td>Avis et réclamations</td><td>{{ $sentimentCounts['positive'] }}</td><td>{{ $sentimentCounts['neutral'] }}</td><td>{{ $sentimentCounts['negative'] }}</td></tr>
            <tr><td>Réclamations uniquement</td><td>{{ $reclamationSentimentCounts['positive'] }}</td><td>{{ $reclamationSentimentCounts['neutral'] }}</td><td>{{ $reclamationSentimentCounts['negative'] }}</td></tr>
        </tbody>
    </table>

    <h2>Clients les mieux et les moins bien notés</h2>
    <table class="columns">
        <tr>
            <td>
                <table>
                    <thead><tr><th colspan="3">Meilleurs clients</th></tr><tr><th>Client</th><th>Avis</th><th>Moyenne</th></tr></thead>
                    <tbody>
                    @forelse($bestClients as $client)
                        <tr><td>{{ $client['name'] }}</td><td>{{ $client['count'] }}</td><td>{{ number_format($client['average'], 1, ',', ' ') }}/5</td></tr>
                    @empty
                        <tr><td colspan="3">Aucun client noté.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <table>
                    <thead><tr><th colspan="3">Clients à surveiller</th></tr><tr><th>Client</th><th>Avis</th><th>Moyenne</th></tr></thead>
                    <tbody>
                    @forelse($worstClients as $client)
                        <tr><td>{{ $client['name'] }}</td><td>{{ $client['count'] }}</td><td>{{ number_format($client['average'], 1, ',', ' ') }}/5</td></tr>
                    @empty
                        <tr><td colspan="3">Aucun client noté.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">SolarShare — Rapport réservé à l’administration.</div>
</body>
</html>
