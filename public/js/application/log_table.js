/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * Override clear search
 */
const noConflict = clearSearch;
clearSearch = function ($element, table, callback) { // jshint ignore:line
    'use strict';

    const $channel = $('#channel');
    const $level = $('#level');
    if ($channel.val() || $level.val()) {
        table.column(2).search('');
        table.column(3).search('');
        $channel.val('');
        $level.val('');
        if (!noConflict($element, table, callback)) {
            table.draw();
            return false;
        }
        return true;
    } else {
        return noConflict($element, table, callback);
    }
};

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize search columns
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#channel'), 2);
    table.initSearchColumn($('#level'), 3);
}(jQuery));
