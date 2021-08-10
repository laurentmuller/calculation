/**! compression tag for ftp-deployment */

/**
 * Format the country entry.
 *
 * @param {Object}
 *            country - the country data.
 * @returns the formated country.
 */
function formatCountry(country) {
    'use strict';
    const id = country.id;
    const text = country.text;
    if (!id) {
        return text;
    }

    const $img = $('<img/>', {
        'class': 'mr-1 flag flag-' + id.toLowerCase(),
        'src': $('#country').data('url'),
        'alt': ''
    });
    const $span = $('<span/>', {
        'class': 'text-truncate',
        'text': text
    });
    return $span.prepend($img);
}

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

    $tree.on('collapseall', function (e) {
        console.log('Collapse All', e.items);
    }).on('expandall', function (e) {
        console.log('Expand All', e.items);
    }).on('expandtolevel', function (e) {
        console.log('Expand to Level', e.level, e.items);
    }).on('togglegroup', function (e) {
        console.log('Toggle Group', 'expanding:' + e.expanding, e.item);
    });

    // countries
    $('#country').initSelect2({
        templateSelection: formatCountry,
        templateResult: formatCountry
    }).val('CH').trigger('change');

}(jQuery));
