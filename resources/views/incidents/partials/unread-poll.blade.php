@auth
<script>
// Refresh the unread-messages badges (navbar / sidebar) every 15 seconds.
(function () {
    const badges = document.querySelectorAll('[data-unread-badge]');
    if (!badges.length) return;
    setInterval(function () {
        fetch('{{ route('incidents.unread') }}', { headers: { Accept: 'application/json' } })
            .then(response => response.ok ? response.json() : null)
            .then(data => {
                if (!data) return;
                badges.forEach(badge => {
                    badge.textContent = data.count;
                    badge.hidden = data.count === 0;
                });
            })
            .catch(() => {});
    }, 15000);
})();
</script>
@endauth
