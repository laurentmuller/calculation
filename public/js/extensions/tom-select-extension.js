/**
 * -------------- TomSelect Extensions --------------
 */

/* global TomSelect */
/**
 * Update id attributes.
 */
function updateTomSelectIdentifiers() {
    'use strict';
    $('.items-placeholder, .dropdown-input').each(function (index, element) {
        $(element).attr('id', `tom-select-${index}`);
    });
}

/**
 * Update attribute for labels.
 * @param {string} id the target identifier.
 */
function updateTomSelectLabel(id) {
    'use strict';
    if (id.startsWith('#')) {
        id = id.slice(1);
    }
    const selector = `[for='${id}-ts-control']`;
    $(selector).attr('for', id);
}

// /**
//  * Add a label for the search input.
//  * @param {string} id the target identifier.
//  */
// function addMissingFormLabel(id) {
//     if (id.startsWith('#')) {
//         id = id.slice(1);
//     }
//     const select = document.getElementById(id);
//     const labelText = $(select).prevAll('label').first().text();
//
//     const hiddenLabel = document.createElement('label');
//     hiddenLabel.setAttribute('for', id);
//     hiddenLabel.className = 'visually-hidden';
//     hiddenLabel.textContent = labelText;
//
//     select.insertAdjacentElement('beforebegin', hiddenLabel);
// }

/**
 * Initialize a tom select.
 * @param {string} id the control selector.
 * @param {Object} options the tom select options.
 * @param {boolean} dropdownInput true to add the dropdown input plugin.
 * @return {TomSelect}
 */
function initSingleTomSelect(id, options = {}, dropdownInput = true) {
    const node = document.querySelector(id);
    const isRequired = node && node.required !== undefined && node.required === true;

    options.maxItems = 1;
    options.plugins = options.plugins || {};
    if (isRequired) {
        options.plugins['no_backspace_delete'] = {};
    }
    if (dropdownInput) {
        options.plugins['dropdown_input'] = {};
    }
    // options.onInitialize = function () {
    //     .items-placeholder, .dropdown-input
    //     const element = document.querySelector(id);
    //     const text = element.parentElement.querySelector('label').textContent;
    //     const input = element.parentElement.querySelector('.items-placeholder')
    //     const label = document.createElement('label');
    //     input.id = 'fake';
    //     label.className = 'visually-hidden';
    //     label.textContent = text;
    //     label.htmlFor = input.id;
    //     input.insertAdjacentElement('beforebegin', label);
    // };
    return new TomSelect(id, options);
}

/**
 * Initialize a tom select.
 * @param {string} id the control selector.
 * @param {Object} options the tom select options.
 * @param {boolean} dropdownInput true to add the dropdown input plugin.
 * @param {string} [removeButton] a text used to remove a selected item or null to disable.
 * @return {TomSelect}
 */
function initMultipleTomSelect(id, options = {}, dropdownInput = true, removeButton) {
    const node = document.querySelector(id);
    const isRequired = node && node.required !== undefined && node.required === true;
    const isMultiple = node && node.multiple !== undefined && node.multiple === true;

    options.plugins = options.plugins || {};
    if (isRequired) {
        options.plugins['no_backspace_delete'] = {};
    }
    if (isMultiple) {
        options.plugins['remove_button'] = {};
    }
    if (dropdownInput) {
        options.plugins['dropdown_input'] = {};
    }
    if (removeButton) {
        options.plugins['remove_button'] = {
            title: removeButton,
        };
    }
    // options.onInitialize = function () {
    //     updateTomSelectLabel(id);
    // };
    return new TomSelect(id, options);
}
