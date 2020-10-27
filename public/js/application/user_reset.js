/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize captcha
    $('#_captcha').initCaptcha();

    // initialize password strength meter
    $("#plainPassword_first").initPasswordStrength();

    // initialize validator
    const message = $("#edit-form").data("equal_to");
    const options = {
        rules: {
            "plainPassword[first]": {
                password: 3,
                notEmail: true
            },
            "plainPassword[second]": {
                equalTo: '#plainPassword_first'
            }
        },
        messages: {
            "plainPassword[second]": {
                equalTo: message
            }
        }
    };
    $("#edit-form").initValidator(options);
}(jQuery));
