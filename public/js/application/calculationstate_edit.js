

/**
 * Ready function
 */
$(function () {
    'use strict';
    $('#calculation_state_color').initColorPicker();
    $("form").initValidator({
        colorPicker: true
    });
});
