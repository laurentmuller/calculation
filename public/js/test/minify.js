/**! compression tag for ftp-deployment */

/**
 * Document ready function
 */
$(function () {
    'use strict';

    $("#password").selectFocus();

    $(".switch").on('change', function () {
        const $that = $(this);
        const checked = $that.is(":checked");
        const $label = $that.siblings("label");
        const text = checked ? $that.data('checked') : $that.data('unchecked');
        $label.text(text);
    });
});