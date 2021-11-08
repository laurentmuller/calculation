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
 * @returns {jQuery} The element for chaining.
 */
function initSearchElement($element, url, error) {
    'use strict';

    $element.typeahead({
        valueField: '',
        ajax: {
            url: url
        },
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
    });

    return $element;
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initalize search
    const $form = $("#edit-form");
    initSearchElement($("#product_unit"), $form.data("unit-search"), $form.data("unit-error"));
    initSearchElement($("#product_supplier"), $form.data("supplier-search"), $form.data("supplier-error"));

    // initalize price
    $('#product_price').inputNumberFormat();

    // validation
    $form.initValidator();
}(jQuery));
