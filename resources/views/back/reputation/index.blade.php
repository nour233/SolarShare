@extends('layouts.back')
@section('title', 'Réputation')
@section('content')
<section class="office-banner">
    <h2>Rapport de réputation</h2>
    <p class="mb-0">Analysez les avis clients pour comprendre la perception de SolarShare et de chaque service.</p>
</section>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Réputation globale</span><strong>{{ $averageRating !== null ? number_format($averageRating, 1, ',', ' ') . '/5' : '—' }}</strong><span class="text-primary">{{ $companyLabel }}</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Avis analysés</span><strong>{{ $totalReviews }}</strong><span class="text-muted">Tous les avis publiés</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Avis positifs</span><strong class="text-success">{{ $positiveCount }}</strong><span class="text-muted">Note de 4 ou 5</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-box h-100"><span class="text-muted">Clients participants</span><strong>{{ $clientCount }}</strong><span class="text-muted">{{ $negativeCount }} avis à surveiller</span></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="surface h-100">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="text-muted small">ANALYSE PAR SERVICE</span><h3 class="mb-0">Quelle expérience fonctionne le mieux ?</h3></div></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Service</th><th>Avis</th><th>Moyenne</th><th>Positifs</th><th>À améliorer</th></tr></thead><tbody>
                @foreach($services as $service)
                    <tr><td><strong>{{ $service['label'] }}</strong></td><td>{{ $service['count'] }}</td><td>{{ $service['average'] !== null ? number_format($service['average'], 1, ',', ' ') . '/5' : '—' }}</td><td class="text-success">{{ $service['positive'] }}</td><td class="text-danger">{{ $service['negative'] }}</td></tr>
                @endforeach
            </tbody></table></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="surface h-100">
            <span class="text-muted small">INTERACTION CLIENT</span><h3>Clients à suivre</h3>
            <p class="text-muted small">Les clients ayant publié le plus d'avis et ceux dont les avis sont régulièrement négatifs.</p>
            @forelse($clients->take(6) as $client)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2"><div><strong>{{ $client['name'] }}</strong><div class="small text-muted">{{ $client['count'] }} avis · moyenne {{ number_format($client['average'], 1, ',', ' ') }}/5</div></div>@if($client['negative'] > 0)<span class="badge bg-warning text-dark">{{ $client['negative'] }} négatif{{ $client['negative'] > 1 ? 's' : '' }}</span>@else<span class="badge bg-success">Satisfait</span>@endif</div>
            @empty
                <p class="text-muted mb-0">Aucun avis à analyser pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="surface mt-4">
    <span class="text-muted small">DERNIERS AVIS</span><h3>Retours récents</h3>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Client</th><th>Service</th><th>Avis</th><th>Note</th><th>Date</th></tr></thead><tbody>
        @forelse($recentReviews as $review)
            <tr><td>{{ $review->user?->name ?? 'Client supprimé' }}</td><td>{{ $review->serviceLabel() }}</td><td><strong>{{ $review->title }}</strong><div class="small text-muted">{{ \Illuminate\Support\Str::limit($review->comment, 90) }}</div></td><td class="{{ $review->rating <= 2 ? 'text-danger' : ($review->rating >= 4 ? 'text-success' : 'text-warning') }}">{{ $review->rating }}/5</td><td>{{ $review->created_at->format('d/m/Y') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Aucun avis à analyser pour le moment.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endsection
