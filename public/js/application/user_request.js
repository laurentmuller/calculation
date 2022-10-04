/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize captcha
    $('#captcha').initCaptcha();

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
            }
        },
        spinner: {
            text: $('.card-title').text() + '...',
            css: {
                top: '20%',
            }
        }
    };
    $form.initValidator(options);
}(jQuery));
