/* globals bootstrap */
(function ($) {
    'use strict';

    /**
     * -------------- jQuery Extensions --------------
     */
    $(function () {
        /**
         * -------------- Expression extensions --------------
         */
        $.extend($.expr[':'], {
            /**
             * Contains case-insensitive
             */
            icontains: $.expr.createPseudo(function (toFind) {
                return function (elem) {
                    const text = (elem.textContent || elem.innerText || '').clean();
                    return text.length && text.indexOf(toFind) !== -1;
                };
            })
        });

        /**
         * -------------- Core extensions --------------
         */
        $.extend({
            /**
             * Returns if the given data is a string.
             *
             * @param {any} data - The data to evaluate.
             * @return {boolean} true if a string.
             */
            isString: function (data) {
                return typeof data === 'string';
            },

            /**
             * Returns if the given data is an object.
             *
             * @param {any} data - The data to evaluate.
             * @return {boolean} true if an object.
             */
            isObject: function (data) {
                return typeof data === 'object';
            },

            /**
             * Returns if the given data is boolean.
             *
             * @param {any} data - The data to evaluate.
             * @return {boolean} true if boolean.
             */
            isBoolean: function (data) {
                return typeof data === 'boolean';
            },

            /**
             * Returns if the given data is undefined.
             *
             * @param {any} data - The data to evaluate.
             * @return {boolean} true if undefined.
             */
            isUndefined: function (data) {
                return typeof data === 'undefined';
            },

            /**
             * Parse the given value as float.
             * If the parsed valus is NaN, 0 is returned.
             *
             * @param {string} value - the value to parse.
             * @returns {number} the parsed value.
             */
            parseFloat: function (value) {
                const parsedValue = Number.parseFloat(value);
                return Number.isNaN(parsedValue) ? 0 : parsedValue;
            },

            /**
             * Parse the given value as integer.
             * If the parsed valus is NaN, 0 is returned.
             *
             * @param {string} value - the value to parse.
             * @returns {number} the parsed value.
             */
            parseInt: function (value) {
                const parsedValue = Number.parseInt(value, 10);
                return Number.isInteger(parsedValue) ? parsedValue : 0;
            },

            /**
             * Rounds the given value with 2 decimals.
             *
             * @param {number} value - the value to round.
             * @returns {number} the rounded value.
             */
            roundValue: function (value) {
                return Math.round((value + Number.EPSILON) * 100) / 100;
            },

            /**
             * Format a value with 0 decimal and the grouping separator.
             *
             * @param {number} value - the value to format.
             * @returns {string} the formatted value.
             */
            formatInt: function (value) {
                if ($.isUndefined($.integerFormatter)) {
                    $.integerFormatter = new Intl.NumberFormat('de-CH', {maximumFractionDigits: 0});
                }
                return $.integerFormatter.format(value);
            },

            /**
             * Parse and format a value with 2 decimals and grouping separator.
             *
             * @param {number} value - the value to format.
             * @returns {string} the formatted value.
             */
            formatFloat: function (value) {
                if ($.isUndefined($.floatFormatter)) {
                    $.floatFormatter = new Intl.NumberFormat('de-CH', {
                        'minimumFractionDigits': 2,
                        'maximumFractionDigits': 2
                    });
                }
                value = $.parseFloat(value);
                return $.floatFormatter.format(value);
            },

            /**
             * Hide the drop-down menus.
             */
            hideDropDownMenus: function () {
                $('.show[data-bs-toggle="dropdown"], .dropdown-menu.show').removeClass('show');
            },

            /**
             * Hide the tooltips.
             */
            hideTooltips: function () {
                $('.show[data-bs-toggle="tooltip"]').removeClass('show');
            }
        });

        /**
         * -------------- Functions extensions --------------
         */
        $.fn.extend({
            /**
             * Scrolls the element into the visible area of the browser window.
             *
             * @return {jQuery} The element for chaining.
             */
            scrollInViewport: function () {
                const margin = 5;
                const $target = $('html, body');
                const footer = $('footer').outerHeight() || 0;

                const $window = $(window);
                const windowTop = $window.scrollTop();
                const windowBottom = windowTop + $window.height() - footer;

                return this.each(function () {
                    const $this = $(this);
                    const top = $this.offset().top;
                    const bottom = top + $this.outerHeight();
                    if (top < windowTop) {
                        $target.scrollTop(top - margin);
                    } else if (bottom > windowBottom) {
                        $target.scrollTop(windowTop + bottom - windowBottom + margin);
                    }
                });
            },

            /**
             * Add the given class to the element and remove it after a delay of 1500 ms.
             *
             * @param {string} className - The class name to toggle.
             * @param {function} [callback] - The function to call after the class has been removed.
             * @return {jQuery} The element for chaining.
             */
            timeoutToggle: function (className, callback) {
                const $this = $(this);
                if ($this.length) {
                    return $this.addClass(className).stop().delay(1500).queue(function () {
                        $this.removeClass(className).dequeue();
                        if (typeof callback === 'function') {
                            callback();
                        }
                    });
                }
                return $this;
            },

            /**
             * Sets the given attribute class name to the element.
             *
             * @param {string} className - The class name to set.
             * @return {jQuery} The element for chaining.
             */
            setClass: function (className) {
                return $(this).each(function () {
                    $(this).attr('class', className);
                });
            },

            /**
             * Create a timer within the element.
             * Callback function parameters can be given after the callback and timeout parameters.
             *
             * @param {function} callback - The callback function that will be executed after the timer expires.
             * @param {int} timeout - The number of milliseconds to wait before executing the callback.
             * @return {jQuery} The element for chaining.
             */
            createTimer: function (callback, timeout) {
                const $element = $(this);
                const args = Array.prototype.slice.call(arguments, 2);
                const id = setTimeout(callback, timeout, ...args);
                return $element.data('timer', id);
            },

            /**
             * Clear the existing timer (if any) of the element.
             *
             * @return {jQuery} The element for chaining.
             */
            removeTimer: function () {
                return $(this).each(function () {
                    const $element = $(this);
                    const timer = $element.data('timer');
                    if (timer) {
                        clearTimeout(timer);
                        $element.removeData('timer');
                    }
                });
            },

            /**
             * Clear the existing timer (if any) and create a new timer within the element.
             * Callback function parameters can be given after the callback and timeout parameters.
             *
             * @param {function} _callback - The callback function that will be executed after the timer expires.
             * @param {int} _timeout - The number of milliseconds to wait before executing the callback.
             * @return {jQuery} The element for chaining.
             */
            updateTimer: function (_callback, _timeout) {
                const args = Array.prototype.slice.call(arguments, 2);
                $(this).removeTimer().createTimer(_callback, _timeout, ...args);
            },

            /**
             * Sets or gets the value as integer.
             *
             * @param {number|string} [value] - if present the value to set; otherwise return the value.
             * @return {jQuery|number} The value if the value parameter is not set.
             */
            intVal: function (value) {
                // get?
                if (!arguments.length) {
                    return $.parseInt($(this).val());
                }

                // set
                const parsedValue = $.parseInt(value);
                return $(this).val(parsedValue.toString());
            },

            /**
             * Sets or gets the value as float with 2 fixed decimals.
             *
             * @param {number|string} [value] - if present the value to set; otherwise return the value.
             * @return {number} The value if the value parameter is not set.
             */
            floatVal: function (value) {
                // get?
                if (!arguments.length) {
                    return $.parseFloat($(this).val());
                }

                // set
                const parsedValue = $.parseFloat(value);
                return $(this).val(parsedValue.toFixed(2));
            },

            /**
             * Returns if the checkbox is checked.
             *
             * @return {boolean} The checked value.
             */
            isChecked: function () {
                return $(this).is(':checked');
            },

            /**
             * Sets the checkbox-checked value.
             *
             * @param {boolean} checked - the checked value to set.
             * @return {jQuery} The element for chaining.
             */
            setChecked: function (checked) {
                return $(this).each(function () {
                    $(this).prop('checked', checked);
                });
            },

            /**
             * Toggle the checkbox checked value
             *
             * @return {jQuery} The element for chaining.
             */
            toggleChecked: function () {
                return $(this).each(function () {
                    const $this = $(this);
                    $this.setChecked(!$this.isChecked());
                });
            },

            /**
             * Select content and set focus.
             *
             * @return {jQuery} The element for chaining.
             */
            selectFocus: function () {
                $(this).focus().select();
                return $(this);
            },

            /**
             * Select the first option in the list.
             *
             * @return {jQuery} The element for chaining.
             */
            selectFirstOption: function () {
                const value = $(this).find(':first').val();
                return $(this).val(value);
            },

            /**
             * Gets the selected option in the list.
             *
             * @return {?jQuery<HTMLOptionElement>} the selected element, if any; null otherwise.
             */
            getSelectedOption: function () {
                return $(this).find(':selected');
            },

            /**
             * Get the descendants of each element in the current set of matched elements, filtered by a selector,
             * jQuery object, or element.
             *
             * @param {string} selector - a string containing a selector expression to match elements against.
             * @return {?jQuery} the selected element or null if matching element's length is equal to 0.
             */
            findExists: function (selector) {
                const $elements = $(this).find(selector);
                return $elements.length ? $elements : null;
            },

            /**
             * Toggle the disabled attribute.
             *
             * @param {boolean} state - true to add the attribute (disabled); false to remove
             *            attribute (enabled).
             */
            toggleDisabled: function (state) {
                return this.each(function () {
                    const $this = $(this);
                    if (state) {
                        $this.addClass('disabled').attr({
                            'disabled': 'disabled',
                            'aria-disabled': 'true'
                        });
                        if ($this.is('a')) {
                            $this.attr('tabindex', -1);
                        }
                    } else {
                        $this.removeClass('disabled').removeAttr('disabled aria-disabled');
                        if ($this.is('a')) {
                            $this.removeAttr('tabindex');
                        }
                    }
                });
            },

            /**
             * Returns if the given attribute exists and is not false or null.
             *
             * @param {string} name - the attribute name to check existence for.
             * @return {boolean} true if the attribute name is set.
             */
            hasAttr: function (name) {
                const attr = $(this).attr(name);
                return !$.isUndefined(attr) && attr !== false && attr !== null;
            },

            /**
             * Drop first, last, and all 2 consecutive separators in a drop-down menu.
             *
             * @return {jQuery} - the element for chaining.
             */
            removeSeparators: function () {
                const selector = 'li:has(.dropdown-divider),.dropdown-divider';
                return this.each(function () {
                    const $this = $(this);
                    if ($this.is('.dropdown-menu')) {
                        // remove firsts
                        while ($this.children().first().is(selector)) {
                            $this.children().first().remove();
                        }
                        // remove lasts
                        while ($this.children().last().is(selector)) {
                            $this.children().last().remove();
                        }
                        // remove 2 consecutive separators
                        let previewSeparator = false;
                        $this.children().each(function (index, element) {
                            const $item = $(element);
                            const isSeparator = $item.is(selector);
                            if (previewSeparator && isSeparator) {
                                $item.remove();
                            } else {
                                previewSeparator = isSeparator;
                            }
                        });
                    }
                });
            },

            /**
             * Replace the d-none class by the display none style.
             *
             * @return {jQuery} - the element for chaining.
             */
            replaceDisplayNone: function () {
                return this.each(function () {
                    const $this = $(this);
                    if ($this.hasClass('d-none')) {
                        $this.css('display', 'none').removeClass('d-none');
                    }
                });
            }
        });

        /**
         * -------------- Global Ajax functions --------------
         */
        $(document).ajaxStart(function () {
            $('*').css('cursor', 'wait');
        });
        $(document).ajaxStop(function () {
            $('*').css('cursor', '');
        });

        /**
         * --------- Global modal dialog functions -----------
         */
        window.addEventListener('hide.bs.modal', () => {
            if (document.activeElement) {
                document.activeElement.blur();
            }
        });
    });
}(jQuery));
