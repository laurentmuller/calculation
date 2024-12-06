/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize captcha
    const $captcha = $('#captcha').initCaptcha();

    // initialize validator
    const $form = $("#edit-form");
    const url = $form.data('check-user');
    const options = {
        showModification: false,
        rules: {
            'user': {
                    remote: {
                    url: url,
                    data: {
                        user: function () {
                            return $('#user').val();
                        }
                    }
                }
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
        }
    };
    $form.initValidator(options);
});
