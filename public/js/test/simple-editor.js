/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#form_message').initSimpleEditor({
        focus: true
    });
    $("#edit-form").initValidator({
        simpleeditor: true,
        focus: false
    });

}(jQuery));
