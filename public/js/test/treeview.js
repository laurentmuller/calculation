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

function formatState(state) {
    'use strict';
    const id = state.id;
    const text = state.text;
    if (!id) {
        return text;
    }
    const color = $(state.element).data('color');
    const $color = $('<span/>', {
        'class': 'border border-secondary mr-1',
        'css': {
            'background-color': color,
            'display': 'inline-block',
            'height': '0.75rem',
            'width': '1rem'
        }
    });
    const $span = $('<div/>', {
        'class': 'text-truncate',
        'text': text
    });
    return $span.prepend($color);
}

function formatStateSelection(state) {
    'use strict';
    const id = state.id;
    const text = state.text;
    if (!id) {
        return text;
    }
    const color = $(state.element).data('color');
    const $color = $('<span/>', {
        'class': 'border border-secondary mr-1',
        'css': {
            'background-color': color,
            'display': 'inline-block',
            'height': '0.75rem',
            'width': '1rem'
        }
    });
    const $span = $('<span/>', {
        'class': 'mx-1',
        'text': text
    });
    return $span.prepend($color);
}

function formatCurrency(currency) {
    'use strict';
    const id = currency.id;
    const text = currency.text;
    if (!id) {
        return text;
    }

    const $flag = $('<span/>', {
        'class': 'mr-1 currency-flag currency-flag-' + id.toLowerCase(),
    });
    const $text = $('<span/>', {
        'class': 'text-truncate',
        'text': text
    });
    const $div = $('<div/>', {
        'class': 'd-inline-flex align-items-center w-100'
    });
    return $div.append($flag).append($text);
}

function formatCategory(category) {
    'use strict';
    const id = category.id;
    const text = category.text;
    if (!id) {
        return text;
    }

    const $icon = $('<i/>', {
        'class': 'fas fa-folder fa-fw',
    });
    const $span = $('<span/>', {
        'class': 'text-truncate',
        'text': text
    });

    return $span.prepend($icon);
}

function formatCategorySelection(category) {
    'use strict';
    const id = category.id;
    const text = category.text;
    if (!id) {
        return text;
    }

    const $icon = $('<i/>', {
        'class': 'fas fa-folder fa-fw',
    });
    const $span = $('<span/>', {
        'text': text,
        'class': 'mx-1'
    });

    return $span.prepend($icon);
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

    // currency
    $('#currency').initSelect2({
        templateSelection: formatCurrency,
        templateResult: formatCurrency
    }).val('CHF').trigger('change');

    // states
    $('#state').initSelect2({
        templateResult: formatState,
        templateSelection: formatStateSelection,
        minimumResultsForSearch: Infinity
    }).val(['1', '5']).trigger('change');

    // categories
    $('#category').initSelect2({
        templateResult: formatCategory,
        templateSelection: formatCategorySelection
    }).val(['1', '2', '3', '4', '5', '6']).trigger('change');

    $('.btn-search').on('click', function () {
        $(this).parents('.form-group').find('select').select2('open');
    });
    $('.btn-clear').on('click', function () {
        $(this).parents('.form-group').find('select').val('').trigger('change').focus();
    });

}(jQuery));
