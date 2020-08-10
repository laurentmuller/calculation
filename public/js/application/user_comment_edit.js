/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize editor
    const focus = $("form").data('focus');
    $("#user_comment_message").initTinyEditor({
        focus: focus
    });

    // initialize attachements
    $("#user_comment_attachments").initFileType();

    // initialize validator
    $("form").initValidator({
        tinyeditor: true,
        fileInput: true,
        focus: !focus
    });
}(jQuery));
