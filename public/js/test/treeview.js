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
    const url = $('#country').data('url');
    const className = 'flag flag-' + id.toLowerCase();
    return $('<span><img src="' + url + '" class="' + className + '" alt="' + text + '"> ' + text + '</span>');
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
    const $country = $('#country');
    const countries = $country.data('countries');
    const data = Object.keys(countries).map(function (key) {
        return {
            id: key,
            text: countries[key]
        };
    });
    $country.removeAttr('data-countries').initSelect2({
        data: data,
        templateResult: formatCountry,
        templateSelection: formatCountry
    }).val('CH').trigger('change');

}(jQuery));
