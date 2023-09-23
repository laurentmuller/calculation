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

        const Arrow = {
            Left: 'ArrowLeft',
            Up: 'ArrowUp',
            Right: 'ArrowRight',
            Down: 'ArrowDown'
        };

        /**
         * @param {KeyboardEvent} e - the event.
         */
        this.on('keydown', 'input', function (e) {
            // shortcut for key other than Arrow keys
            if ($.inArray(e.key, [Arrow.Left, Arrow.Up, Arrow.Right, Arrow.Down]) < 0) {
                return;
            }

            let $moveTo = null;
            const input = e.target;
            const $cell = $(e.target).closest('td');

            switch (e.key) {
                case Arrow.Left:
                    if (input.selectionStart || 0 === 0) {
                        $moveTo = $cell.prev('td:has(input)');
                    }
                    break;

                case Arrow.Right:
                    if (input.selectionEnd || input.value.length === input.value.length) {
                        $moveTo = $cell.next('td:has(input)');
                    }
                    break;

                case Arrow.Up:
                case Arrow.Down: {
                    let $moveToRow = null;
                    const $row = $cell.closest('tr');
                    const pos = $cell[0].cellIndex;
                    if (e.key === Arrow.Down) {
                        $moveToRow = $row.next('tr');
                    } else if (e.key === Arrow.up) {
                        $moveToRow = $row.prev('tr');
                    }
                    if ($moveToRow && $moveToRow.length) {
                        $moveTo = $($moveToRow[0].cells[pos]);
                    }
                    break;
                }
            }

            if ($moveTo && $moveTo.length) {
                e.preventDefault();
                $moveTo.find('input:first').select().focus();
            }
        });
        return this;
    }
});
