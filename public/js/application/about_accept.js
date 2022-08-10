/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    if ($.cookiebanner) {
        $('.btn-accept').on('click', function () {
            $.cookiebanner.accept();
            const target = $(this).data('target');
            if (target) {
                window.location.assign(target);
            }
        });
    }
}(jQuery));
