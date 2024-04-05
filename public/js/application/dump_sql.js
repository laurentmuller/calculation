/**! compression tag for ftp-deployment */

/* globals Toaster, ClipboardJS */

/**
 * Display a notification.
 *
 * @param {string} type - the notification type.
 * @param {string} message - the notification message.
 */
function notify(type, message) {
    'use strict';
    const title = $('.card-title').text();
    Toaster.notify(type, message, title);
}

/**
 * Handle success copy event.
 */
function onCopySuccess(e) {
    'use strict';
    e.clearSelection();
    const message = $('.btn-copy').data('success');
    notify(Toaster.NotificationTypes.SUCCESS, message);
}

/**
 * Handle the error copy event.
 */
function onCopyError(e) {
    'use strict';
    e.clearSelection();
    const message = $('.btn-copy').data('error');
    notify(Toaster.NotificationTypes.WARNING, message);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    // clipboard
    if (ClipboardJS && ClipboardJS.isSupported('copy')) {
        const clipboard = new ClipboardJS('.btn-copy');
        clipboard.on('success', (e) => onCopySuccess(e));
        clipboard.on('error', (e) => onCopyError(e));
    } else {
        $('.btn-copy').remove();
    }
}(jQuery));
