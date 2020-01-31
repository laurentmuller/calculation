/**! compression tag for ftp-deployment */

/**
 * Document ready function
 */
$(function () {
    'use strict';

    // bind events
    $('#edit-form .btn-clear').on('click', function () {
        $('#edit-form #form_query').val('').focus();
    });

    $('#form_pagelength').on('input', function () {
        if ($('#form_query').val().length) {
            $('#form_page').val(0);
            $("#edit-form").submit();
        }
    });

    // validate
    $("#edit-form").initValidator({
        submitHandler: function (form) {
            $('#form_page').val(0);
            form.submit();
        }
    });
});