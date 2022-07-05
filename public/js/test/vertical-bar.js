(function ($) {
    'use strict';
    $('#sidebarCollapse').on('click', function () {
        $('.navbar-vertical, .page-content').toggleClass('active');
    });
    $('.navbar-vertical .nav-link-toggle').on('click', function () {
        const $this = $(this);
        const $next = $this.next('ul.navbar-nav');
        $next.toggle();
        //$next.toggleClass('show');
        //$this.parents('.nav-item-dropdown').toggleClass('show');
        $this.attr('aria-expanded', $next.is(':visible'));
        // if ($next.is(':visible')) {
        //     $next.hide();
        //     $this.attr('aria-expanded', false);
        // } else {
        //     $next.show();
        //     $this.attr('aria-expanded', true);
        // }
        //$(this).next('ul').toggle();
    });
}(jQuery));
