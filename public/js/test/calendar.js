/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('[data-toggle="popover"]').popover({
        html: true,
        trigger: 'hover',
        placement: 'top',
        customClass: 'popover-w-100',
        content: function () {
            const content = $(this).data("html");
            return $(content);
        },
    });
}(jQuery));
