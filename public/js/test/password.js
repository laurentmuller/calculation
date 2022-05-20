/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // password strength
    $("#form_input").initPasswordStrength({
        debug: true
    });

    // validation
    const $captcha = $('#form_captcha');
    $("#edit-form").initValidator({
        rules: {
            'form[captcha]': {
                remote: {
                    url: $captcha.data('remote'),
                    data: {
                        captcha: function () {
                            return $captcha.val();
                        }
                    }
                }
            }
        }
    });

    // image
    const url = $captcha.data('refresh');
    $('.captcha-refresh').on('click', function () {
        $.get(url, function (response) {
            if (response.result) {
                $('.captcha-image').attr('src', response.data);
                $captcha.val('').trigger('focus');
            }
        });
    });
}(jQuery));
