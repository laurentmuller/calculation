/**
 * Ready function
 */
$(function () {
    'use strict';

    // image file handler
    $('#user_imageFile_file').initFileInput($('#user_imageFile_delete'));

    // options
    const $form = $('#edit-form');
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
        const message = $form.data('equal_to');
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
                    equalTo: message
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
});
