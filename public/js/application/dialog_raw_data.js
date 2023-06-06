/**! compression tag for ftp-deployment */

/* globals ClipboardJS, Toaster */

/**
 * Notify a message.
 *
 * @param {string} type - the message type.
 * @param {string} message - the message content.
 * @param {string} title - the message title.
 */
function notify(type, message, title) {
    'use strict';
    Toaster.notify(type, message, title);
}

/**
 * Handle success copy event.
 */
function onCopySuccess(e) {
    'use strict';
    e.clearSelection();
    const $modal = $(e.trigger).parents('.modal-raw-data');
    const title = $modal.find('.dialog-title').text();
    const message = $modal.data('copy-success');
    $modal.modal('hide');
    notify(Toaster.NotificationTypes.SUCCESS, message, title);
}

/**
 * Handle the error copy event.
 */
function onCopyError(e) {
    'use strict';
    e.clearSelection();
    const $button = $(e.trigger);
    const $modal = $button.parents('.modal-raw-data');
    const title = $modal.find('.dialog-title').text();
    const message = $modal.data('copy-error');
    $modal.modal('hide');
    $button.remove();
    notify(Toaster.NotificationTypes.WARNING, message, title);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const selector = '.modal-raw-data .btn-copy';
    const $button = $(selector);
    if ($button.length && ClipboardJS && ClipboardJS.isSupported('copy')) {
        const clipboard = new ClipboardJS(selector);
        clipboard.on('success', function (e) {
            onCopySuccess(e);
        }).on('error', function (e) {
            onCopyError(e);
        });
    } else {
        $button.remove();
    }
    $('.modal-raw-data').on('hide.bs.modal', function () {
        $(this).find('.pre-scrollable').scrollTop(0);
    });
}(jQuery));
