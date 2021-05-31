/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $tree = $('#tree');
    const view = $tree.boostrapTreeView({
        badgeCount: true,
        url: $tree.data('url'),
        loading: 'Recherche des données...'
    }).data('boostrapTreeView');
    $('.btn-expand-all').on('click', function () {
        view.expandAll().focus();
    });
    $('.btn-collapse-all').on('click', function () {
        view.collapseAll().focus();
    });
    $('.btn-expand-level').on('click', function () {
        view.expandToLevel(1).focus();
    });
    $('.btn-refresh').on('click', function () {
        view.refresh().focus();
    });
}(jQuery));
