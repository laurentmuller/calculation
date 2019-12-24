/**! compression tag for ftp-deployment */

/* global grecaptcha */

/**
 * reCaptcha ready callback
 */
if ($('#_recaptcha').length) {
    grecaptcha.ready(function () {
        'use strict';
        const key = $('[recaptcha-site]').attr('recaptcha-site');
        const action = $('[recaptcha-action]').attr('recaptcha-action');
        grecaptcha.execute(key, {
            action: action
        }).then(function (token) {
            $('#_recaptcha').val(token);
        });
    });
}
