/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#form_sources .custom-switch').addClass('mr-4');
    $('#edit-form').simulate().initValidator({
        rules: {
            'form[sources][]': {
                require_from_group: [1, '.form-check-inline .custom-control-input'],
            }
        },
        messages: {
            require_from_group: 'Au moins 1 statut doit être sélectionné.'
        },
        spinner: {
            text: $('.card-title').text() + '...'
        }
    });
}(jQuery));
