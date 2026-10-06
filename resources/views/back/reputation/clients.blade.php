@extends('layouts.back')
@section('title', 'Clients et réputation')
@section('content')
<div class="catalog-intro"><div><span class="section-kicker">INTERACTION CLIENT</span><h2>Clients ayant laissé un avis</h2><p>Consultez l’historique et la tendance des avis de chaque client.</p></div><a href="{{ route('admin.reputation.index') }}" class="btn btn-outline-primary">← Retour au rapport</a></div>
<div class="surface">
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Client</th><th>Email</th><th>Avis</th><th>Moyenne</th><th>Avis négatifs</th><th></th></tr></thead><tbody>
        @forelse($clients as $client)
            <tr><td><strong>{{ $client->name }}</strong></td><td>{{ $client->email }}</td><td>{{ $client->reviews_count }}</td><td>{{ $client->reviews_average !== null ? number_format($client->reviews_average, 1, ',', ' ') . '/5' : '—' }}</td><td class="{{ $client->negative_reviews > 0 ? 'text-danger' : 'text-success' }}">{{ $client->negative_reviews }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('admin.reputation.client', $client) }}">Voir les avis</a></td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Aucun client n’a encore publié d’avis.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endsection
