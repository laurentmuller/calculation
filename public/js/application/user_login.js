/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize captcha
    const $captcha = $('#captcha').initCaptcha();

    // initialize validator
    $('#edit-form').initValidator({
        showModification: false,
        rules: {
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
    });
});
