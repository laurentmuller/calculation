/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * Update selection on key press.
     *
     * @param {JQuery} $select - the drop-down list.
     * @param {KeyboardEvent} e - the source event.
     */
    const select2KeyPress = function ($select, e) {
        // special key?
        if (e.ctrlKey || e.altKey || e.shiftKey || e.which <= 32) {
            return;
        }

        let newIndex = -1;
        const oldIndex = $select.getSelectedOption().index();
        const options = $select.children('option:enabled');
        const lastIndex = options.length - 1;

        switch (e.which) {
        case 33:
            // page up
            newIndex = Math.max(oldIndex - 5, 0);
            break;
        case 34:
            // page down
            newIndex = Math.min(oldIndex + 5, lastIndex);
            break;
        case 35:
            // end
            newIndex = lastIndex;
            break;
        case 36:
            // home
            newIndex = 0;
            break;
        case 38:
            // arrow up
            newIndex = Math.max(oldIndex - 1, 0);
            break;
        case 40:
            // arrow down
            newIndex = Math.min(oldIndex + 1, lastIndex);
            break;
        default:
            const toFind = e.key || String.fromCharCode(e.which);

            // find from after index to end
            if (newIndex === -1) {
                for (let i = oldIndex + 1; i <= lastIndex; i++) {
                    if (options[i].text.startsWithIgnoreCase(toFind)) {
                        newIndex = i;
                        break;
                    }
                }
            }
            // find from start to before index
            if (newIndex === -1) {
                for (let i = 0; i < oldIndex; i++) {
                    if (options[i].text.startsWithIgnoreCase(toFind)) {
                        newIndex = i;
                        break;
                    }
                }
            }
            break;
        }

        // update selection
        if (newIndex >= 0 && newIndex <= lastIndex && newIndex !== oldIndex) {
            const value = options[newIndex].value;
            $select.val(value).trigger('change').trigger('input');
            e.preventDefault();
        }
    };

    /**
     * -------------- jQuery functions extensions --------------
     */
    $.fn.extend({
        /**
         * Initialize a select with select2 plugin.
         *
         * @param {object} [options] - The select2 options.
         */
        initSelect2: function (options) {
            const borderRadius = $.isBorderRadius();
            return this.each(function () {
                const $select = $(this);
                const multiple = $select.hasAttr('multiple');
                const settings = $.extend(true, {
                    theme: 'bootstrap4',
                    // dropdownAutoWidth: true,
                    // closeOnSelect: !multiple,
                    placeholder: $select.data('placeholder'),
                    allowClear: Boolean($select.data('allow-clear')),
                    width: $select.data('width') ? $select.data('width') : $select.hasClass('w-100') ? '100%' : 'style',
                    selectionCssClass: borderRadius ? '' : 'rounded-0'
                }, options);

                if (!borderRadius) {
                    $select.css('border-radius', '');
                }
                const radius = $select.css('border-radius');
                $select.select2(settings).on('select2:opening', function () {
                    $('.select2-hidden-accessible').each(function () {
                        if ($(this) !== $select) {
                            $(this).select2('close');
                        }
                    });
                }).on('select2:open', function () {
                    const $dropdown = $('.select2-dropdown.select2-dropdown--below');
                    if ($dropdown.length) {
                        $dropdown.addClass('border-top').css('border-radius', radius);
                    }
                    const $search = $('.select2-search--dropdown .select2-search__field');
                    if ($search.length) {
                        $search.addClass('form-control form-control-sm').css('border-radius', radius);
                        $search[0].focus();
                    }
                }).on('change', function () {
                    const $removes = $select.next('.select2').find('.select2-selection__choice__remove');
                    if ($removes.length) {
                        $removes.text('').addClass('border-0 fas fa-times m-0 px-1 bg-transparent');
                        const title = $select.data('delete');
                        if (title) {
                            $removes.attr('title', title);
                        }
                    }
                }).css('width', '');

                if (!multiple) {
                    $select.on('select2:keypress', function (e) {
                       window.console.log(e);
                    });
                    const select2 = $select.data('select2');
                    select2.on('keypress', function (e) {
                        if (!select2.isOpen()) {
                            select2KeyPress($select, e);
                        }
                    });
                }
            });
        },
    });
}(jQuery));
