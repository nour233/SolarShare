(function () {
    document.querySelectorAll('[data-reclamation-filters]').forEach((form) => {
        const results = document.querySelector(form.dataset.results);
        let timer;

        const load = (url) => {
            results.classList.add('opacity-50');
            fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    results.innerHTML = payload.html;
                    window.history.replaceState({}, '', url);
                })
                .catch(() => { results.classList.remove('opacity-50'); })
                .finally(() => results.classList.remove('opacity-50'));
        };

        const submit = () => {
            const params = new URLSearchParams(new FormData(form));
            params.delete('page');
            load(`${form.action}?${params.toString()}`);
        };

        form.addEventListener('submit', (event) => { event.preventDefault(); submit(); });
        form.querySelectorAll('select').forEach((field) => field.addEventListener('change', submit));
        const search = form.querySelector('[name="search"]');
        if (search) search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(submit, 300);
        });
        results.addEventListener('click', (event) => {
            const link = event.target.closest('.pagination a');
            if (!link) return;
            event.preventDefault();
            load(link.href);
        });
    });
})();
