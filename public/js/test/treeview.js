/* global bootstrap */

/**
 * Format the country entry.
 *
 * @param {Object} country - the country data.
 * @returns the formatted country.
 */
function formatCountry(country) {
    'use strict';
    const id = country.id;
    const text = country.text;
    if (!id) {
        return text;
    }

    const path = $('#country').data('path');
    const src = `${path}${id.toLowerCase()}.png`;
    const $img = $('<img/>', {
        class: 'country-flag me-1',
        alt: text,
        src: src,
        css: {
            width: '1rem',
            height: '1rem',
            'margin-bottom': '1px'
        }
    });
    const $text = $('<span/>', {
        class: 'text-truncate',
        text: text
    });
    return $text.prepend($img);
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
        'class': 'border border-secondary me-1',
        'css': {
            'background-color': color,
            'display': 'inline-block',
            'height': '0.75rem',
            'width': '1rem'
        }
    });
    const $text = $('<div/>', {
        'class': 'text-truncate',
        'text': text
    });
    return $text.prepend($color);
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
        'class': 'border border-secondary me-1',
        'css': {
            'background-color': color,
            'display': 'inline-block',
            'height': '0.75rem',
            'width': '1rem'
        }
    });
    const $text = $('<span/>', {
        'class': 'me-1',
        'text': text
    });
    return $text.prepend($color);
}

function formatCurrency(currency) {
    'use strict';
    const id = currency.id;
    const text = currency.text;
    if (!id) {
        return text;
    }
    const $flag = $('<span/>', {
        'class': 'me-1 currency-flag currency-flag-' + id.toLowerCase(),
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
        'class': 'far fa-folder me-1',
    });
    const $text = $('<span/>', {
        'class': 'text-truncate',
        'text': text
    });
    return $text.prepend($icon);
}

function formatCategorySelection(category) {
    'use strict';
    const id = category.id;
    const text = category.text;
    if (!id) {
        return text;
    }
    const $icon = $('<i/>', {
        'class': 'far fa-folder me-1',
    });
    const $text = $('<span/>', {
        'text': text,
        'class': 'mx-1'
    });
    return $text.prepend($icon);
}

function formatProduct(product) {
    'use strict';
    const id = product.id;
    let text = product.text;

    if (!id) {
        return text;
    }

    const price = $.parseFloat($(product.element).data('price'));
    const priceClass = price ? '' : 'text-danger';
    const priceText = $.formatFloat(price);

    let unit = $(product.element).data('unit');
    unit = unit ? `<span>&nbsp;/&nbsp;${unit}</span>` : '';

    return $(`<div class="d-flex w-100">
                <span class="me-auto text-truncate">${text}</span>
                <span class="${priceClass}">${priceText}</span>
                ${unit}                            
            </div>`);
}

