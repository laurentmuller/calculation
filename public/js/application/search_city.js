/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $form = $("form");
    $form.initValidator({
        spinner: {
            text: $('.card-title').text() + '...',
            css: {
                top: '10rem'
            }
        }
    });
}(jQuery));
