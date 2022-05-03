/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // image
    const $imageFile = $('#user_imageFile_file');
    if ($imageFile.length) {
        // delete checkbox handler
        let callback = null;
        const $delete = $('#user_imageFile_delete');
        if ($delete.length) {
            callback = function ($file) {
                const source = $file.data('src') || '';
                const target = $file.parents('.form-group').find('img').attr('src') || '';
                $delete.setChecked(source !== target);
            };
        }

        // initialize
        $imageFile.initFileInput(callback);
    }

    // options
    const $form = $("#edit-form");
    const urlName = $form.data('check-name');
    const urlEmail = $form.data('check-email');
    let options = {
        fileInput: true,
        rules: {
            'user[username]': {
                remote: {
                    url: urlName,
                    data: {
                        id: function () {
                            return $('#user_id').val();
                        },
                        username: function () {
                            return $('#user_username').val();
                        }
                    }
                }
            },
            'user[email]': {
                remote: {
                    url: urlEmail,
                    data: {
                        id: function () {
                            return $('#user_id').val();
                        },
                        email: function () {
                            return $('#user_email').val();
                        }
                    }
                }
            }
        }
    };

    // new user?
    const $userPlainPasswordFirst = $('#user_plainPassword_first');
    if ($userPlainPasswordFirst.length) {
        // update options
        const message = $('#edit-form').data('equal_to');
        options = $.extend(true, options, {
            rules: {
                'user[plainPassword][first]': {
                    password: 3,
                    notEmail: true,
                    notUsername: '#user_username'
                },
                'user[plainPassword][second]': {
                    equalTo: '#user_plainPassword_first'
                },
            },
            messages: {
                'user[plainPassword][second]': {
                    'equalTo': message
                }
            }
        });

        // initialize password strength meter
        $userPlainPasswordFirst.initPasswordStrength({
            userField: '#user_username'
        });
    }

    // initialize validator
    $form.initValidator(options);
}(jQuery));
