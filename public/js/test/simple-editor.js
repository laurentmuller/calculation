/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#form_message').initSimpleEditor({
        focus: true
    });
    $("#form_attachments").initFileInput();
    $("#edit-form").initValidator({
        simpleEditor: true,
        fileInput: true,
        focus: false
    });
}(jQuery));
