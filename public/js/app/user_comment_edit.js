/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize editor
    const focus = $("form").data('focus');
    $("#user_comment_message").initEditor({
        focus: focus
    });

    // initialize attachements
    $("#user_comment_attachments").initFileType();
    
    // initialize validator
    $("form").initValidator({
        editor: true,
        fileInput: true,
        focus: !focus
    });
});
