/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#form_sources .custom-switch').addClass('mr-4');

    $.extend($.validator.messages, {
        require_from_group: 'Au moins 1 statut doit être sélectionné.'
    });

    $('#edit-form').simulate().initValidator({
        rules: {
            'form[sources][]': {
                require_from_group: [1, '.form-check-inline .custom-control-input'],
            }
        },
    });
}(jQuery));
