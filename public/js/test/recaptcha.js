/**! compression tag for ftp-deployment */

/* global grecaptcha */

/**
 * reCaptcha ready callback
 */
grecaptcha.ready(function () {
    'use strict';

    // update badge position
    $('.grecaptcha-badge').css('bottom', '30px');

    // execute
    const key = $('[recaptcha-site]').attr('recaptcha-site');
    const action = $('[recaptcha-action]').attr('recaptcha-action');
    grecaptcha.execute(key, {
        action: action
    }).then(function (token) {
        $('#form_recaptcha').val(token);
        $(':submit').removeAttr('disabled');
    });
});

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#edit-form').initValidator();
}(jQuery));