function updatePosition($radio) {
    'use strict';
    const $button = $radio.parents('.dropdown').find('.dropdown-toggle');
    const $label = $radio.siblings('label');
    const value = $radio.val();
    const text = $label.attr('title');
    const icon = $label.html().trim();

    $button.data('value', value);
    $button.find('.position-icon').html(icon);
    $button.find('.position-text').text(text);
    $button.dropdown('hide');
    $button.trigger('focus');
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    const $tree = $('#tree');
    const treeView = $tree.boostrapTreeView({
        texts: {
            expand: 'Développer',
            collapse: 'Réduire'
        }
    }).data($.BoostrapTreeView.NAME);
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

    $tree.on('collapseall.bs.treeview', function (e) {
        window.console.log('Collapse All', e.items);
    }).on('expandall.bs.treeview', function (e) {
        window.console.log('Expand All', e.items);
    }).on('expandtolevel.bs.treeview', function (e) {
        window.console.log('Expand to Level', e.level, e.items);
    }).on('togglegroup.bs.treeview', function (e) {
        window.console.log('Toggle Group', 'expanding:' + e.expanding, e.item);
    });

    // countries
    $('#country').initSelect2({
        templateResult: formatCountry,
        templateSelection: formatCountry
    }).val('CH').trigger('change');

    // currency
    $('#currency').initSelect2({
        templateResult: formatCurrency,
        templateSelection: formatCurrency
    }).val('CHF').trigger('change');

    // states
    $('#state_single').initSelect2({
        templateResult: formatState,
        templateSelection: formatStateSelection,
        minimumResultsForSearch: Infinity
    });
    $('#state').initSelect2({
        templateResult: formatState,
        templateSelection: formatStateSelection,
    }).val([1, 5]).trigger('change');

    // product
    $('#product').initSelect2({
        templateResult: formatProduct,
        templateSelection: formatProduct,
    });

    // categories
    $('#category').initSelect2({
        templateResult: formatCategory,
        templateSelection: formatCategorySelection
    }).val([1, 2]).trigger('change');

    $('.btn-search').on('click', function () {
        $(this).parents('.form-group').find('select').select2('open');
    });
    $('.btn-clear').on('click', function () {
        $(this).parents('.form-group').find('select').val('').trigger('change').focus();
    });

    // drag modal
    $('#dragModal').draggableModal({
        marginBottom: $('footer:visible').length ? $('footer').outerHeight() : 0,
        focusOnShow: true
    }).on('shown.bs.modal', function () {
        $('#text').trigger('focus');
    });

    // position
    $('.dropdown-position .btn-position').on('show.bs.dropdown', function () {
        const $this = $(this);
        const value = $this.data('value');
        /** @type {jQuery|any} */
        const $parent = $(this).parents('.dropdown');
        //'.btn-check[value="' + value + '"]'
        const $radio = $parent.find(`.btn-check[value="${value}"]`);
        $radio.prop('checked', true);
    }).on('shown.bs.dropdown', function () {
        /** @type {jQuery|any} */
        const $parent = $(this).parents('.dropdown');
        $parent.find('.btn-check:checked').trigger('focus');
    });
    $('.dropdown-position label').on('click', function (e) {
        if (e.button === 0) {
            updatePosition($(this).siblings(':radio'));
        }
    });
    $('.dropdown-position .btn-check').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            updatePosition($(this));
        }
    });
    $('.dropdown-position').parent().find('.form-label').on('click', function () {
        $('.btn-position').trigger('focus');
    });

    const getItems = function () {
        return document.querySelectorAll('.dropdown-input .dropdown-menu .dropdown-item');
    };

    const input = document.querySelector('#first_name');
    input.setAttribute('data-bs-toggle', 'dropdown');
    input.setAttribute('aria-expanded', 'false');

    const dropdownMenu = document.createElement('ul');
    dropdownMenu.classList.add('dropdown-menu');
    input.after(dropdownMenu);
    for (let index = 1; index < 10; index++) {
        const liElement = document.createElement('li');
        const btnElement = document.createElement('button');
        btnElement.classList.add('dropdown-item');
        btnElement.setAttribute('role', 'button');
        btnElement.textContent = 'Item ' + index;
        liElement.append(btnElement);
        dropdownMenu.append(liElement);
    }

    let focused = false;
    const dropdown = new bootstrap.Dropdown(input);
    input.addEventListener('focus', () => focused = true);
    input.addEventListener('blur', () => focused = false);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') {
            focused = false;
            dropdown.show();
        }
    });
    input.addEventListener('show.bs.dropdown', (e) => {
        if (focused || getItems().length === 0) {
            e.preventDefault();
        }
    });
    input.addEventListener('shown.bs.dropdown', () => {
        getItems()[0].focus();
        focused = true;
    });

    getItems().forEach(function (item) {
        item.addEventListener('click', () => {
            input.value = item.innerText;
            item.removeEventListener('click', this);
            item.remove();
            dropdown.hide();
            input.select();
            input.focus();
        });
    });

    // rotate arrow icons
    $('.fa-rotate-by').each(function () {
        const $this = $(this);
        $this.css('--fa-rotate-angle', $this.data('rotate'));
    });
});
