/**! compression tag for ftp-deployment */

/**
 * Handles the months input change.
 *
 * @param {jQuery} $months - the months element to get value from.
 */
function onMonthsChange($months) {
    'use strict';
    const oldMonths = $months.data('months');
    const newMonths = $.parseInt($months.val());
    if (newMonths !== oldMonths) {
        const url = new URL($months.data('url'));
        url.searchParams.set('count', newMonths);
        window.location.assign(url.href);
    }
}

/**
 * Ready function.
 */
(function ($) {
    'use strict';
    // tooltip
    $('#data').tooltip({
        customClass: 'tooltip-danger',
        selector: '.has-tooltip',
        html: true
    });

    // handle months change
    const $months = $('#months');
    if ($months.length) {
        $months.on('input', function () {
            $months.updateTimer(onMonthsChange, 250, $months);
        }).trigger('focus');
    }
}(jQuery));
