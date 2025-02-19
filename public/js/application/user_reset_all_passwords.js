/**
 * Ready function
 */
$(function () {
    'use strict';
    const $form = $("#edit-form");
    $form.initValidator({
        focus: false,
        rules: {
            'form[users][]': {
                // eslint-disable-next-line camelcase
                require_from_group: [1, '.form-check-input']
            }
        },
        messages: {
            'form[users][]': $form.data('error')
        }
    });
});
