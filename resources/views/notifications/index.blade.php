@extends('layouts.front')

@section('title', 'Mes Notifications')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">Notifications</h1>
            <p class="text-muted small mb-0">Suivez l'activité de vos réservations et demandes</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-check-double me-1"></i> Tout marquer comme lu ({{ $unreadCount }})
                    </button>
                </form>
            @endif

            <div class="btn-group btn-group-sm">
                <a href="{{ route('notifications.index', ['filter' => 'all']) }}" class="btn {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Toutes
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="btn {{ $filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Non lues @if($unreadCount > 0)<span class="badge bg-danger ms-1">{{ $unreadCount }}</span>@endif
                </a>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-3">
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
                @php
                    $isRead = $notification->read();
                    $data = $notification->data;
                    $icon = $data['icon'] ?? 'fa-bell';
                    $color = $data['color'] ?? 'text-primary';
                @endphp
                <div class="list-group-item p-3 d-flex align-items-start gap-3 {{ $isRead ? '' : 'bg-light border-start border-4 border-primary' }}">
                    <div class="p-2 rounded-circle bg-white shadow-sm mt-1">
                        <i class="fa {{ $icon }} {{ $color }} fa-lg"></i>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 fw-bold {{ $isRead ? 'text-secondary' : 'text-dark' }}">
                                {{ $data['title'] ?? 'Notification' }}
                            </h6>
                            <small class="text-muted">
                                <i class="fa fa-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                            </small>
                        </div>
                        <p class="mb-2 text-muted small">
                            {{ $data['message'] ?? '' }}
                        </p>

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if(!empty($data['url']))
                                <a href="{{ route('notifications.read', $notification->id) }}" class="btn btn-sm btn-primary py-1 px-3" style="font-size: 0.8rem;">
                                    {{ $data['action_text'] ?? 'Voir les détails' }} &rarr;
                                </a>
                            @endif

                            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="m-0" onsubmit="return confirm('Supprimer cette notification ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0 ms-2" style="font-size: 0.8rem;">
                                    <i class="fa fa-trash-alt me-1"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fa fa-bell-slash fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="fw-bold text-secondary">Aucune notification</h5>
                    <p class="text-muted small mb-0">Vous êtes à jour !</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection

