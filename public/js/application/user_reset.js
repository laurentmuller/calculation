/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // image
    if ($('#_captcha').length) {
        const url = $('#_captcha').data('refresh');
        $('#refresh_captcha').on('click', function () {
            $.get(url, function (response) {
                if (response.result) {
                    $('#image_captcha').attr('src', response.data);
                    $('#_captcha').val('').focus();
                }
            });
        });
    }

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
