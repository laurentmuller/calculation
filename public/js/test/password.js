/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // password strength
    $("#form_input").initPasswordStrength({
        labelContainer: $('#score'),
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
    // const $level = $('#form_level');
    $('#form_level').on('input', function () {
        const value = $(this).val();
        $('#form_input').data('strength', value).trigger('keyup');
    }).trigger('keyup');

}(jQuery));
