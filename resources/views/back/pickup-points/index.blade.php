@extends('layouts.back')

@section('title', 'Points de retrait')

@section('content')

<div class="catalog-intro">
    <div>
        <span class="section-kicker">GESTION DES LIVRAISONS</span>

        <h2>Points de retrait</h2>

        <p>
            {{ $counts['all'] }}
            point{{ $counts['all'] > 1 ? 's' : '' }}
            de retrait au total.
        </p>
    </div>

    <a
        class="btn btn-primary add-button"
        href="{{ route('admin.pickup-points.create') }}"
    >
        <i class="fa fa-plus me-2"></i>
        Nouveau point
    </a>
</div>


{{-- Messages --}}
@if(session('success'))
    <div class="alert alert-success">
        <i class="fa fa-check-circle me-2"></i>
        {{ session('success') }}
    </div>
@endif


{{-- Filters --}}
<div class="d-flex flex-wrap gap-2 mb-4">

    <a
        href="{{ route('admin.pickup-points.index') }}"
        class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        Tous
        <span class="badge bg-secondary ms-1">
            {{ $counts['all'] }}
        </span>
    </a>

    <a
        href="{{ route('admin.pickup-points.index', ['status' => 'active']) }}"
        class="btn btn-sm {{ request('status') === 'active' ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        Actifs
        <span
            class="badge ms-1"
            style="background:#2e7d32"
        >
            {{ $counts['active'] }}
        </span>
    </a>

    <a
        href="{{ route('admin.pickup-points.index', ['status' => 'inactive']) }}"
        class="btn btn-sm {{ request('status') === 'inactive' ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        Inactifs
        <span
            class="badge ms-1"
            style="background:#757575"
        >
            {{ $counts['inactive'] }}
        </span>
    </a>

</div>


{{-- Search --}}
<form
    method="GET"
    action="{{ route('admin.pickup-points.index') }}"
    class="mb-4"
>
    @if(request('status'))
        <input
            type="hidden"
            name="status"
            value="{{ request('status') }}"
        >
    @endif

    <div class="input-group" style="max-width:500px">

        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Rechercher par nom, ville ou adresse..."
            value="{{ request('search') }}"
        >

        <button class="btn btn-outline-secondary">
            <i class="fa fa-search me-1"></i>
            Rechercher
        </button>

    </div>
</form>


{{-- Table --}}
<div class="surface p-0" style="overflow:hidden">

    <div class="table-responsive">

        <table class="table table-hover mb-0">

            <thead style="background:#f4f6f3">

                <tr>
                    <th class="px-4">#</th>
                    <th>Nom</th>
                    <th>Ville</th>
                    <th>Adresse</th>
                    <th>Horaires</th>
                    <th>Capacité</th>
                    <th>Statut</th>
                    <th class="text-end px-4">Actions</th>
                </tr>

            </thead>

            <tbody>

                @forelse($pickupPoints as $pickupPoint)

                    <tr>

                        <td class="px-4 text-muted">
                            #{{ str_pad($pickupPoint->id, 4, '0', STR_PAD_LEFT) }}
                        </td>

                        <td>
                            <div class="fw-semibold">
                                {{ $pickupPoint->name }}
                            </div>
                        </td>

                        <td>
                            <i class="fa fa-map-marker-alt text-muted me-1"></i>
                            {{ $pickupPoint->city }}
                        </td>

                        <td>
                            {{ Str::limit($pickupPoint->address, 35) }}
                        </td>

                        <td>
                            <small>
                                {{ $pickupPoint->opening_hours }}
                            </small>
                        </td>

                        <td>
                            {{ $pickupPoint->capacity }}
                        </td>

                        <td>

                            @if($pickupPoint->status === 'active')

                                <span class="badge bg-success">
                                    Actif
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    Inactif
                                </span>

                            @endif

                        </td>

                        <td class="text-end px-4">

                            <div class="actions justify-content-end">

                                <a
                                    href="{{ route('admin.pickup-points.show', $pickupPoint) }}"
                                    class="btn btn-sm btn-outline-success"
                                    title="Détail"
                                >
                                    <i class="fa fa-eye"></i>
                                </a>

                                <a
                                    href="{{ route('admin.pickup-points.edit', $pickupPoint) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Modifier"
                                >
                                    <i class="far fa-edit"></i>
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('admin.pickup-points.destroy', $pickupPoint) }}"
                                    onsubmit="return confirm('Supprimer ce point de retrait définitivement ?')"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer"
                                    >
                                        <i class="far fa-trash-alt"></i>
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5 text-muted"
                        >
                            Aucun point de retrait trouvé.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


<div class="mt-4">
    {{ $pickupPoints->links('pagination::bootstrap-5') }}
</div>

@endsection