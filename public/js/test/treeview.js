/* global bootstrap, TomSelect */

/**
 * Format the country entry.
 *
 * @param {Object} country
 * @param {function} escape
 */
function formatCountry(country, escape) {
    'use strict';
    const value = country.value;
    const text = escape(country.text);
    const path = $('#country').data('path');
    const src = `${path}${value.toUpperCase()}.svg`;
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
    }).prepend($img);

    return $('<div/>').append($text);
}

/**
 * Format a state.
 * @param {Object} state
 * @param {function} escape
 */
function formatState(state, escape) {
    'use strict';
    return $('<div/>').append(formatStateSelection(state, escape));
}

/**
 * Format a selected state.
 * @param {Object} state
 * @param {function} escape
 */
function formatStateSelection(state, escape) {
    'use strict';
    const value = state.value;
    const $color = $('<span/>', {
        'class': `me-1 border border-secondary state-color state-background-${value}`,
    });
    return $('<span/>', {
        'text': escape(state.text)
    }).prepend($color);
}

/**
 * Format a currency.
 * @param {Object} currency
 * @param {function} escape
 */
function formatCurrency(currency, escape) {
    'use strict';
    const value = currency.value.toLowerCase();
    const $flag = $('<span/>', {
        'class': 'me-1 currency-flag currency-flag-' + value,
    });
    const $text = $('<span/>', {
        'class': 'text-truncate',
        'text': escape(currency.text)
    });
    const $div = $('<div/>', {
        'class': 'd-inline-flex align-items-center w-100'
    });
    return $div.append($flag).append($text);
}

/**
 * Format a category.
 * @param {Object} category
 * @param {function} escape
 */
function formatCategory(category, escape) {
    'use strict';
    return $('<div/>').append(formatCategorySelection(category, escape));
}

/**
 * Format the selected category.
 * @param {Object} category
 * @param {function} escape
 */
function formatCategorySelection(category, escape) {
    'use strict';
    const $icon = $('<i/>', {
        'class': 'far fa-folder me-1'
    });

    return $('<span/>', {
        'text': escape(category.text)
    }).prepend($icon);
}

/**
 * Format a product.
 * @param {Object} product
 * @param {function} escape
 */
function formatProduct(product, escape) {
    'use strict';
    const text = escape(product.text);
    const dataset = product.$option.dataset;
    const price = $.parseFloat(dataset.price);
    const priceClass = price ? '' : 'text-danger';
    const priceText = $.formatFloat(price);
    const unit = dataset.unit ? `<span>&nbsp;/&nbsp;${dataset.unit}</span>` : '';

    return `<div class="d-flex w-100">
                <span class="me-auto text-truncate">${text}</span>
                <span class="${priceClass}">${priceText}</span>
                <span>${unit}</span>                                            
            </div>`;
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

    // country
    initSingleTomSelect('#country', {
        items: ['CH'],
        render: {
            item: (data, escape) => formatCountry(data, escape),
            option: (data, escape) => formatCountry(data, escape),
        }
    });

    // product
    initSingleTomSelect('#product', {
        render: {
            item: (data, escape) => formatProduct(data, escape),
            option: (data, escape) => formatProduct(data, escape),
        }
    })

    // currency
    initSingleTomSelect('#currency', {
        items: ['CHF'],
        render: {
            item: (data, escape) => formatCurrency(data, escape),
            option: (data, escape) => formatCurrency(data, escape),
        }
    });

    // categories
    initMultipleTomSelect('#category', {
        items: [4, 6],
        dropdownInput: true,
        removeButton: 'Supprimer la catégorie',
        render: {
            item: (data, escape) => formatCategorySelection(data, escape),
            option: (data, escape) => formatCategory(data, escape),
        }
    });

    // states
    initMultipleTomSelect('#state', {
        items: [1, 5],
        dropdownInput: false,
        removeButton: 'Supprimer le statut',
        render: {
            item: (data, escape) => formatStateSelection(data, escape),
            option: (data, escape) => formatState(data, escape),
        }
    });

    initSingleTomSelect('#state-single', {
        dropdownInput: false,
        render: {
            item: (data, escape) => formatStateSelection(data, escape),
            option: (data, escape) => formatState(data, escape),
        }
    });

    // open button
    $('.btn-tom-select-open').on('click', function () {
        const $input = $(this).siblings('select, input');
        if (!$input.length) {
            return;
        }
        const tomSelect = $input[0].tomselect;
        if (!tomSelect) {
            return;
        }
        tomSelect.open();
    });
    // clear button
    $('.btn-tom-select-clear').on('click', function () {
        const $input = $(this).siblings('select, input');
        if (!$input.length) {
            return;
        }
        const tomSelect = $input[0].tomselect;
        if (!tomSelect) {
            return;
        }
        tomSelect.clear(false);
    });
});

