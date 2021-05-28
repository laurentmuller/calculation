/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $tree = $('#tree');
    $tree.boostrapTreeView({
        badgeCount: true,
        url: $tree.data('url'),
        loading: 'Recherche des données...'
    });
    $('.btn-expand-all').on('click', function () {
        $tree.data('boostrapTreeView').expandAll();
    });
    $('.btn-collapse-all').on('click', function () {
        $tree.data('boostrapTreeView').collapseAll();
    });
    $('.btn-expand-level').on('click', function () {
        $tree.data('boostrapTreeView').expandToLevel(1);
    });
    $('.btn-refresh').on('click', function () {
        $tree.data('boostrapTreeView').refresh();
    });
}(jQuery));
