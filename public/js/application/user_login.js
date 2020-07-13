/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize captcha
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

    // initialize validator
    $('#edit-form').initValidator();
});