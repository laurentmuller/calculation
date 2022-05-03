/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#form_simulate').on('input', function () {
        const $confirm = $('#form_confirm');
        if ($(this).isChecked()) {
            $confirm.toggleDisabled(true).removeValidation();
        } else {
            $confirm.toggleDisabled(false);
        }
    });

    // validation
    $('#edit-form').initValidator();
}(jQuery));
