/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize password strength meter
    $("#user_change_password_plainPassword_first").initPasswordStrength({
        userField: "#user_change_password_username"
    });

    // initialize validator
    const message = $("#edit-form").data("equal_to");
    const options = {
        rules: {
            "user_change_password[plainPassword][first]": {
                password: 3,
                notEmail: true,
                notUsername: '#user_change_password_username'
            },
            "user_change_password[plainPassword][second]": {
                equalTo: "#user_change_password_plainPassword_first"
            }
        },
        messages: {
            "user_change_password[plainPassword][second]": {
                equalTo: message
            }
        }
    };
    $("#edit-form").initValidator(options);
});
