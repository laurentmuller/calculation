/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('[data-bs-toggle="popover"]').popover({
        html: true,
        trigger: 'hover',
        placement: 'auto',
        customClass: 'popover-primary popover-w-100',
        content: function (e) {
            const content = $(e).data("html");
            return $(content);
        },
    });
}(jQuery));
