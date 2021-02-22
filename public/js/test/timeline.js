/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#date').focus();
    $('form#search .btn-submit').on('click', function () {
        const spinner = '<span class="spinner-border spinner-border-sm"></span>';
        $(this).addClass('disabled').html(spinner);
    });

    $('#interval').on('input', function () {
        $(this).updateTimer(function () {
            $('form#search').submit();
        }, 500);
    });
}(jQuery));
