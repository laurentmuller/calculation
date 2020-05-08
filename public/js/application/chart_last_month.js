/**! compression tag for ftp-deployment */

/**
 * Ready function.
 */
$(function () {
    'use strict';

    // update input value
    const $months = $('#months');
    const href = window.location.href;
    const index = href.lastIndexOf('/');
    let value = Number.parseInt(href.substr(index + 1), 10);
    if (Number.isNaN(value)) {
        value = 12;
    }
    $months.data('months', value);
    $months.val(value);

    // handle input change
    $months.on('input', function () {
        const $this = $(this);
        const oldMonths = $this.data('months');
        const newMonths = $this.val();
        if (newMonths !== oldMonths) {
            const url = $this.data('url') + "/" + newMonths;
            window.location.assign(url);
        }
    });
});
