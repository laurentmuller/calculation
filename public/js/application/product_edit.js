/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Initialize a search for an element.
 *
 * @param {jQuery}
 *            $element - The element to handle.
 * @param {String]
 *            url - The search URL.
 * @param {string}
 *            error - The message to display on search error.
 *
 * @returns {jQuery} The element for chaining.
 */
function initSearchElement($element, url, error) {
    'use strict';

    // search units url
    const options = {
        valueField: '',
        ajax: {
            url: url
        },
        // overridden functions (all are set in the server side)
        matcher: function () {
            return true;
        },
        grepper: function (data) {
            return data;
        },
        onSelect: function () {
            $element.select();
        },
        onError: function () {
            const title = $("#edit-form").data("title");
            Toaster.danger(error, title, $("#flashbags").data());
        }
    };
    $element.typeahead(options);

    return $element;
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $form = $("#edit-form");
    initSearchElement($("#product_unit"), $form.data("unit-search"), $form.data("unit-error"));
    initSearchElement($("#product_supplier"), $form.data("supplier-search"), $form.data("supplier-error"));
    $('#product_price').inputNumberFormat();
    $form.initValidator();
}(jQuery));
