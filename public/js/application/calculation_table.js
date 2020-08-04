/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize search columns
    const $state = $('#state');
    if ($state.length) {
        const table = $('#data-table').dataTable().api();
        table.initSearchColumn($state, 8);    
    }
});
