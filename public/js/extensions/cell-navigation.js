/**! compression tag for ftp-deployment */

/**
 * -------------- jQuery Extensions --------------
 */
$.fn.extend({
    /**
     * Use the arrow keys to navigate among the input cells.
     */
    enableCellNavigation: function () {
        'use strict';
        
        const arrow = {
            left: 37,
            up: 38,
            right: 39,
            down: 40
        };

        // function getSelectionText() {
        // var element = document.activeElement;
        // var tagName = element ? element.tagName.toLowerCase() : null;
        // if (tagName === 'textarea' || tagName === 'input' &&
        // /^(?:text|search|password|tel|url)$/i.test(element.type) &&
        // typeof
        // element.selectionStart === 'number') {
        // return element.value.slice(element.selectionStart,
        // element.selectionEnd);
        // }
        // if (window.getSelection) {
        // return window.getSelection().toString();
        // }
        // return '';
        // }

        this.on('keydown', 'input', function (e) {
            // this.find('input').keydown(function (e) {
            // shortcut for key other than arrow keys
            if ($.inArray(e.which, [arrow.left, arrow.up, arrow.right, arrow.down]) < 0) {
                return;
            }

            let $moveTo = null;
            const input = e.target;
            const $cell = $(e.target).closest('td');

            switch (e.which) {
            case arrow.left: {
                if (input.selectionStart || 0 === 0) {
                    $moveTo = $cell.prev('td:has(input)');
                }
                break;
            }
            case arrow.right: {
                if (input.selectionEnd || input.value.length === input.value.length) {
                    $moveTo = $cell.next('td:has(input)');
                }
                break;
            }

            case arrow.up:
            case arrow.down: {
                var $moveToRow = null;
                const $row = $cell.closest('tr');
                const pos = $cell[0].cellIndex;
                if (e.which === arrow.down) {
                    $moveToRow = $row.next('tr');
                } else if (e.which === arrow.up) {
                    $moveToRow = $row.prev('tr');
                }
                if ($moveToRow.length) {
                    $moveTo = $($moveToRow[0].cells[pos]);
                }
                break;
            }
            }

            if ($moveTo && $moveTo.length) {
                e.preventDefault();
                $moveTo.find('input:first').select().focus();
                // $moveTo.find('input').each(function (index, input) {
                // input.focus().select();
                // });
            }
        });
        return this;
    }
});
