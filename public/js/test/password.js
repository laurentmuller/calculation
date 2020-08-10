/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // password strength
    $("#form_password").initPasswordStrength();

    // validation
    const $captcha = $('#form_captcha');
    $("#edit-form").initValidator({
        rules: {
            'form[captcha]': {
                remote: {
                    url: $captcha.attr('remote'),
                    data: {
                        _captcha: function () {
                            return $captcha.val();
                        }
                    }
                }
            }
        }
    });

    // image
    const url = $captcha.data('refresh');
    $('#refreshform_captcha').on('click', function () {
        $.get(url, function (response) {
            if (response.result) {
                $('#imageform_captcha').attr('src', response.data);
                $captcha.val('').focus();
            }
        });
    });
}(jQuery));
