/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // delete checkbox handler
    let callback = null;
    const $delete = $("#user_imageFile_delete");
    if ($delete.length) {
        callback = function ($file) {
            const source = $file.data('src') || '';
            const target = $file.parents('.form-group').find('img').attr('src') || '';
            $delete.setChecked(source !== target);
        };
    }

    // image file handler
    $("#user_imageFile_file").initFileType(callback);

    // initialize
    $('#edit-form').initValidator({
        fileInput: true
    });
}(jQuery));
