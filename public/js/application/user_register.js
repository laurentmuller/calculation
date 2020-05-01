/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize password strength meter
    $("#plainPassword_first").initPasswordStrength({
        userField: "#username"
    });
    
    
    // initialize captcha
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

    // initialize validator
    const message = $("#edit-form").data("equal_to");
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
    $("#edit-form").initValidator(options);
});
