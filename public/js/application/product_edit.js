/**
 * Ready function
 */
$(function () {
    'use strict';
    const $form = $("#edit-form");
    $("#product_unit").initTypeahead({
        url: $form.data("unit-search"),
        error: $form.data("unit-error")
    });
    $("#product_supplier").initTypeahead({
        url: $form.data("supplier-search"),
        error: $form.data("supplier-error")
    });
    $('#product_price').inputNumberFormat();
    $form.initValidator();
});
