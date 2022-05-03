/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    // initialize attachements
    $("#form_file").initFileInput();

    // initialize validator
    $("#edit-form").initValidator({
        fileInput: true,
    });
}(jQuery));
