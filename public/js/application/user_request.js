/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize captcha
    $('#captcha').initCaptcha();

    // initialize validator
    const url = $('#edit-form').data('check-user');
    const options = {
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
            }
        }
    };
    $('#edit-form').initValidator(options);
}(jQuery));
