/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // selection
    $(".card-last.border-primary").scrollInViewport().timeoutToggle('border-primary');

    // tooltip
    $('body').customTooltip({
        selector: '.has-tooltip',
        type: 'danger overall-card'
    });
});