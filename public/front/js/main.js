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
    const link = event.target.closest('#equipments .equipment-filters a');
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
        section.replaceWith(updated);
        updated.querySelectorAll('a').forEach(item => { if (item.textContent === link.textContent) item.focus({preventScroll: true}); });
    } catch (error) {
        section.style.opacity = '';
        section.removeAttribute('aria-busy');
        let notice = section.querySelector('[role="alert"]');
        if (!notice) { notice = document.createElement('p'); notice.className = 'text-danger text-center'; notice.setAttribute('role', 'alert'); section.prepend(notice); }
        notice.textContent = 'Impossible de charger les équipements. Veuillez réessayer.';
    }
});
