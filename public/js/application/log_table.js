/**! compression tag for ftp-deployment */

/**
 * Handle the channel input
 */
function channelCallback(column) {
    'use strict';

    const $channel = $('#channel');
    const value = $channel.val();
    if (column.search() !== value) {
        column.search(value).draw();
        $channel.updateTimer(function () {
            $channel.focus();
        }, 500);
    }
}

/**
 * Handle the level input
 */
function levelCallback(column) {
    'use strict';

    const $level = $('#level');
    const value = $level.val();
    if (column.search() !== value) {
        column.search(value).draw();
        $level.updateTimer(function () {
            $level.focus();
        }, 500);
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // get columns
    const table = $('#data-table').dataTable().api();
    const channel = table.column(2);
    const level = table.column(3);

    $('#channel').val(channel.search()).on('input', function () {
        $(this).updateTimer(channelCallback, 250, channel);
    }).handleKeys();

    $('#level').val(level.search()).on('input', function () {
        $(this).updateTimer(levelCallback, 250, level);
    }).handleKeys();
});
