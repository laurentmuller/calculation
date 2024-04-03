/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#command').on('change', function () {
        const $selection = $(this).getSelectedOption();
        if ($selection && $selection.length) {
            $('.help').text($selection.data('help'));
        } else {
            $('.help').text('');
        }
    }).trigger('change');
}(jQuery));
