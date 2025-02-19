/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize captcha
    const $captcha = $('#captcha').initCaptcha();

    // initialize password strength meter
    $('#plainPassword_first').initPasswordStrength();

    // initialize validator
    const $form = $('#edit-form');
    const message = $form.data('equal_to');
    const options = {
        showModification: false,
        rules: {
            'plainPassword[first]': {
                password: 3,
                notEmail: true
            },
            'plainPassword[second]': {
                equalTo: '#plainPassword_first'
            },
            'captcha': {
                remote: {
                    url: $captcha.data('remote'),
                    data: {
                        captcha: function () {
                            return $captcha.val();
                        }
                    }
                }
            }
        },
        messages: {
            'plainPassword[second]': {
                equalTo: message
            }
        }
    };
    $form.initValidator(options);
});
