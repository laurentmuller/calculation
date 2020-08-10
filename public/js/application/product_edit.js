/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Initialize search units
 */
function initSearchUnits() {
    'use strict';

    // search units url
    const url = $("#edit-form").data("search-unit");
    const $element = $("#product_unit");
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
            const message = $("#edit-form").data("error-unit");
            Toaster.danger(message, title, $("#flashbags").data());
        }
    };
    $element.typeahead(options);
}

/**
 * Initialize search suppliers
 */

function initSearchSuppliers() {
    'use strict';

    // search suppliers url
    const url = $("#edit-form").data("search-supplier");
    const $element = $("#product_supplier");
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
            const message = $("#edit-form").data("error-supplier");
            Toaster.danger(message, title, $("#flashbags").data());
        }
    };
    $element.typeahead(options);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    initSearchUnits();
    initSearchSuppliers();
    $('#product_price').inputNumberFormat();
    $("#edit-form").initValidator();
}(jQuery));
