/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize editor
    $("#user_comment_message").initSimpleEditor({
        focus: true
    });

    // initialize attachements
    $("#user_comment_attachments").initFileInput();

    // initialize validator
    $("form").initValidator({
        simpleeditor: true,
        fileInput: true,
        focus: false
    });
}(jQuery));
