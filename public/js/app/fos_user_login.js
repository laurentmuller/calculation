/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // validation
    $('#edit-form').initValidator({
        focus: false
    });

    // image
    if ($('#_captcha').length) {
        const url = $('#_captcha').data('refresh');
        $('#refresh_captcha').on('click', function () {
            $.get(url, function (response) {
                if (response.result) {
                    $('#image_captcha').attr('src', response.data);
                    $('#_captcha').val('').focus();
                }
            });
        });
    }

    // focus
    $('#_username').createTimer(function () {
        $('#_username').selectFocus().removeTimer();
    }, 250);
});