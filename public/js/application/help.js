(function ($) {
    'use strict';
    /**
     * jQuery functions extensions
     */
    $.fn.extend({
        showItems: function (e) {
            if (e) {
                e.preventDefault();
            }
            $(this).find('.collapse:not(.show)').collapse('show');
        },

        hideItems: function (e) {
            if (e) {
                e.preventDefault();
            }
            $(this).find('.collapse.show').collapse('hide');
        },

        toggleItems: function (e) {
            if (e) {
                e.preventDefault();
            }
            $(this).find('.collapse').collapse('toggle');
        },
    });

    /**
     * Ready function
     */
    $(function () {
        let searching = false;


        // expand/collapse
        $('.btn-expand-all').on('click', function () {
            $('.help-body').showItems();
        });
        $('.btn-collapse-all').on('click', function () {
            $('.help-body').hideItems();
        });
        $('.btn-toggle-all').on('click', function () {
            $('.help-body').toggleItems();
        });

        $('.expand-items').on('click', function (e) {
            $(this).parents('.items').showItems(e);
        });
        $('.collapse-items').on('click', function (e) {
            $(this).parents('.items').hideItems(e);
        });
        $('.toggle-items').on('click', function (e) {
            $(this).parents('.items').toggleItems(e);
        });

        // search
        const $input = $('#help-search');
        if ($input.length === 0) {
            return;
        }
        const $button = $('.btn-help-search').on('click', function () {
            $input.val('').trigger('input').trigger('focus');
        });

        const $context = $('.help-body .help-item');
        const options = {
            element: 'span',
            className: 'text-success',
            separateWordSearch: false,
            each: function (element) {
                $(element).parents('.help-item').showItems();
            },
            done: function () {
                $context.not(':has(span)').hide();
            },
        };
        $input.on('input', function () {
            const value = String($input.val()).trim();
            searching = true;
            $context.show().unmark();
            if (value.length) {
                $('.link-items').toggleDisabled(true);
                $button.toggleDisabled(false);
                $context.mark(value, options);
            } else {
                $('.link-items').toggleDisabled(false);
                $button.toggleDisabled(true);
            }
            searching = false;
        });
    });
}(jQuery));
