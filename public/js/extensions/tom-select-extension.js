/**
 * -------------- TomSelect Extensions --------------
 */

/* global TomSelect */

/**
 * Update the tom-select.
 * @param  {string} id the target identifier.
 */
const updateTomSelect = (id) => {
    'use strict';
    if (id.startsWith('#')) {
        id = id.slice(1);
    }

    // update label attribute
    const selector = `[for='${id}-ts-control']`;
    const label = document.querySelector(selector);
    if (label) {
        label.setAttribute('for', id);
    }

    // update identifiers
    const element = document.getElementById(id);
    if (!element) {
        return;
    }
    const prefix = `${id.snakeCase()}_tom_select_`;
    const inputs = element.parentElement.querySelectorAll('.items-placeholder, .dropdown-input');
    inputs.forEach(function (element, key) {
        element.id = element.name = `${prefix}${key}`;
    });
}

/**
 * Initialize a tom select with a single selection.
 * @param {string} id the control selector.
 * @param {Object} options the initial options.
 * @return {TomSelect}
 */
const initSingleTomSelect = (id, options = {}) => {
    const node = document.querySelector(id);
    const isRequired = node && (node.required || false);
    const dropdownInput = options.dropdownInput || true;

    options.maxItems ??= 1;
    options.maxOptions ??= null;
    options.plugins ??= {};

    if (isRequired) {
        options.plugins['no_backspace_delete'] = {};
    }
    if (dropdownInput) {
        options.plugins['dropdown_input'] = {};
    }
    options.onInitialize = function () {
        updateTomSelect(id);
    };
    return new TomSelect(id, options);
}

/**
 * Initialize a tom select with multiple selection.
 * @param {string} id the control selector.
 * @param {Object} options the initial options.
 * @return {TomSelect}
 */
const initMultipleTomSelect = (id, options = {}) => {
    const node = document.querySelector(id);
    const isRequired = node && (node.required || false);
    const isMultiple = node && (node.multiple || false);
    const dropdownInput = options.dropdownInput || true;

    options.plugins ??= {};
    if (isRequired) {
        options.plugins['no_backspace_delete'] = {};
    }
    if (isMultiple) {
        options.plugins['remove_button'] = {};
    }
    if (dropdownInput) {
        options.plugins['dropdown_input'] = {};
    }
    if (options.removeButton) {
        options.plugins['remove_button'] = {
            title: options.removeButton,
        };
    }
    options.onInitialize = function () {
        updateTomSelect(id);
    };
    return new TomSelect(id, options);
}
