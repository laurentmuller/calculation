/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';
    $('#edit-form').initValidator({
        spinner: {
            text: $('.card-header small').text() + '..'
        }
    });
    $('#edit-form [data-bs-toggle="popover"]').popover();
});
