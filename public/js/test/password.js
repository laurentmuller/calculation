/**
 * Update rules.
 * @param {jQuery} $input
 * @param {Object} [validator]
 */
const updateRules = function ($input, validator) {
    'use strict';
    $input.rules('remove');
    $('.password-option:checked').each(function () {
        let rule = null;
        switch ($(this).data('validation')) {
            case 'letters':
                rule = {letter: true};
                break;
            case 'case_diff':
                rule = {mixedCase: true};
                break;
            case 'numbers':
                rule = {digit: true};
                break;
            case 'special_char':
                rule = {specialChar: true};
                break;
            case 'email':
                rule = {notEmail: true};
                break;
        }
        if (rule) {
            $input.rules('add', rule);
        }
    });
    if (validator) {
        validator.element($input);
    }
};

/**
 * Ready function
 */
$(function () {
    'use strict';
    // password strength
    const $input = $('#form_input');
    $input.initPasswordStrength({
        labelContainer: $('#form_input_passwordStrength'),
        debug: true
    });

    // validation
    const $captcha = $('#form_captcha');
    const validator = $('#edit-form').initValidator({
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

    // password rules
    $('.password-option').on('change', function () {
        updateRules($input, validator);
    });
    updateRules($input);

    // image
    const url = $captcha.data('refresh');
    $('.captcha-refresh').on('click', function () {
        $.getJSON(url, function (response) {
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
});
