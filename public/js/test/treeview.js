/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $tree = $('#tree');
    const treeView = $tree.boostrapTreeView().data('boostrapTreeView');
    $('.btn-expand-all').on('click', function () {
        treeView.expandAll().focus();
    });
    $('.btn-collapse-all').on('click', function () {
        treeView.collapseAll().focus();
    });
    $('.btn-expand-level').on('click', function () {
        treeView.expandToLevel(1).focus();
    });
    $('.btn-refresh').on('click', function () {
        treeView.refresh().focus();
    });

    $tree.on('collapseall', function(e) {
        console.log('Collapse All', e.items);
    }).on('expandall', function(e) {
        console.log('Expand All', e.items);
    }).on('expandtolevel', function(e) {
        console.log('Expand to Level', e.level, e.items);
    }).on('togglegroup', function(e) {
        console.log('Toggle Group', e.expanded, e.item);
    });

}(jQuery));
