/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';
    const $form = $('#edit-form');
    $form.simulate().initValidator({
        rules: {
            'form[sources][]': {
                // eslint-disable-next-line camelcase
                require_from_group: [1, '#form_sources .form-check-input']
            }
        },
        messages: {
            'form[sources][]':  $form.data('error')
        }
    });
    $('#form_sources .custom-switch').addClass('me-4');
});
