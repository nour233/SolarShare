@extends('layouts.back')
@section('title', 'Réputation')
@section('content')
<section class="office-banner">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h2>Rapport de réputation</h2>
    <p class="mb-0">Analysez les avis clients pour comprendre la perception de SolarShare et de chaque service.</p>
        </div>
        <a href="{{ route('admin.reputation.download') }}" class="btn btn-primary"><i class="fas fa-file-pdf me-2" aria-hidden="true"></i>Télécharger le rapport</a>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Réputation globale</span><strong>{{ $averageRating !== null ? number_format($averageRating, 1, ',', ' ') . '/5' : '—' }}</strong><span class="text-primary">{{ $companyLabel }}</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Avis analysés</span><strong>{{ $totalReviews }}</strong><span class="text-muted">Tous les avis publiés</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Avis positifs</span><strong class="text-success">{{ $positiveCount }}</strong><span class="text-muted">Note de 4 ou 5</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Clients participants</span><strong>{{ $clientCount }}</strong><span class="text-muted">{{ $negativeCount }} avis à surveiller</span></div></div>
</div>

<div class="surface mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><span class="text-muted small">ANALYSE AUTOMATIQUE DU TEXTE</span><h3 class="mb-1">Sentiment des retours</h3></div>
        <span class="text-muted small">Score moyen : {{ $sentimentAverage !== null ? number_format($sentimentAverage, 2, ',', ' ') : '—' }} / 1</span>
    </div>
    <div class="row g-3 mt-2">
        <div class="col-md-4"><div class="stat-box h-100"><span class="text-success">Positif</span><strong>{{ $sentimentCounts['positive'] }}</strong><span class="text-muted">Avis et réclamations</span></div></div>
        <div class="col-md-4"><div class="stat-box h-100"><span class="text-secondary">Neutre</span><strong>{{ $sentimentCounts['neutral'] }}</strong><span class="text-muted">Avis et réclamations</span></div></div>
        <div class="col-md-4"><div class="stat-box h-100"><span class="text-danger">Négatif</span><strong>{{ $sentimentCounts['negative'] }}</strong><span class="text-muted">Avis et réclamations</span></div></div>
    </div>
    <p class="small text-muted mb-0 mt-3">Réclamations : {{ $reclamationSentimentCounts['positive'] }} positives, {{ $reclamationSentimentCounts['neutral'] }} neutres, {{ $reclamationSentimentCounts['negative'] }} négatives. OpenAI est utilisé si une clé est configurée ; sinon l’analyse locale par mots-clés est appliquée.</p>
</div>

<div class="surface mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="text-muted small">ASSISTANT ADMIN</span>
            <h3 class="mb-1">Posez une question à l’IA</h3>
            <p class="text-muted small mb-0">L’assistant répond à partir des avis, réclamations et locations enregistrés.</p>
        </div>
        <span class="badge bg-light text-dark">Analyse à la demande</span>
    </div>
    <form id="reputation-assistant-form" class="mt-3">
        @csrf
        <div class="input-group">
            <input id="reputation-assistant-question" class="form-control" maxlength="500" required
                placeholder="Ex. : Qui a le plus de réclamations et a-t-il déjà loué ?">
            <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Demander</button>
        </div>
    </form>
    <div id="reputation-assistant-result" class="alert alert-light border mt-3 mb-0 d-none" role="status"></div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="surface h-100">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="text-muted small">ANALYSE PAR SERVICE</span><h3 class="mb-0">Quelle expérience fonctionne le mieux ?</h3></div></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Service</th><th>Avis</th><th>Moyenne étoiles</th><th>Sentiment</th></tr></thead><tbody>
                @foreach($services as $service)
                    <tr><td><strong>{{ $service['label'] }}</strong></td><td>{{ $service['count'] }}</td><td>{{ $service['average'] !== null ? number_format($service['average'], 1, ',', ' ') . '/5' : '—' }}</td><td><span class="text-success">{{ $service['sentiment_positive'] }} positif(s)</span><br><span class="text-danger">{{ $service['sentiment_negative'] }} négatif(s)</span></td></tr>
                @endforeach
            </tbody></table></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="surface h-100">
            <span class="text-muted small">INTERACTION CLIENT</span><h3>Clients à suivre</h3>
            <p class="text-muted small">Les clients ayant publié le plus d'avis et ceux dont les avis sont régulièrement négatifs.</p>
            @forelse($clients->take(6) as $client)
                <a href="{{ route('admin.reputation.client', $client['user_id']) }}" class="d-flex justify-content-between align-items-center border-bottom py-2 text-decoration-none text-reset"><div><strong>{{ $client['name'] }}</strong><div class="small text-muted">{{ $client['count'] }} avis · moyenne {{ number_format($client['average'], 1, ',', ' ') }}/5</div></div>@if($client['negative'] > 0)<span class="badge bg-warning text-dark">{{ $client['negative'] }} négatif{{ $client['negative'] > 1 ? 's' : '' }}</span>@else<span class="badge bg-success">Satisfait</span>@endif</a>
            @empty
                <p class="text-muted mb-0">Aucun avis à analyser pour le moment.</p>
            @endforelse
            <a href="{{ route('admin.reputation.clients') }}" class="btn btn-outline-primary btn-sm mt-3">Voir tous les clients</a>
        </div>
    </div>
</div>

<div class="surface mt-4">
    <span class="text-muted small">DERNIERS AVIS</span><h3>Retours récents</h3>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Client</th><th>Service</th><th>Avis</th><th>Note</th><th>Date</th></tr></thead><tbody>
        @forelse($recentReviews as $review)
            <tr><td><a href="{{ route('admin.reputation.client', $review->user_id) }}">{{ $review->user?->name ?? 'Client supprimé' }}</a></td><td>{{ $review->serviceLabel() }}</td><td><strong>{{ $review->title }}</strong><div class="small text-muted">{{ \Illuminate\Support\Str::limit($review->comment, 90) }}</div></td><td class="{{ $review->rating <= 2 ? 'text-danger' : ($review->rating >= 4 ? 'text-success' : 'text-warning') }}">{{ $review->rating }}/5</td><td>{{ $review->created_at->format('d/m/Y') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun avis à analyser pour le moment.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
<script>
document.getElementById('reputation-assistant-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    const form = event.currentTarget;
    const input = document.getElementById('reputation-assistant-question');
    const result = document.getElementById('reputation-assistant-result');
    const button = form.querySelector('button');
    result.classList.remove('d-none', 'alert-danger');
    result.classList.add('alert-light');
    result.textContent = 'Analyse en cours…';
    button.disabled = true;

    try {
        const response = await fetch('{{ route('admin.reputation.assistant') }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ question: input.value })
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'La demande n’a pas pu être traitée.');
        }
        result.innerHTML = '<strong>Réponse :</strong> ' + data.answer.replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        }) + '<div class="small text-muted mt-2">Source : ' + (data.source === 'ia' ? 'OpenAI' : 'règles locales') + '</div>';
    } catch (error) {
        result.classList.remove('alert-light');
        result.classList.add('alert-danger');
        result.textContent = error.message;
    } finally {
        button.disabled = false;
    }
});
</script>
@endsection
