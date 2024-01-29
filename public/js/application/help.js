/**! compression tag for ftp-deployment */

/* global bootstrap */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    let searching = false;

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

        // saveState: function () {
        //     if (searching) {
        //         return;
        //     }
        //     const $this = $(this);
        //     const id = $this.attr('id');
        //     if (!id) {
        //         return;
        //     }
        //     const key = 'help-' + id;
        //     if ($this.hasClass('show')) {
        //         window.sessionStorage.setItem(key, '1');
        //     } else {
        //         window.sessionStorage.removeItem(key);
        //     }
        // }
    });

    // save/restore collapse state
    // if (window.sessionStorage) {
    //     const $collapses = $('.help-body .collapse');
    //     $collapses.each((index, element) => {
    //         const $this = $(element);
    //         if (!$this.hasAttr('id')) {
    //             return true;
    //         }
    //         const key = 'help-' + $this.attr('id');
    //         if (window.sessionStorage.getItem(key)) {
    //             bootstrap.Collapse.getOrCreateInstance(element);
    //         }
    //     });
    //     $collapses.on('show.bs.collapse hide.bs.collapse', (e) => {
    //         $(e.target).saveState();
    //     });
    // }

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

}(jQuery));
