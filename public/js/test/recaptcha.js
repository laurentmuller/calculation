/**! compression tag for ftp-deployment */

/* global grecaptcha */

/**
 * reCaptcha ready callback
 */
grecaptcha.ready(function () {
    'use strict';
    // update badge position
    let bottom = 5;
    if ($('footer:visible').length) {
        bottom += $('footer').outerHeight();
    }
    $('.grecaptcha-badge').css('bottom',  bottom + 'px');

    // execute
    const $form = $('#edit-form');
    const key = $form.data('key');
    const action = $form.data('action');
    grecaptcha.execute(key, {
        action: action
    }).then(function (token) {
        $('#form_recaptcha').val(token);
        $(':submit').toggleDisabled(false);
    });
});

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#edit-form').initValidator();
}(jQuery));
