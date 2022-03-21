/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initalize search
    const $form = $("#edit-form");
    $("#product_unit").initTypeahead({
        url: $form.data("unit-search"),
        error: $form.data("unit-error")
    });
    $("#product_supplier").initTypeahead({
        url: $form.data("supplier-search"),
        error: $form.data("supplier-error")
    });

    // initalize price
    $('#product_price').inputNumberFormat();

    // validation
    $form.initValidator();
}(jQuery));
