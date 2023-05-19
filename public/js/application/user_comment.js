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
    $('#user_comment_attachments').initSimpleFileInput();

    // initialize validator
    $("form").initValidator({
        simpleEditor: true,
        focus: false
    });
}(jQuery));
