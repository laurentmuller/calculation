/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $form = $('#edit-form');
    $form.simulate().initValidator({
        rules: {
            'form[states][]': {
                require_from_group: [1, '#form_states .form-check-input'],
            }
        },
        messages: {
            'form[states][]':  $form.data('error')
        },
        spinner: {
            text: $('.card-title').text() + '...'
        }
    });
    $('#form_sources .custom-switch').addClass('me-4');
}(jQuery));
