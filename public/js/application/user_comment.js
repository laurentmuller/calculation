/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize editor
    $("#message").initSimpleEditor({
        focus: true
    });

    // initialize attachements
    $('#attachments').initSimpleFileInput();

    // initialize validator
    $("form").initValidator({
        simpleEditor: true,
        focus: false
    });
});
