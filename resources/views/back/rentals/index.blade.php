@extends('layouts.back')
@section('title', 'Locations')

@section('content')
<div class="catalog-intro">
    <div>
        <span class="section-kicker">GESTION DES LOCATIONS</span>
        <h2>Toutes les réservations</h2>
        <p>{{ $counts['all'] }} location{{ $counts['all'] > 1 ? 's' : '' }} au total.</p>
    </div>
    <a class="btn btn-primary add-button" href="{{ route('admin.rentals.create') }}">
        <i class="fa fa-plus me-2"></i>Nouvelle location
    </a>
</div>

{{-- Status filter tabs --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.rentals.index') }}"
       class="btn btn-sm {{ !$status ? 'btn-dark' : 'btn-outline-secondary' }}">
        Toutes <span class="badge bg-secondary ms-1">{{ $counts['all'] }}</span>
    </a>
    @foreach(['pending' => ['En attente', '#e65100', $counts['pending']], 'accepted' => ['Acceptées', '#2e7d32', $counts['accepted']], 'ongoing' => ['En cours', '#1565c0', $counts['ongoing']], 'returned' => ['Retournées', '#6a1b9a', $counts['returned']], 'cancelled' => ['Annulées', '#b71c1c', $counts['cancelled']]] as $key => [$label, $color, $count])
    <a href="{{ route('admin.rentals.index', ['status' => $key]) }}"
       class="btn btn-sm {{ $status === $key ? 'btn-dark' : 'btn-outline-secondary' }}">
        {{ $label }} <span class="badge ms-1" style="background:{{ $color }}">{{ $count }}</span>
    </a>
    @endforeach
</div>

<div class="surface p-0" style="overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background:#f4f6f3">
                <tr>
                    <th class="px-4">#</th>
                    <th>Équipement</th>
                    <th>Locataire</th>
                    <th>Dates</th>
                    <th>Durée</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th class="text-end px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentals as $rental)
                <tr>
                    <td class="px-4 text-muted">#{{ str_pad($rental->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if(!empty($rental->equipment->photos[0]))
                            <img src="{{ asset($rental->equipment->photos[0]) }}" class="thumb" alt="">
                            @endif
                            <div>
                                <div class="fw-semibold">{{ Str::limit($rental->equipment->title, 30) }}</div>
                                <small class="text-muted">{{ $rental->equipment->category->name }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>{{ $rental->renter->name }}</div>
                        <small class="text-muted">{{ $rental->renter->email }}</small>
                    </td>
                    <td>
                        <small>{{ $rental->start_date->format('d/m/Y') }}<br>→ {{ $rental->end_date->format('d/m/Y') }}</small>
                    </td>
                    <td class="text-center">{{ $rental->durationDays() }}j</td>
                    <td><strong style="color:#278658">{{ number_format($rental->total_price, 2, ',', ' ') }} €</strong></td>
                    <td>
                        <form method="POST" action="{{ route('admin.rentals.status', $rental) }}">
                            @csrf @method('PATCH')
                            <select name="status" class="form-select form-select-sm" style="min-width:130px"
                                    onchange="this.form.submit()">
                                @foreach(['pending' => 'En attente', 'accepted' => 'Acceptée', 'ongoing' => 'En cours', 'returned' => 'Retournée', 'cancelled' => 'Annulée'] as $s => $lbl)
                                <option value="{{ $s }}" @selected($rental->status === $s)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="text-end px-4">
                        <div class="actions justify-content-end">
                            <a href="{{ route('admin.rentals.show', $rental) }}" class="btn btn-sm btn-outline-success" title="Détail">
                                <i class="fa fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.rentals.edit', $rental) }}" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                <i class="far fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.rentals.destroy', $rental) }}"
                                  onsubmit="return confirm('Supprimer cette location définitivement ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">Aucune location trouvée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $rentals->links('pagination::bootstrap-5') }}</div>
@endsection
