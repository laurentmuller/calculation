/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    const options = {
        rules: {
            'global_margin[maximum]': {
                greaterThan: '#global_margin_minimum'
            }
        }
    };

    // initialize validator
    $('#edit-form').initValidator(options);
});