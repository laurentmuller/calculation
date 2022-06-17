/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // color
    $('#calculation_state_color').initColorPicker();

    // validation
    $("form").initValidator({
        colorPicker: true
    });
}(jQuery));
