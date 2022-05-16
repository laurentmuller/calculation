/**! compression tag for ftp-deployment */


/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#form_sources .custom-switch').addClass('mr-4');

    $('#form_simulate').on('input', function () {
        if ($(this).isChecked()) {
            $('#form_confirm').toggleDisabled(true).removeValidation();
        } else {
            $('#form_confirm').toggleDisabled(false);
        }
    });

    // #form[sources][]
    $('#edit-form').initValidator({
        // #form_sources .custom-control-input
    });
}(jQuery));
