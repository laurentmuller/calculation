/**! compression tag for ftp-deployment */

/**
 * Handle the state input
 */
function stateCallback($state, column) {
    'use strict';

    const value = $state.val();
    if (column.search() !== value) {
        column.search(value).draw();
        $state.updateTimer(function () {
            $state.focus();
        }, 500);
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    const $state = $('#state');
    const column = $('#data-table').dataTable().api().column(8);
    $state.val(column.search()).on('input', function () {
        $(this).updateTimer(stateCallback, 250, $state, column);
    }).handleKeys();
});
