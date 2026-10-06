@extends('layouts.back')
@section('title', 'Avis de '.$client->name)
@section('content')
<a href="{{ route('admin.reputation.clients') }}" class="text-primary">← Retour aux clients</a>
<div class="office-banner mt-3"><h2>{{ $client->name }}</h2><p class="mb-0">{{ $client->email }} · {{ $reviews->total() }} avis · moyenne {{ number_format($averageRating, 1, ',', ' ') }}/5</p></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="surface">
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Service</th><th>Titre et commentaire</th><th>Note</th><th>Date</th><th>Action</th></tr></thead><tbody>
        @forelse($reviews as $review)
            <tr><td>{{ $review->serviceLabel() }}</td><td><strong>{{ $review->title }}</strong><div class="small text-muted">{{ $review->comment }}</div></td><td class="{{ $review->rating <= 2 ? 'text-danger' : ($review->rating >= 4 ? 'text-success' : 'text-warning') }}">{{ $review->rating }}/5</td><td>{{ $review->created_at->format('d/m/Y H:i') }}</td><td><form method="POST" action="{{ route('admin.reputation.reviews.destroy', $review) }}" onsubmit="return confirm('Supprimer cet avis définitivement ?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Supprimer l’avis"><i class="far fa-trash-alt"></i></button></form></td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Ce client n’a aucun avis.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
<div class="mt-4">{{ $reviews->links('pagination::bootstrap-5') }}</div>
@endsection
