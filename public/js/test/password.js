/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // password strength
    const $input =  $("#form_input");
    $input.initPasswordStrength({
        labelContainer: $('#form_input_passwordStrength'),
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

    // strength level
    $('#form_level').on('input', function () {
        const value = $(this).val();
        $input.data('strength', value).trigger('keyup');
    }).trigger('keyup');

}(jQuery));
