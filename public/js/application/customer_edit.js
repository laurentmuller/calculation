/**! compression tag for ftp-deployment */

/**
 * ready function
 */
(function ($) {
    'use strict';

    // get controls
    const $form = $('#edit-form');
    const $title = $('#customer_title');
    const $address = $('#customer_address');
    const $zip = $('#customer_zipCode');
    const $city = $('#customer_city');
    const addressUrl = $form.data('search-address');

    // default typeahead options
    const defaultOptions = {
        alignWidth: false,
        displayField: 'display',
        ajax: {
            triggerLength: 2
        },
        error: $form.data('error')
    };

    // title typeahead
    $title.initTypeahead($.extend({}, defaultOptions, {
        valueField: false,
        displayField: 'name',
        ajax: {
            url: $form.data('search-title'),
            triggerLength: 1
        },
        onSelect: function () {
            $title.select();
        }
    }));

    // address typeahead
    $address.initTypeahead($.extend({}, defaultOptions, {
        valueField: 'street',
        ajax: {
            url: addressUrl,
            preDispatch: function (query) {
                return {
                    street: query
                };
            }
        },
        onSelect: function (item) {
            $zip.val(item.zip);
            $city.val(item.city);
            $address.val($address.val() + ' ');
        },
    }));

    // zip typeahead
    $zip.initTypeahead($.extend({}, defaultOptions, {
        valueField: 'zip',
        ajax: {
            url: addressUrl,
            preDispatch: function (query) {
                return {
                    zip: query
                };
            }
        },
        onSelect: function (item) {
            $city.val(item.city);
            $zip.select();
        },
    }));

    // city typeahead
    $city.initTypeahead($.extend({}, defaultOptions, {
        valueField: 'name',
        ajax: {
            url: addressUrl,
            preDispatch: function (query) {
                return {
                    city: query
                };
            }
        },
        onSelect: function (item) {
            $zip.val(item.zip);
            $city.select();
        },
    }));

    // validator options
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

    // initialize
    $form.initValidator(options);
}(jQuery));
