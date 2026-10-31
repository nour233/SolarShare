@auth
<div class="dropdown d-inline-block position-relative me-3" id="notif-dropdown-wrapper">
    <button class="btn btn-link text-decoration-none position-relative p-2" type="button" id="notifDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" style="color: inherit;">
        <i class="fa fa-bell fa-lg"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notif-badge" style="display: none; font-size: 0.65rem;">
            0
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm p-0" aria-labelledby="notifDropdownBtn" style="width: 340px; max-width: 90vw; max-height: 460px; overflow-y: auto; z-index: 1050; border-radius: 12px;">
        <li class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
            <strong class="text-dark small text-uppercase" style="letter-spacing: 0.5px;">
                <i class="fa fa-bell text-primary me-1"></i> Notifications
            </strong>
            <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="m-0" id="mark-all-form">
                @csrf
                <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 text-muted" style="font-size: 0.75rem;">
                    Tout marquer comme lu
                </button>
            </form>
        </li>
        <li id="notif-items-container">
            <div class="p-4 text-center text-muted small">
                <i class="fa fa-spinner fa-spin me-1"></i> Chargement...
            </div>
        </li>
        <li class="p-2 border-top text-center bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <a href="{{ auth()->user()->isAdmin() && request()->is('admin*') ? route('admin.notifications.index') : route('notifications.index') }}" class="small text-decoration-none text-primary fw-semibold">
                Voir toutes les notifications &rarr;
            </a>
        </li>
    </ul>
</div>

<script>
(function() {
    const badge = document.getElementById('notif-badge');
    const container = document.getElementById('notif-items-container');
    const feedUrl = '{{ route('notifications.feed') }}';

    function fetchNotifications() {
        fetch(feedUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (!data) return;

                // Update badge
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }

                // Render list
                if (!data.notifications || data.notifications.length === 0) {
                    container.innerHTML = '<div class="p-4 text-center text-muted small"><i class="fa fa-check-circle text-success mb-2 d-block fa-2x"></i>Aucune notification</div>';
                    return;
                }

                let html = '';
                data.notifications.forEach(n => {
                    const icon = n.data.icon || 'fa-info-circle';
                    const color = n.data.color || 'text-primary';
                    const bgClass = n.read ? '' : 'bg-light';
                    const unreadDot = n.read ? '' : '<span class="badge bg-primary rounded-circle p-1 ms-auto" style="width: 8px; height: 8px;"> </span>';
                    const readUrl = '{{ url('/notifications') }}/' + n.id + '/read';

                    html += `
                        <a href="${readUrl}" class="dropdown-item d-flex align-items-start gap-2 p-3 border-bottom text-wrap ${bgClass}" style="white-space: normal;">
                            <div class="mt-1"><i class="fa ${icon} ${color} fa-lg"></i></div>
                            <div class="flex-grow-1" style="font-size: 0.85rem;">
                                <div class="fw-semibold text-dark mb-1">${escapeHtml(n.data.title || 'Notification')}</div>
                                <div class="text-secondary small mb-1">${escapeHtml(n.data.message || '')}</div>
                                <div class="text-muted" style="font-size: 0.72rem;"><i class="fa fa-clock me-1"></i>${n.created_at}</div>
                            </div>
                            ${unreadDot}
                        </a>
                    `;
                });
                container.innerHTML = html;
            })
            .catch(() => {});
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Initial fetch
    fetchNotifications();

    // Poll every 20 seconds
    setInterval(fetchNotifications, 20000);
})();
</script>
@endauth

