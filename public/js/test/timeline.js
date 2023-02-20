/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // $('#date').trigger('focus');

    const showWait = function () {
        $('*').css('cursor', 'wait');
        setTimeout(function () {
            $('*').css('cursor', '');
        }, 350);
    };
    $('form#search a, form#search .btn-submit').on('click', showWait);
    $('form#search #interval,form#search #date').on('input', function () {
        $('form#search').updateTimer(function () {
            showWait();
            $('form#search').trigger('submit');
        }, 1500);
    });
}(jQuery));
