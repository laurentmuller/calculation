/* globals Toaster, ClipboardJS */

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
    const text = selection.toString().trim();
    selection.removeAllRanges();
    return text;
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
 * Ready function
 */
$(function () {
    'use strict';
    // clipboard
    $('.btn-copy').copyClipboard({
        text: function () {
            const text = getTitle() + '\n\n' + getMessage();
            const exception = getException();
            if (exception) {
                return text + '\n\n' + exception;
            }
            return text;
        }
    });
});
