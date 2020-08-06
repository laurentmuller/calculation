/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * Override clear search
 */
const noConflict = clearSearch;
clearSearch = function ($element, table, callback) { // jshint ignore:line
    'use strict';

    const $category = $('#category');
    if ($category.val() !== '') {
        table.column(6).search('');
        $category.val('');
        if (!noConflict($element, table, callback)) {
            table.draw();
        }
    } else {
        noConflict($element, table, callback);
    }
};

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize search columns
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#category'), 6);
});
