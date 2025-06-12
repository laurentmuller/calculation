/* globals Toaster */

/**
 * @typedef CopyEvent
 * @type {object}
 * @property {function} clearSelection
 */

/**
 * @return {string}
 */
function getTitle() {
    'use strict';
    return $('.card-title').text().trim();
}

/**
 * @return {string}
 */
function getMessage() {
    'use strict';
    return $('#error-message').text().trim();
}

/**
 * @return {string|null}
 */
function getException() {
    'use strict';
    const table = document.getElementById('exception-table');
    if (!table || !table.checkVisibility()) {
        return null;
    }

    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(table);
    selection.removeAllRanges();
    selection.addRange(range);
    // const text = selection.toString().trim();
    // selection.removeAllRanges();
    // return text;
    return selection.toString().trim();
}

/**
 * @param {string} type
 * @param {string} message
 */
function notify(type, message) {
    'use strict';
    Toaster.notify(type, message, getTitle());
}

/**
 * @param {CopyEvent} e
 */
function copySuccess(e) {
    'use strict';
    e.clearSelection();
    const message = $('.btn-copy').data('success');
    notify(Toaster.NotificationTypes.SUCCESS, message);
}

/**
 * @param {CopyEvent} e
 */
function copyError(e) {
    'use strict';
    e.clearSelection();
    const message = $('.btn-copy').data('error');
    notify(Toaster.NotificationTypes.ERROR, message);
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    if (ClipboardJS && ClipboardJS.isSupported('copy')) {
        const clipboard = new ClipboardJS('.btn-copy', {
            text: function () {
                const text = getTitle() + '\n\n' + getMessage();
                const exception = getException();
                if (exception) {
                    return text + '\n\n' + exception;
                }
                return text;
            }
        });
        clipboard.on('success', copySuccess);
        clipboard.on('error', copyError);
    } else {
        $('.btn-copy').fadeOut();
    }
});
