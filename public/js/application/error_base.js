(function ($) {
    'use strict';
    /**
     * jQuery functions extensions
     */
    $.fn.extend({
        /** @return {string} */
        getTitle: function () {
            return $(this).find('.card-title').text().trim();
        },

        /** @return {string} */
        getMessage: function () {
            return $(this).find('#error-message').text().trim();
        },

        /** @return {?string} */
        getException: function () {
            const $table = $(this).find('#exception-table');
            if (!$table.length) {
                return null;
            }
            const table = $table[0];
            if (!table.checkVisibility()) {
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
    });

    /**
     * Ready function
     */
    $(function () {
        // clipboard
        $('.btn-copy').copyClipboard({
            text: function (element) {
                const $card = $(element).parents('.card');
                const text = $card.getTitle() + '\n\n' + $card.getMessage();
                const exception = $card.getException();
                if (exception) {
                    return text + '\n\n' + exception;
                }
                return text;
            }
        });
    });
}(jQuery));
