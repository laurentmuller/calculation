/**
 * Ready function
 */
$(function () {
    'use strict';
    $('#form_message').initSimpleEditor({
        focus: true
    });
    $('#form_attachments').initSimpleFileInput();

    $("#edit-form").initValidator({
        simpleEditor: true,
        focus: false
    });
});
