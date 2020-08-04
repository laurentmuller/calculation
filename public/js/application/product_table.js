/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize search columns
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#category'), 6);
});
