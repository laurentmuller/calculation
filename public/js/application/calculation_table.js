/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * Override clear search
 */
const noConflict = clearSearch;
clearSearch = function ($element, table, callback) { // jshint ignore:line
    'use strict';

    const $state = $('#state');
    if ($state.length && $state.val() !== '') {
        table.column(8).search('');
        $state.val('');
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
    const $state = $('#state');
    if ($state.length) {
        const table = $('#data-table').dataTable().api();
        table.initSearchColumn($state, 8);
    }
}(jQuery));
