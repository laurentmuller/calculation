/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#form_simulated').on('input', function () {
        if ($(this).isChecked()) {
            $('#form_confirm').attr('disabled', true).removeValidation();
        } else {
            $('#form_confirm').attr('disabled', false);
        }
    });

    // validation
    $('#edit-form').initValidator();
}(jQuery));
