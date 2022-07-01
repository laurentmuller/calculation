/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#date').trigger('focus');
    const updateCursor = function () {
        $('*').css('cursor', 'wait');
        setTimeout(function () {
            $('*').css('cursor', '');
        }, 250);
    };
    $('.btn-previous, .btn-today, .btn-next, .btn-submit').on('click', updateCursor);
    $('#interval, #date').on('input', function () {
        $(this).updateTimer(function () {
            updateCursor();
            $('form#search').trigger('submit');
        }, 500);
    });
}(jQuery));
