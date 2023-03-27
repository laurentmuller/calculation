/**! compression tag for ftp-deployment */

/* globals ClipboardJS */

function handleCopy(e) {
    'use strict';
    $(e.trigger).parents('.modal').modal('hide');
    e.clearSelection();
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    if (ClipboardJS.isSupported('copy')) {
        const clipboard = new ClipboardJS('.btn-copy');
        clipboard.on('success', function (e) {
            handleCopy(e);
        }).on('error', function (e) {
            handleCopy(e);
        });
    } else {
        $('.btn-copy').remove();
    }
    $('.modal').on('hide.bs.modal', function (e) {
        $('.pre-scrollable-highlight').scrollTop(0);
    });

}(jQuery));