/**
 * @param {HTMLInputElement[]} radios
 * @param {HTMLInputElement} targetRadio
 * @param {boolean} moveFocus
 */
const selectRadio = (radios, targetRadio, moveFocus = false) => {
    radios.forEach(radio => {
        const isSelected = radio === targetRadio;
        radio.checked = isSelected;
        radio.setAttribute('tabindex', isSelected ? '0' : '-1');
    });

    if (moveFocus) {
        targetRadio.focus();
    }
    targetRadio.dispatchEvent(new CustomEvent('three-state-change'));
};

const initializeThreeState = () => {
    document.querySelectorAll('.three-state').forEach(function (parent) {
        /** @type {HTMLInputElement[]} */
        const radios = Array.from(parent.querySelectorAll('input[type="radio"]'));
        radios.forEach(radio => {
            radio.addEventListener('change', () => selectRadio(radios, radio, false));
        });
        parent.addEventListener('keydown', (e) => {
            const currentIndex = radios.findIndex(radio => radio === document.activeElement);
            if (currentIndex === -1) {
                return;
            }
            let nextIndex = null;
            switch (e.key) {
                case 'ArrowRight':
                case 'ArrowDown':
                    nextIndex = (currentIndex + 1) % radios.length;
                    break;
                case 'ArrowLeft':
                case 'ArrowUp':
                    nextIndex = (currentIndex - 1 + radios.length) % radios.length;
                    break;
                case 'Home':
                    nextIndex = 0;
                    break;
                case 'End':
                    nextIndex = radios.length - 1;
                    break;
            }
            if (nextIndex !== null) {
                e.preventDefault();
                selectRadio(radios, radios[nextIndex], true);
            }
        });
    });
}

const initThemeHandler = () => {
    // initialize theme three-state
    const THEME_AUTO = 'auto';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

    const parent = document.querySelector('.three-state-theme');
    const radios = Array.from(parent.querySelectorAll('input[type="radio"]'));
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    const savedTheme = localStorage.getItem('theme') || THEME_AUTO;
    const applyTheme = (theme) => {
        const resolved = theme === THEME_AUTO ? (mediaQuery.matches ? THEME_DARK : THEME_LIGHT) : theme;
        document.documentElement.setAttribute('data-bs-theme', resolved);
    };

    // update theme
    radios.forEach(radio => {
        radio.addEventListener('three-state-change', () => {
            localStorage.setItem('theme', radio.value);
            applyTheme(radio.value);
        });
    });
    const initialRadio = radios.find(radio => radio.value === savedTheme) || radios[1];
    // default selection
    selectRadio(radios, initialRadio, false);

    // synchronize with OS theme changes when set to 'auto'
    mediaQuery.addEventListener('change', () => {
        if ((localStorage.getItem('theme') || THEME_AUTO) === THEME_AUTO) {
            applyTheme(THEME_AUTO);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeThreeState();
    initThemeHandler();
    $('.three-state [data-bs-toggle="popover"]').popover();
});
