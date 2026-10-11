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

// Filter equipment on the homepage without navigating away.
document.addEventListener('click', async function (event) {
    const link = event.target.closest('#equipments .equipment-filters a, #equipments .equipment-pagination a');
    if (!link || window.location.pathname !== '/' || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    const section = document.getElementById('equipments');
    if (section.getAttribute('aria-busy') === 'true') return;
    section.setAttribute('aria-busy', 'true');
    section.style.opacity = '0.6';
    try {
        const response = await fetch(link.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        if (!response.ok) throw new Error('Filter unavailable');
        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const updated = parsed.getElementById('equipments');
        if (!updated) throw new Error('Missing equipment list');
        const oldAdvisor = section.querySelector('.solar-advisor');
        const newAdvisor = updated.querySelector('.solar-advisor');
        if (oldAdvisor && newAdvisor) newAdvisor.replaceWith(oldAdvisor);
        section.replaceWith(updated);
        if (link.closest('.equipment-pagination')) updated.querySelector('.equipment-filters').scrollIntoView({behavior:'smooth', block:'start'});
        updated.querySelectorAll('a').forEach(item => { if (item.textContent === link.textContent) item.focus({preventScroll: true}); });
    } catch (error) {
        section.style.opacity = '';
        section.removeAttribute('aria-busy');
        let notice = section.querySelector('[role="alert"]');
        if (!notice) { notice = document.createElement('p'); notice.className = 'text-danger text-center'; notice.setAttribute('role', 'alert'); section.prepend(notice); }
        notice.textContent = 'Impossible de charger les équipements. Veuillez réessayer.';
    }
});

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
    button.disabled = true;
    status.textContent = 'Nous explorons le catalogue pour votre projet…';
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 55000);
    try {
        const response = await fetch(form.action, {method:'POST',body:new FormData(form),signal:controller.signal,headers:{Accept:'application/json'}});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(response.status === 429 ? 'Trop de recherches. Patientez une minute.' : response.status === 419 ? 'Votre session a expiré. Rechargez la page.' : Object.values(data.errors || {}).flat()[0] || data.message || 'Recherche indisponible. Réessayez.');
        status.textContent = data.items.length ? 'Voici les pistes qui correspondent à votre besoin. Vérifiez les caractéristiques et les dates sur chaque fiche.' : 'Aucun équipement adapté trouvé dans le catalogue consulté. Essayez de préciser vos appareils ou explorez les catégories.';
        for (const item of data.items) {
            const card = document.createElement('article'); card.className = 'advisor-result';
            const category = document.createElement('small'); category.textContent = item.category;
            const title = document.createElement('h4'); title.textContent = item.title;
            const reason = document.createElement('p'); reason.textContent = item.reason;
            const link = document.createElement('a'); link.href = item.url; link.textContent = 'Voir la fiche · ' + item.price + ' / jour ↗';
            card.append(category,title,reason,link); results.append(card);
        }
    } catch (error) {status.textContent = error.name === 'AbortError' ? 'La recherche prend trop de temps. Réessayez.' : error.message;}
    finally {clearTimeout(timer);button.disabled=false;}
});

document.addEventListener('click', event => {
    const example = event.target.closest('.advisor-examples button[data-need]');
    if (!example) return;
    const input = example.closest('.advisor-interaction').querySelector('[name="need"]');
    input.value = example.dataset.need;
    input.focus({preventScroll:true});
});
