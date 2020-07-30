/**! compression tag for ftp-deployment */

/**
 * Handle the category input
 */
function categoryCallback($category, column) {
    'use strict';

    const value = $category.val();
    if (column.search() !== value) {
        column.search(value).draw();
        $category.updateTimer(function () {
            $category.focus();
        }, 500);
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    const $category = $('#category');
    const column = $('#data-table').dataTable().api().column(6);
    $category.val(column.search()).on('input', function () {
        $(this).updateTimer(categoryCallback, 250, $category, column);
    }).handleKeys();
});
