/**
 * Ready function
 */
$(function () {
    'use strict';
    const $form = $('#edit-form');
    $form.simulate().initValidator({
        rules: {
            'form[states][]': {
                // eslint-disable-next-line camelcase
                require_from_group: [1, '#form_states .form-check-input']
            }
        },
        messages: {
            'form[states][]': $form.data('error')
        },
        showModification: false
    });
});
