/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $form = $('#edit-form');
    $form.simulate().initValidator({
        rules: {
            'form[sources][]': {
                /* eslint camelcase: "off" */
                require_from_group: [1, '#form_sources .form-check-input']
            }
        },
        messages: {
            'form[sources][]':  $form.data('error')
        },
        spinner: {
            text: $('.card-title').text() + '...'
        }
    });
    $('#form_sources .custom-switch').addClass('me-4');
}(jQuery));
