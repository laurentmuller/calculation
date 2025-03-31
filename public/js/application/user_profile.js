/**
 * Ready function
 */
$(function () {
    'use strict';

    // remove pattern attribute
    $('#username').removeAttr('pattern');

    // image file handler
    $('#imageFile_file').initImageInput($('#imageFile_delete'))
        .imageInput();

    // options
    const $form = $('#edit-form');
    const urlName = $form.data('check-name');
    const urlEmail = $form.data('check-email');
    const options = {
        imageInput: true,
        rules: {
            'username': {
                remote: {
                    url: urlName,
                    data: {
                        id: function () {
                            return $('#id').val();
                        },
                        username: function () {
                            return $('#username').val();
                        }
                    }
                }
            },
            'email': {
                remote: {
                    url: urlEmail,
                    data: {
                        id: function () {
                            return $('#id').val();
                        },
                        email: function () {
                            return $('#email').val();
                        }
                    }
                }
            }
        }
    };

    // validation
    $form.initValidator(options);
});
