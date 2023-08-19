/**! compression tag for ftp-deployment */

/* globals bootstrap */

/**
 * -------------- jQuery Extensions --------------
 */
(function ($) {
    'use strict';

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
         * Returns if the given data is a boolean.
         *
         * @param {any} data - The data to evaluate.
         * @return {boolean} true if a boolean.
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
         * Parse the given value as float. If the parsed valus is NaN, 0 is
         * returned.
         *
         * @param {string} value - the value to parse.
         * @returns {number} the parsed value.
         */
        parseFloat: function (value) {
            const parsedValue = Number.parseFloat(value);
            return Number.isNaN(parsedValue) ? 0 : parsedValue;
        },

        /**
         * Parse the given value as integer. If the parsed valus is NaN, 0 is
         * returned.
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
         * Format a value with 0 decimal and grouping separator.
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
        }
    });

    /**
     * -------------- Functions extensions --------------
     */
    $.fn.extend({

        /**
         * Check if the element is visible into area of the browser window.
         *
         * @param {int} bottomMargin - The bottom margin (default to 50).
         * @return {boolean} true if visible, false if not.
         */
        isInViewport: function (bottomMargin = 50) {
            const $this = $(this);
            if ($this.length) {
                if ($.isUndefined(bottomMargin)) {
                    bottomMargin = 50;
                }
                const top = $this.offset().top;
                const bottom = top + $this.outerHeight();
                const $window = $(window);
                const windowTop = $window.scrollTop();
                const windowBottom = windowTop + $window.height() - bottomMargin;
                return top >= windowTop && bottom <= windowBottom;
            }
            return false;
        },

        /**
         * Scrolls the element into the visible area of the browser window.
         *
         * @param {int} delay - The scroll animation delay in milliseconds (default to 400).
         * @param {number} bottomMargin - The bottom margin (default to 50).
         * @return {jQuery} The element for chaining.
         */
        scrollInViewport: function (delay = 400, bottomMargin = 50) {
            const $target = $('html, body');
            return this.each(function () {
                const $this = $(this);
                try {
                    if (!$this.isInViewport(bottomMargin)) {
                        let top = $this.offset().top;
                        if (delay) {
                            $target.animate({
                                scrollTop: top
                            }, delay);
                        } else {
                            $target.scrollTop(top);
                        }
                    }
                } catch (e) {
                    // ignore
                }
            });
        },

        /**
         * Add the given class to the element and remove it after a delay of
         * 1500 ms.
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
         * Create a timer within the element. Callback function parameters can be given after the callback
         * and timeout parameters.
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
         * Clear the existing timer (if any) and create a new timer within the
         * element. Callback function parameters can be given after the callback
         * and timeout parameters.
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
         * Create a timer interval within the element. Callback function
         * parameters can be given after the callback and timeout values.
         *
         * @param {function} callback - The callback function that will be executed.
         * @param {int} timeout - The intervals (in milliseconds) on how often to execute the callback.
         * @return {jQuery} The jQuery element for chaining.
         */
        createInterval: function (callback, timeout) {
            const $element = $(this);
            const args = Array.prototype.slice.call(arguments, 2);
            const id = setInterval(callback, timeout, ...args);
            return $element.data('interval', id);
        },

        /**
         * Clear the existing timer interval (if any) of the element.
         *
         * @return {jQuery} The element for chaining.
         */
        removeInterval: function () {
            return $(this).each(function () {
                const $element = $(this);
                const interval = $element.data('interval');
                if (interval) {
                    clearInterval(interval);
                    $element.removeData('interval');
                }
            });
        },

        /**
         * Clear the existing timer interval (if any) and create a new timer interval within
         * the element. Callback function parameters can be given after the callback and timeout values.
         *
         * @param {function} _callback - The callback function that will be executed.
         * @param {int} _timeout - The intervals (in milliseconds) on how often to execute the callback.
         * @return {jQuery} The element for chaining.
         */
        updateInterval: function (_callback, _timeout) {
            const args = Array.prototype.slice.call(arguments, 2);
            $(this).removeInterval().createInterval(_callback, _timeout, ...args);
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
         * Sets the checkbox checked value.
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
         * Returns the first file (if any) from an input type file.
         *
         * @return {file|boolean} The first selected file, if any; false if none or if not valid.
         */
        getInputFile: function () {
            // check files
            const files = $(this).getInputFiles();
            if (!files.length) {
                return false;
            }

            // check type (copied from accept method)
            let type = $(this).attr('accept') || false;
            if (type) {
                type = type.replace(/[\-\[\]\/\{\}\(\)\+\?\.\\\^\$\|]/g, "\\$&")
                    .replace(/,/g, "|")
                    .replace(/\/\*/g, "/.*");
                const pattern = `.?(${type})$`;
                const regex = new RegExp(pattern, 'i');
                for (let i = 0, len = files.length; i < len; i++) {
                    if (!files[i].type.match(regex)) {
                        return false;
                    }
                }
            }

            // OK
            return files[0];
        },

        /**
         * Returns the files (if any) from an input type file.
         *
         * @return {Array.<File>} The selected files, if any; an empty array otherwise.
         */
        getInputFiles: function () {
            const files = this[0].files;
            return files && files.length ? files : [];
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
         * Gets select option in the list.
         *
         * @return {?jQuery} the selected element, if any; null otherwise.
         */
        getSelectedOption: function () {
            return $(this).find(':selected');
        },

        /**
         * Get the descendants of each element in the current set of matched
         * elements, filtered by a selector, jQuery object, or element.
         *
         * @param {string} selector - a string containing a selector expression to match elements against.
         * @return {?jQuery} the selected element or null if matching element's length is equal to 0.
         */
        findExists: function (selector) {
            const $elements = $(this).find(selector);
            return $elements.length ? $elements : null;
        },

        /**
         * Remove all 'data' attributes.
         *
         * @return {jQuery} The element for chaining.
         */
        removeDataAttributes: function () {
            return $(this).each(function () {
                const $element = $(this);
                $.each($element.data(), function (key) {
                    $element.removeAttr('data-' + key.dasherize());
                });
            });
        },

        /**
         * Remove duplicate classes.
         *
         * @return {jQuery} The element for chaining.
         */
        removeDuplicateClasses: function () {
            return this.each(function (_index, element) {
                const $this = $(element);
                const source = $this.attr('class');
                const target = source.split(' ').unique();
                $this.attr('class', target.join(' '));
            });
        },

        /**
         * Toggle the disabled attribute.
         *
         * @param {boolean} state - true to add attribute (disabled); false to remove
         *            attribute (enabled).
         */
        toggleDisabled: function (state) {
            return this.each(function () {
                if (state) {
                    $(this).addClass('disabled').attr({
                        'disabled': 'disabled',
                        'aria-disabled': 'true'
                    });
                } else {
                    $(this).removeClass('disabled').removeAttr('disabled aria-disabled');
                }
            });
        },

        /**
         * Returns if the given attribute exist and is not false or null.
         *
         * @param {string} name - the attribute name to check existence for.
         * @return {boolean} true if the attribute name is set.
         */
        hasAttr: function (name) {
            const attr = $(this).attr(name);
            return !$.isUndefined(attr) && attr !== false && attr !== null;
        },

        /**
         * Drop first, last and all 2 consecutive separators in a drop-down menu.
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
    });

    if (!$.fn.popover) {
        $.fn.popover = function (options = {}) {
            return this.each(function () {
                return new bootstrap.Popover(this, options);
            });
        };
    }

    if (!$.fn.tooltip) {
        $.fn.tooltip = function (options = {}) {
            return this.each(function () {
                return new bootstrap.Tooltip(this, options);
            });
        };
    }

    // if (!$.fn.toast) {
    //     $.fn.toast = function (options = {}) {
    //         return this.each(function () {
    //             const data = new bootstrap.Toast(this, options);
    //             return data;
    //         });
    //     };
    // }

    /**
     * -------------- Global Ajax functions --------------
     */
    $(document).ajaxStart(function () {
        $('*').css('cursor', 'wait');
    });
    $(document).ajaxStop(function () {
        $('*').css('cursor', '');
    });
}(jQuery));
