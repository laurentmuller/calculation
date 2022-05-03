/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize captcha
    $('#captcha').initCaptcha();

    // initialize password strength meter
    $("#plainPassword_first").initPasswordStrength({
        userField: "#username"
    });

    // initialize validator
    const $form = $("#edit-form");
    const message = $form.data("equal_to");
    const options = {
        rules: {
            "plainPassword[first]": {
                password: 3,
                notEmail: true,
                notUsername: '#username'
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
    $form.initValidator(options);
}(jQuery));
