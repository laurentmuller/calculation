/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $form = $("#edit-form");
    const $inputs = $form.find(':checkbox');
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
        },
        highlight: function (element, errorClass) {
            $inputs.addClass(errorClass);
        },
        unhighlight: function (element, errorClass) {
            $inputs.removeClass(errorClass);
        }
    });
}(jQuery));
