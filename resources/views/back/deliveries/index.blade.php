@extends('layouts.back')

@section('title', 'Livraisons')

@section('content')

<div class="catalog-intro">
    <div>
        <span class="section-kicker">
            GESTION DES LIVRAISONS
        </span>

        <h2>Toutes les livraisons</h2>

        <p>
            {{ $counts['all'] }}
            livraison{{ $counts['all'] > 1 ? 's' : '' }}
            au total.
        </p>
    </div>

    <a
        href="{{ route('admin.deliveries.create') }}"
        class="btn btn-primary add-button"
    >
        <i class="fa fa-plus me-2"></i>
        Nouvelle livraison
    </a>
</div>


@if(session('success'))
    <div class="alert alert-success">
        <i class="fa fa-check-circle me-2"></i>
        {{ session('success') }}
    </div>
@endif


{{-- Status filters --}}

<div class="d-flex flex-wrap gap-2 mb-4">

    <a
        href="{{ route('admin.deliveries.index') }}"
        class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        Toutes
        <span class="badge bg-secondary ms-1">
            {{ $counts['all'] }}
        </span>
    </a>

    <a
        href="{{ route('admin.deliveries.index', ['status' => 'planned']) }}"
        class="btn btn-sm {{ request('status') === 'planned' ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        Planifiées
        <span class="badge bg-secondary ms-1">
            {{ $counts['planned'] }}
        </span>
    </a>

    <a
        href="{{ route('admin.deliveries.index', ['status' => 'in_transit']) }}"
        class="btn btn-sm {{ request('status') === 'in_transit' ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        En transit
        <span
            class="badge ms-1"
            style="background:#1565c0"
        >
            {{ $counts['in_transit'] }}
        </span>
    </a>

    <a
        href="{{ route('admin.deliveries.index', ['status' => 'delivered']) }}"
        class="btn btn-sm {{ request('status') === 'delivered' ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        Livrées
        <span
            class="badge ms-1"
            style="background:#2e7d32"
        >
            {{ $counts['delivered'] }}
        </span>
    </a>

    <a
        href="{{ route('admin.deliveries.index', ['status' => 'returned']) }}"
        class="btn btn-sm {{ request('status') === 'returned' ? 'btn-dark' : 'btn-outline-secondary' }}"
    >
        Retournées
        <span
            class="badge ms-1"
            style="background:#6a1b9a"
        >
            {{ $counts['returned'] }}
        </span>
    </a>

</div>


<div class="surface p-0" style="overflow:hidden">

    <div class="table-responsive">

        <table class="table table-hover mb-0">

            <thead style="background:#f4f6f3">

                <tr>
                    <th class="px-4">#</th>
                    <th>Location</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Date / Heure</th>
                    <th>Point de retrait</th>
                    <th>Frais</th>
                    <th>Statut</th>
                    <th class="text-end px-4">
                        Actions
                    </th>
                </tr>

            </thead>

            <tbody>

                @forelse($deliveries as $delivery)

                    <tr>

                        <td class="px-4 text-muted">
                            #{{ str_pad($delivery->id, 4, '0', STR_PAD_LEFT) }}
                        </td>


                        <td>

                            <div class="fw-semibold">

                                #{{ str_pad(
                                    $delivery->rental_id,
                                    4,
                                    '0',
                                    STR_PAD_LEFT
                                ) }}

                            </div>

                            <small class="text-muted">

                                {{ $delivery->rental?->equipment?->title
                                    ?? 'Équipement indisponible'
                                }}

                            </small>

                        </td>


                        <td>

                            {{ $delivery->rental?->renter?->name
                                ?? '—'
                            }}

                        </td>


                        <td>

                            @if($delivery->type === 'pickup')

                                <span class="badge bg-success">
                                    <i class="fa fa-map-marker-alt me-1"></i>
                                    Retrait
                                </span>

                            @else

                                <span class="badge bg-primary">
                                    <i class="fa fa-truck me-1"></i>
                                    Livraison
                                </span>

                            @endif

                        </td>


                        <td>

                            <div>
                                {{ $delivery->scheduled_date->format('d/m/Y') }}
                            </div>

                            <small class="text-muted">
                                {{ substr($delivery->scheduled_time, 0, 5) }}
                            </small>

                        </td>


                        <td>

                            @if($delivery->pickupPoint)

                                {{ $delivery->pickupPoint->name }}

                                <br>

                                <small class="text-muted">
                                    {{ $delivery->pickupPoint->city }}
                                </small>

                            @else

                                <span class="text-muted">
                                    Domicile
                                </span>

                            @endif

                        </td>


                        <td>

                            <strong style="color:#278658">

                                {{ number_format(
                                    $delivery->delivery_fee,
                                    2,
                                    ',',
                                    ' '
                                ) }}

                                TND

                            </strong>

                        </td>


                        <td>

                            @switch($delivery->status)

                                @case('planned')
                                    <span class="badge bg-secondary">
                                        Planifiée
                                    </span>
                                    @break

                                @case('in_transit')
                                    <span class="badge bg-primary">
                                        En transit
                                    </span>
                                    @break

                                @case('delivered')
                                    <span class="badge bg-success">
                                        Livrée
                                    </span>
                                    @break

                                @case('returned')
                                    <span class="badge bg-dark">
                                        Retournée
                                    </span>
                                    @break

                            @endswitch

                        </td>


                        <td class="text-end px-4">

                            <div class="actions justify-content-end">

                                <a
                                    href="{{ route('admin.deliveries.show', $delivery) }}"
                                    class="btn btn-sm btn-outline-success"
                                    title="Détail"
                                >
                                    <i class="fa fa-eye"></i>
                                </a>


                                <a
                                    href="{{ route('admin.deliveries.edit', $delivery) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Modifier"
                                >
                                    <i class="far fa-edit"></i>
                                </a>


                                <form
                                    method="POST"
                                    action="{{ route('admin.deliveries.destroy', $delivery) }}"
                                    onsubmit="return confirm('Supprimer cette livraison définitivement ?')"
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
                            colspan="9"
                            class="text-center py-5 text-muted"
                        >

                            <i class="fa fa-truck fa-2x mb-3 d-block"></i>

                            Aucune livraison trouvée.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


<div class="mt-4">
    {{ $deliveries->links('pagination::bootstrap-5') }}
</div>

@endsection