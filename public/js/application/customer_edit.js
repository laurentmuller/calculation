/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * ready function
 */
(function ($) {
    'use strict';

    const url = $("#edit-form").data("search");
    const errorHandler = function () {
        const title = $("#edit-form").data("title");
        const message = $("#edit-form").data("error");
        Toaster.danger(message, title, $("#flashbags").data());
    };

    // controls
    const $address = $('#customer_address');
    const $zip = $('#customer_zipCode');
    const $city = $('#customer_city');

    // address
    const addressOptions = {
        valueField: 'street',
        displayField: 'display',
        alignWidth: false,
        onError: errorHandler,

        ajax: {
            url: url,
            triggerLength: 2,
            preDispatch: function (query) {
                return {
                    street: query
                };
            }
        },

        // copy
        onSelect: function (item) {
            $zip.val(item.zip);
            $city.val(item.city);
            $address.val($address.val() + ' ');
        },

        // overridden functions (all are set in the server side)
        matcher: function () {
            return true;
        },
        grepper: function (data) {
            return data;
        }
    };
    $address.typeahead(addressOptions);

    // zip
    const zipOptions = {
        valueField: 'zip',
        displayField: 'display',
        alignWidth: false,
        onError: errorHandler,

        ajax: {
            url: url,
            triggerLength: 2,
            preDispatch: function (query) {
                return {
                    zip: query
                };
            }
        },

        // copy
        onSelect: function (item) {
            $city.val(item.name);
            $zip.select();
        },

        // overridden functions (all are set in the server side)
        matcher: function () {
            return true;
        },
        grepper: function (data) {
            return data;
        }
    };
    $zip.typeahead(zipOptions);

    // city
    const cityOptions = {
        valueField: 'name',
        displayField: 'display',
        alignWidth: false,
        onError: errorHandler,

        ajax: {
            url: url,
            triggerLength: 2,
            preDispatch: function (query) {
                return {
                    city: query
                };
            }
        },

        // copy
        onSelect: function (item) {
            $zip.val(item.zip);
            $city.select();
        },

        // overridden functions (all are set in the server side)
        matcher: function () {
            return true;
        },
        grepper: function (data) {
            return data;
        }
    };
    $city.typeahead(cityOptions);

    // options
    const options = {
        rules: {
            "customer[firstName]": {
                // eslint-disable-next-line camelcase
                require_from_group: [1, ".customer-group"]
            },
            "customer[lastName]": {
                // eslint-disable-next-line camelcase
                require_from_group: [1, ".customer-group"]
            },
            "customer[company]": {
                // eslint-disable-next-line camelcase
                require_from_group: [1, ".customer-group"]
            },
            "customer[zipCode]": {
                zipcodeCH: true
            },
            'customer[webSite]': {
                url: true
            }
        }
    };

    // initialize
    $("form").initValidator(options);
}(jQuery));
