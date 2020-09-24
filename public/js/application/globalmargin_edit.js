/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#global_margin_minimum').inputNumberFormat();
    $('#global_margin_maximum').inputNumberFormat();
    $('#global_margin_margin').inputNumberFormat({
        'decimal': 0
    });

    // initialize validator
    $('#edit-form').initValidator();
}(jQuery));
