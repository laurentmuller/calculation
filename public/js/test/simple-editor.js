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
        simpleEditor: true,
        focus: false
    });

}(jQuery));
