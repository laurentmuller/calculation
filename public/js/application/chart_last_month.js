/**! compression tag for ftp-deployment */

/**
 * Handles the months input change.
 *
 * @param {jQuery}
 *            $months - the months element to get value from.
 */
function onMonthsChange($months) {
    'use strict';

    const oldMonths = $months.data('months');
    const newMonths = Number.parseInt($months.val(), 10);
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

    // handle months change
    const $months = $('#months');
    $months.on('input', function () {
        $months.updateTimer(onMonthsChange, 250, $months);
    }).focus();
}(jQuery));
