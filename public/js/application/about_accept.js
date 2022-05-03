/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $close = $('#cookie-banner-close');
    if ($close.length && $.cookiebanner) {
        $('.btn-accept').on('click', function () {
            // simulate the close button click
            $close.trigger('click');
            // goto home page
            const target = $(this).data('target');
            if (target) {
                window.location.assign(target);
            }
        });
    }
}(jQuery));
