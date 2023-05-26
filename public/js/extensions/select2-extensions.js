/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * -------------- jQuery functions extensions --------------
     */
    $.fn.extend({

        /**
         * Update selection on key press event when selection is not open.
         */
        handleSelect2KeyDown: function () {
            const $this = $(this);
            if ($this.hasAttr('multiple')) {
                return;
            }

            /* @param {KeyboardEvent} e - the event. */
            $this.data('select2').on('keypress', function (e) {
                if (this.isOpen()) {
                    return;
                }
                if (e.ctrlKey || e.altKey || e.shiftKey) {
                    return;
                }

                let newIndex = -1;
                const options = $this.find('option:enabled').toArray();
                const selection = $this.find('option:enabled:selected')[0];
                const oldIndex = options.indexOf(selection);
                const lastIndex = options.length - 1;

                switch (e.key) {
                    case 'PageUp':
                        newIndex = Math.max(oldIndex - 5, 0);
                        break;
                    case 'PageDown':
                        newIndex = Math.min(oldIndex + 5, lastIndex);
                        break;
                    case 'End':
                        newIndex = lastIndex;
                        break;
                    case 'Home':
                        newIndex = 0;
                        break;
                    case 'ArrowUp':
                        newIndex = Math.max(oldIndex - 1, 0);
                        break;
                    case 'ArrowDown':
                        newIndex = Math.min(oldIndex + 1, lastIndex);
                        break;
                    default:
                        // find from after index to end
                        if (newIndex === -1) {
                            for (let i = oldIndex + 1; i <= lastIndex; i++) {
                                if (options[i].text.startsWithIgnoreCase(e.key)) {
                                    newIndex = i;
                                    break;
                                }
                            }
                        }
                        // find from start to before index
                        if (newIndex === -1) {
                            for (let i = 0; i < oldIndex; i++) {
                                if (options[i].text.startsWithIgnoreCase(e.key)) {
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
                    $this.val(value).trigger('change').trigger('input');
                    e.preventDefault();
                }
            });
        },

        /**
         * Initialize a select with select2 plugin.
         *
         * @param {object} [options] - The select2 options.
         */
        initSelect2: function (options) {
            return this.each(function () {
                const $select = $(this);
                const settings = $.extend(true, {
                    theme: 'bootstrap-5',
                    placeholder: $select.data('placeholder'),
                    allowClear: Boolean($select.data('allow-clear')),
                    width: $select.data('width') ? $select.data('width') : $select.hasClass('w-100') ? '100%' : 'style'
                }, options);
                $select.select2(settings).on('select2:opening', function () {
                    $('.select2-hidden-accessible').each(function () {
                        $(this).select2('close');
                    });
                }).on('select2:open', function (e) {
                    const $target = $(e.currentTarget);
                    if (!$target.attr('multiple')) {
                        const $search = $target.parent().find('.select2-search--dropdown .select2-search__field');
                        if ($search.length) {
                            $search[0].focus();
                        }
                    }
                });

                // handle key down
                if (!$select.hasAttr('multiple')) {
                    $select.handleSelect2KeyDown();
                }
            });
        },
    });
}(jQuery));
