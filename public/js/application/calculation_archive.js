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
                require_from_group: [1, '.form-check-inline .custom-control-input'],
            }
        },
        messages: {
            'form[sources][]':  $form.data('error')
        },
        spinner: {
            text: $('.card-title').text() + '...'
        }
    });
    $('#form_sources .custom-switch').addClass('mr-4');
}(jQuery));
