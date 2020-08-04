/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize search columns
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#channel'), 2);
    table.initSearchColumn($('#level'), 3);
});
