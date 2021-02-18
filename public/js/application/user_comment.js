/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize editor
    $("#user_comment_message").initTinymceEditor({
        focus: true
    });

    // initialize attachements
    $("#user_comment_attachments").initFileInput();

    // initialize validator
    $("form").initValidator({
        tinymceeditor: true,
        fileInput: true,
        focus: false
    });
}(jQuery));
