/**! compression tag for ftp-deployment */

/**
 * Handles the months input change.
 *
 * @param {JQuery} $months - the months element to get value from.
 */
function onMonthsChange($months) {
    'use strict';
    const oldMonths = $months.data('months');
    const newMonths = $.parseInt($months.val());
    if (newMonths !== oldMonths) {
        const url = $months.data('url') + "/" + newMonths;
        window.location.assign(url);
    }
}

/**
 * Ready function.
 */
(function ($) {
    'use strict';
    // tooltip
    $('#data').tooltip({
        selector: '.has-tooltip',
        customClass: 'tooltip-danger'
    });
    // handle months change
    const $months = $('#months');
    if ($months.length) {
        $months.on('input', function () {
            $months.updateTimer(onMonthsChange, 250, $months);
        }).trigger('focus');
    }
}(jQuery));
