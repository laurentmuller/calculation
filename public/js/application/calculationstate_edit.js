/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#calculation_state_code').ucFirst();
    $('#calculation_state_color').initColorPicker();

    $("form").initValidator({
        colorPicker: true
    });
}(jQuery));
