(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    
    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.sticky-top').addClass('shadow-sm').css('top', '0px');
        } else {
            $('.sticky-top').removeClass('shadow-sm').css('top', '-100px');
        }
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Facts counter
    $('[data-toggle="counter-up"]').counterUp({
        delay: 10,
        time: 2000
    });


    // Header carousel
    $(".header-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        loop: true,
        nav: false,
        dots: true,
        items: 1,
        dotsData: true,
    });


    // Testimonials carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        center: true,
        dots: false,
        loop: true,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsive: {
            0:{
                items:1
            },
            768:{
                items:2
            }
        }
    });


    // Portfolio isotope and filter
    var portfolioIsotope = $('.portfolio-container').isotope({
        itemSelector: '.portfolio-item',
        layoutMode: 'fitRows'
    });
    $('#portfolio-flters li').on('click', function () {
        $("#portfolio-flters li").removeClass('active');
        $(this).addClass('active');

        portfolioIsotope.isotope({filter: $(this).data('filter')});
    });
    
})(jQuery);

// Dynamic catalogue search, categories and pagination on home and catalogue pages.
(() => {
    let timer, controller, version = 0;
    function cancel() { clearTimeout(timer); version++; if (controller) controller.abort(); }
    async function load(url, scroll = false) {
        cancel();
        const request = version;
        controller = new AbortController();
        const currentController = controller;
        const timeout = setTimeout(() => currentController.abort(), 15000);
        const section = document.getElementById('equipments');
        section.setAttribute('aria-busy', 'true');
        section.querySelector('.catalogue-status').textContent = 'Recherche en cours…';
        try {
            const response = await fetch(url, {signal: controller.signal, headers: {'X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok) throw new Error('request');
            const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updated = parsed.getElementById('equipments');
            if (!updated) throw new Error('response');
            if (request !== version) return;
            for (const selector of ['.solar-advisor', '.catalogue-search']) {
                const old = section.querySelector(selector), fresh = updated.querySelector(selector);
                if (old && fresh) {
                    if (selector === '.catalogue-search') {
                        old.querySelectorAll('input[name="category"]').forEach(input => input.remove());
                        const category = fresh.querySelector('input[name="category"]');
                        if (category) old.append(category);
                    }
                    fresh.replaceWith(old);
                }
            }
            section.replaceWith(updated);
            updated.removeAttribute('aria-busy');
            updated.querySelector('.catalogue-status').textContent = 'Catalogue mis à jour.';
            history.replaceState(null, '', url);
            if (scroll) updated.querySelector('.equipment-filters').scrollIntoView({behavior:'smooth', block:'start'});
        } catch (error) {
            if (request === version) section.querySelector('.catalogue-status').textContent = 'Impossible de charger les équipements. Réessayez en modifiant la recherche.';
        } finally { clearTimeout(timeout); if (request === version) section.removeAttribute('aria-busy'); }
    }
    function searchUrl(form) {
        const url = new URL(form.action);
        for (const [key,value] of new FormData(form)) if (value.trim()) url.searchParams.set(key,value.trim());
        url.hash = 'equipments'; return url;
    }
    document.addEventListener('input', event => {
        if (!event.target.matches('#catalogue-query')) return;
        cancel();
        const form = event.target.form;
        form.querySelector('.catalogue-clear').hidden = !event.target.value;
        timer = setTimeout(() => load(searchUrl(form)), 350);
    });
    document.addEventListener('submit', event => {
        if (!event.target.matches('.catalogue-search')) return;
        event.preventDefault(); load(searchUrl(event.target));
    });
    document.addEventListener('click', event => {
        const clear = event.target.closest('.catalogue-clear');
        if (clear) { const form = clear.closest('form'); form.querySelector('input[name="q"]').value = ''; clear.hidden = true; form.querySelector('input[name="q"]').focus(); load(searchUrl(form)); return; }
        const link = event.target.closest('#equipments .equipment-filters a, #equipments .equipment-pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        const url = new URL(link.href);
        if (link.closest('.equipment-filters')) {
            const value = document.getElementById('catalogue-query').value.trim();
            if (value) url.searchParams.set('q',value); else url.searchParams.delete('q');
        }
        load(url, !!link.closest('.equipment-pagination'));
    });
})();

// Delegation also covers the homepage section replaced by category filtering.
document.addEventListener('submit', async event => {
    const form = event.target.closest('.advisor-form');
    if (!form) return;
    event.preventDefault();
    const panel = form.closest('.advisor-interaction');
    const button = form.querySelector('button');
    if (button.disabled) return;
    const status = panel.querySelector('.advisor-status');
    const results = panel.querySelector('.advisor-results');
    results.replaceChildren();
    const loading = panel.querySelector('.advisor-loading');
    const buttonLabel = button.textContent;
    button.disabled = true;
    button.textContent = 'Recherche en cours…';
    panel.setAttribute('aria-busy', 'true');
    if (loading) loading.hidden = false;
    status.textContent = 'Nous explorons le catalogue pour votre projet…';
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 55000);
    try {
        const response = await fetch(form.action, {method:'POST',body:new FormData(form),signal:controller.signal,headers:{Accept:'application/json'}});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(response.status === 429 ? 'Trop de recherches. Patientez une minute.' : response.status === 419 ? 'Votre session a expiré. Rechargez la page.' : Object.values(data.errors || {}).flat()[0] || data.message || 'Recherche indisponible. Réessayez.');
        status.textContent = data.items.length ? 'Voici les pistes qui correspondent à votre besoin. Vérifiez les caractéristiques et les dates sur chaque fiche.' : 'Aucun équipement adapté trouvé dans le catalogue consulté. Essayez de préciser vos appareils ou explorez les catégories.';
        for (const [index, item] of data.items.entries()) {
            const card = document.createElement('article'); card.className = 'advisor-result';
            const visual = document.createElement('div'); visual.className = 'advisor-result-visual';
            if (item.photo) {
                const image = document.createElement('img'); image.src = item.photo; image.alt = item.title; image.loading = 'lazy';
                image.addEventListener('error', () => { image.remove(); visual.classList.add('advisor-image-missing'); }, {once:true});
                visual.append(image);
            } else visual.classList.add('advisor-image-missing');
            const badge = document.createElement('span'); badge.className = 'advisor-result-rank'; badge.textContent = 'Suggestion ' + (index + 1); visual.append(badge);
            const body = document.createElement('div'); body.className = 'advisor-result-body';
            const category = document.createElement('small'); category.textContent = item.category;
            const title = document.createElement('h4'); title.textContent = item.title;
            const capacity = document.createElement('span'); capacity.className = 'advisor-result-capacity'; capacity.textContent = item.capacity || 'Caractéristiques sur la fiche';
            const reason = document.createElement('p'); reason.className = 'advisor-result-reason'; reason.textContent = item.reason;
            body.append(category,title,capacity,reason);
            if (item.reason.length > 260) {
                reason.classList.add('advisor-reason-collapsed');
                const expand = document.createElement('button'); expand.type = 'button'; expand.className = 'advisor-reason-toggle'; expand.textContent = 'Lire l’explication'; expand.setAttribute('aria-expanded','false');
                expand.addEventListener('click', () => { const collapsed = reason.classList.toggle('advisor-reason-collapsed'); expand.setAttribute('aria-expanded', String(!collapsed)); expand.textContent = collapsed ? 'Lire l’explication' : 'Réduire'; });
                body.append(expand);
            }
            const footer = document.createElement('div'); footer.className = 'advisor-result-footer';
            const price = document.createElement('div'); price.className = 'advisor-result-price';
            const amount = document.createElement('strong'); amount.textContent = item.price;
            const unit = document.createElement('span'); unit.textContent = ' / jour'; price.append(amount,unit);
            const link = document.createElement('a'); link.href = item.url; link.textContent = 'Voir la fiche ↗';
            footer.append(price,link); body.append(footer); card.append(visual,body); results.append(card);
        }
    } catch (error) {status.textContent = error.name === 'AbortError' ? 'La recherche prend trop de temps. Réessayez.' : error.message;}
    finally {clearTimeout(timer);button.disabled=false;button.textContent=buttonLabel;panel.removeAttribute('aria-busy');if(loading) loading.hidden=true;}
});

document.addEventListener('click', event => {
    const example = event.target.closest('.advisor-examples button[data-need]');
    if (!example) return;
    const input = example.closest('.advisor-interaction').querySelector('[name="need"]');
    input.value = example.dataset.need;
    input.focus({preventScroll:true});
});
