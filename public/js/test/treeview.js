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
        hoverClass: 'list-group-item-primary',
        templates: {
            item: '<button type="button" role="treeitem" class="list-group-item list-group-item-action text-left" data-toggle="collapse"></button>'
        }
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
}(jQuery));
