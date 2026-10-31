@extends('layouts.back')

@section('title', 'Centre de notifications')

@section('content')
<div class="surface">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="h4 fw-bold mb-1">Notifications système</h2>
            <p class="text-muted small mb-0">Demandes de location, signalements d'incidents et réclamations récentes</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm">
                        <i class="fa fa-check-double me-1"></i> Tout marquer comme lu ({{ $unreadCount }})
                    </button>
                </form>
            @endif

            <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.notifications.index', ['filter' => 'all']) }}" class="btn {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Toutes
                </a>
                <a href="{{ route('admin.notifications.index', ['filter' => 'unread']) }}" class="btn {{ $filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Non lues @if($unreadCount > 0)<span class="badge bg-danger ms-1">{{ $unreadCount }}</span>@endif
                </a>
            </div>
        </div>
    </div>

    <div class="list-group list-group-flush border rounded-3">
        @forelse($notifications as $notification)
            @php
                $isRead = $notification->read();
                $data = $notification->data;
                $icon = $data['icon'] ?? 'fa-bell';
                $color = $data['color'] ?? 'text-primary';
            @endphp
            <div class="list-group-item p-3 d-flex align-items-start gap-3 {{ $isRead ? '' : 'bg-light border-start border-4 border-success' }}">
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
                                {{ $data['action_text'] ?? 'Accéder' }} &rarr;
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
                <p class="text-muted small mb-0">Toutes les actions système sont à jour.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection

