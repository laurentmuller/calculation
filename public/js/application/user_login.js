/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize captcha
    $('#captcha').initCaptcha();

    // initialize validator
    $('#edit-form').initValidator(
        {
            showModification: false,
            spinner: {
                text: $('.card-title').text() + '...',
                // css: {
                //     top: '25%',
                // }
            }
        }
    );
}(jQuery));
