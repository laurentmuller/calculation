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

    // add method for the Switzerland zip code (1000 - 9999)
    $.validator.addMethod("zipcodeCH", function (value, element) {
        return this.optional(element) || /^[1-9]\d{3}$/.test(value);
    });
    $.extend($.validator.messages, {
        zipcodeChFallback: "Ce champ doit contenir un numéro postal valide.",
        zipcodeChLabel: "Le champ \"{0}\" doit contenir un numéro postal valide.",
        zipcodeCH: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.zipcodeChLabel, $.validator.messages.zipcodeChFallback);
        },
    });

    // options
    const options = {
        rules: {
            "customer[firstName]": {
                require_from_group: [1, ".customer-group"] // eslint-disable-line camelcase
            },
            "customer[lastName]": {
                require_from_group: [1, ".customer-group"] // eslint-disable-line camelcase
            },
            "customer[company]": {
                require_from_group: [1, ".customer-group"] // eslint-disable-line camelcase
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
