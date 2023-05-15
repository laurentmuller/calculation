/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('[data-bs-toggle="popover"]').popover({
        html: true,
        trigger: 'hover',
        placement: 'top',
        fallbackPlacements: ['top', 'bottom', 'right', 'left'],
        customClass: 'popover-primary popover-table popover-w-100',
        content: function (e) {
            const content = $(e).data("content");
            return $(content);
        },
    });
}(jQuery));
