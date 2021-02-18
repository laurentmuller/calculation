/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // remove accept method
    // delete $.validator.methods.accept;

    // initialize attachements
    $("#form_file").initFileInput();

    // initialize validator
    $("form").initValidator({
        fileInput: true,
    });
}(jQuery));
