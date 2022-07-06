/**! compression tag for ftp-deployment */


/**
 * Save the menu state.
 */
function saveMenuState($selector) {
    'use strict';
    const url = $('.navbar-vertical').data('url');
    if (url) {
        //'ul.navbar-nav'
        // .navbar-vertical
        // .nav-item.nav-item-dropdown


        //
        $.post(url, {
            'active': $selector.hasClass('active')
        });
    }
}

(function ($) {
    'use strict';
    const className = 'active';
    const $selector = $('.navbar-vertical, .page-content');
    $('.sidebar-toggle').on('click', function () {
        // toggle and save
        $selector.toggleClass(className);
        saveMenuState($selector);
        // $('.navbar-vertical .nav-link-toggle').each(function() {
        //     const $this = $(this);
        //     const $next = $this.next('ul.navbar-nav');
        //     //.nav-item.nav-item-dropdown
        // });
    });
    $('.navbar-vertical .nav-link-toggle').on('click', function () {
        const $this = $(this);
        $this.toggleClass('nav-link-toggle-show');
        const $next = $this.next('ul.navbar-nav');
        $next.toggleClass('d-block d-none');

        //$next.toggle();
        $this.attr('aria-expanded', $next.is(':visible'));
        //saveMenuState($selector);
        $('.navbar-vertical .nav-item.nav-item-dropdown[id]').each(function () {
            window.console.log($(this).attr('id'));
        });
    });

    // collapse all menus
    $('.navbar-vertical .nav-link-collapse-all').on('click', function () {
        $('.navbar-vertical .nav-item-dropdown .nav-link-toggle.nav-link-toggle-show').removeClass('nav-link-toggle-show')
            .attr('aria-expanded', false);
        $('.navbar-vertical .nav-item-dropdown .navbar-nav.d-block').removeClass('d-block').addClass('d-none');
    });

    // expand all menus
    $('.navbar-vertical .nav-link-expand-all').on('click', function () {
        $('.navbar-vertical .nav-item-dropdown .nav-link-toggle').addClass('nav-link-toggle-show')
            .attr('aria-expanded', true);
        $('.navbar-vertical .nav-item-dropdown .navbar-nav.d-none').addClass('d-block').removeClass('d-none');
    });
}(jQuery));
