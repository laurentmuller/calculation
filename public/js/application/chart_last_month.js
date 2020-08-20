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
    const newMonths = $months.val();
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

    // update input value
    const $months = $('#months');
    const href = window.location.href;
    const index = href.lastIndexOf('/');
    let value = Number.parseInt(href.substr(index + 1), 10);
    if (isNaN(value)) {
        value = 12;
    }
    $months.data('months', value).val(value);

    // handle input change
    $months.on('input', function () {
        $months.updateTimer(onMonthsChange, 500, $months);
    });
    $months.focus();
}(jQuery));
