/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // bind events
    $('#form_entity').on('input', function () {
        const $this = $(this);
        const $selected = $this.find(":selected");

        $('form').attr('action', $this.val());
        $('#form_entity_help').text($selected.data('help'));
    });

    $('#edit-form').initValidator();
}(jQuery));
