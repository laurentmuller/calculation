/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize captcha
    $('#captcha').initCaptcha();

    // initialize password strength meter
    $("#plainPassword_first").initPasswordStrength();

    // initialize validator
    const $form = $("#edit-form");
    const message = $form.data("equal_to");
    const options = {
        showModification: false,
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
        },
        spinner: {
            text: $('.card-title').text() + '...',
            // css: {
            //     top: '25%',
            // }
        }
    };
    $form.initValidator(options);
}(jQuery));
