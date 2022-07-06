(function ($) {
    'use strict';
    const className = 'active';
    const $selector = $('.navbar-vertical, .page-content');
    $('#sidebarCollapse').on('click', function () {
        // toggle and save
        $selector.toggleClass(className);
        const url = $('.navbar-vertical').data('url');
        if (url) {
            const active = $selector.hasClass(className);
            $.post(url, {'active': active});
        }
    });
    $('.navbar-vertical .nav-link-toggle').on('click', function () {
        const $this = $(this);
        const $next = $this.next('ul.navbar-nav');
        $next.toggle();
        $this.attr('aria-expanded', $next.is(':visible'));
    });
}(jQuery));
