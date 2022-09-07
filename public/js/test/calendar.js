/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('[data-toggle="popover"]').popover({
        html: true,
        trigger: 'hover',
        placement: 'auto',
        customClass: 'popover-light popover-w-100 bg-themed',
        content: function () {
            const content = $(this).data("html");
            return $(content);
        },
    });
}(jQuery));
