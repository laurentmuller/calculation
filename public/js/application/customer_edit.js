
/**
 * @typedef {Object} ItemType
 * @property {string} street
 * @property {string} city
 * @property {string} zip
 */

/**
 * ready function
 */
$(function () {
    'use strict';
    // get controls
    const $form = $('#edit-form');
    const $title = $('#customer_title');
    const $address = $('#customer_address');
    const $zip = $('#customer_zipCode');
    const $city = $('#customer_city');
    const addressUrl = String($form.data('search-address'));

    // default typeahead options
    const defaultOptions = {
        alignWidth: false,
        displayField: 'display',
        ajax: {
            triggerLength: 2
        },
        error: String($form.data('error'))
    };

    // title typeahead
    /** @type {Object} */
    const titleOptions = $.extend({}, defaultOptions, {
        url: String($form.data('search-title')),
        valueField: false,
        displayField: 'name',
        ajax: {
            triggerLength: 1
        },
        onSelect: function () {
            $title.trigger('select');
        }
    });
    $title.initTypeahead(titleOptions);

    // address typeahead
    /** @type {Object} */
    const addressOptions = $.extend({}, defaultOptions, {
        url: addressUrl,
        valueField: 'street',
        ajax: {
            /** @param {string} query */
            preDispatch: function (query) {
                return {
                    street: query
                };
            }
        },
        /** @param {ItemType} item */
        onSelect: function (item) {
            $zip.val(item.zip);
            $city.val(item.city);
            $address.val($address.val() + ' ');
        },
    })
    $address.initTypeahead(addressOptions);

    // zip typeahead
    /** @type {Object} */
    const zipOptions = $.extend({}, defaultOptions, {
        url: addressUrl,
        valueField: 'zip',
        ajax: {
            /** @param {string} query */
            preDispatch: function (query) {
                return {
                    zip: query
                };
            }
        },
        /** @param {ItemType} item */
        onSelect: function (item) {
            $city.val(item.city);
            $zip.trigger('select');
        },
    })
    $zip.initTypeahead(zipOptions);

    // city typeahead
    /** @type {Object} */
    const cityOptions = $.extend({}, defaultOptions, {
        valueField: 'city',
        ajax: {
            url: addressUrl,
            /** @param {string} query */
            preDispatch: function (query) {
                return {
                    city: query
                };
            }
        },
        /** @param {ItemType} item */
        onSelect: function (item) {
            $zip.val(item.zip);
            $city.trigger('select');
        },
    });
    $city.initTypeahead(cityOptions);

    // initialize validator
    const options = {
        rules: {
            'customer[firstName]': {
                // eslint-disable-next-line camelcase
                require_from_group: [1, '.customer-group']
            },
            'customer[lastName]': {
                // eslint-disable-next-line camelcase
                require_from_group: [1, '.customer-group']
            },
            'customer[company]': {
                // eslint-disable-next-line camelcase
                require_from_group: [1, '.customer-group']
            },
            'customer[zipCode]': {
                zipcodeCH: true
            },
            'customer[webSite]': {
                url: true
            }
        }
    };
    $form.initValidator(options);
});
