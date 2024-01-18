/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $input = $('#help-search');
    if ($input.length === 0) {
        return;
    }
    const $button = $('.btn-help-search').on('click', function () {
        $input.val('').trigger('input').trigger('focus');
    });
    const $context = $('.help-body').find('.help-item');
    const options = {
        element: 'span',
        className: 'text-success',
        done: function () {
            $context.not(':has(span)').hide();
        },
    };
    $input.on('input', function () {
        const value = String($input.val()).trim();
        $context.show().unmark();
        if (value.length) {
            $button.removeAttr('disabled');
            $context.mark(value, options);
        } else {
            $button.attr('disabled', 'disabled');
        }
    });
}(jQuery));
