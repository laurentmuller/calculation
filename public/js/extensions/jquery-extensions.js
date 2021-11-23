/**! compression tag for ftp-deployment */

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
         * @param {any}
         *            data - The data to evaluate.
         * @return {boolean} true if a string.
         */
        isString: function (data) {
            return $.type(data) === 'string';
        },

        /**
         * Returns if the given data is an object.
         * 
         * @param {any}
         *            data - The data to evaluate.
         * @return {boolean} true if an object.
         */
        isObject: function (data) {
            return $.type(data) === 'object';
        },

        /**
         * Returns if the given data is a boolean.
         * 
         * @param {any}
         *            data - The data to evaluate.
         * @return {boolean} true if a boolean.
         */
        isBoolean: function (data) {
            return $.type(data) === 'boolean';
        },

        /**
         * Returns if the given data is undefined.
         * 
         * @param {any}
         *            data - The data to evaluate.
         * @return {boolean} true if undefined.
         */
        isUndefined: function (data) {
            return $.type(data) === 'undefined';
        },

        /**
         * Returns if the input fields have a radius style.
         * 
         * @return {boolean} true if a border radius is set.
         */
        isBorderRadius: function () {
            if ($.isUndefined(window.isBorderRadius)) {
                const $input = $('<input>', {
                    'class': 'form-control'
                });
                $('body').append($input);
                const border = $input.css('border-radius');
                $input.remove();
                window.isBorderRadius = border && border !== '0px';
            }
            return window.isBorderRadius;
        }
    });

    /**
     * -------------- Functions extensions --------------
     */
    $.fn.extend({

        /**
         * Check if the element is visible into area of the browser window.
         * 
         * @param {int}
         *            bottomMargin - The bottom margin (default to 60).
         * @return {boolean} true if visible, false if not.
         */
        isInViewport: function (bottomMargin) {
            const $this = $(this);
            if ($this.length) {
                if ($.isUndefined(bottomMargin)) {
                    bottomMargin = 60;
                }
                const top = $this.offset().top;
                const bottom = top + $this.outerHeight();
                const $window = $(window);
                const windowTop = $window.scrollTop();
                const windowBottom = windowTop + $window.height() - bottomMargin;
                return bottom > windowTop && top < windowBottom;
            }
            return false;
        },

        /**
         * Scrolls the element into the visible area of the browser window.
         * 
         * @param {int}
         *            delay - The scroll animation delay in milliseconds (default to 400).
         * @param {int}
         *            bottomMargin - The bottom margin (default to 60).
         * @return {jQuery} The jQuery element for chaining.
         */
        scrollInViewport: function (delay, bottomMargin) {
            const $this = $(this);
            try {
                if ($this.length && !$this.isInViewport(bottomMargin)) {
                    const top = $this.offset().top;
                    const $target = $('html, body');
                    if ($.isUndefined(delay)) {
                        delay = 400;
                    }
                    if (delay > 0) {
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
            return $this;
        },

        /**
         * Add the given class to the element and remove it after a delay of 1500 ms.
         * 
         * @param {string}
         *            className - The class name to toggle.
         * @param {function}
         *            callback - The function to call after the class has been removed.
         * @return {jQuery} The jQuery element for chaining.
         */
        timeoutToggle: function (className, callback) {
            const $this = $(this);
            if ($this.length) {
                return $this.addClass(className).stop().delay(1500).queue(function () {
                    $this.removeClass(className).dequeue();
                    if ($.isFunction(callback)) {
                        callback();
                    }
                });
            }
            return $this;
        },

        /**
         * Sets the given attribute class name to the element.
         * 
         * @param {string}
         *            className - The class name to set.
         * @return {jQuery} The jQuery element for chaining.
         */
        setClass: function (className) {
            return $(this).each(function () {
                $(this).attr('class', className);
            });
        },

        /**
         * Create a timer within the element. Callback function parameters can be given after the callback and timeout values.
         * 
         * @param {function}
         *            callback - The callback function that will be executed after the timer expires.
         * @param {int}
         *            timeout - The number of milliseconds to wait before executing the callback.
         * @return {jQuery} The jQuery element for chaining.
         */
        createTimer: function (callback, timeout) {
            let id;
            const $element = $(this);
            const args = Array.prototype.slice.call(arguments, 2);
            if (args.length) {
                const wrapper = function () {
                    callback.apply(this, args);
                };
                id = setTimeout(wrapper, timeout);
            } else {
                id = setTimeout(callback, timeout);
            }
            return $element.data('timer', id);
        },

        /**
         * Clear the timer (if any) of the element.
         * 
         * @return {jQuery} The jQuery element for chaining.
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
         * 
         * @param {function}
         *            _callback - The callback function that will be executed after the timer expires.
         * @param {int}
         *            _timeout - The number of milliseconds to wait before executing the callback.
         * @return {jQuery} The jQuery element for chaining.
         */
        updateTimer: function (_callback, _timeout) {// jshint ignore:line
            $(this).removeTimer();
            return $.fn.createTimer.apply(this, arguments);
        },

        /**
         * Create a timer interval within the element. Callback function parameters can be given after the callback and timeout values.
         * 
         * @param {function}
         *            callback - The callback function that will be executed.
         * @param {int}
         *            timeout - The intervals (in milliseconds) on how often to execute the callback.
         * @return {jQuery} The jQuery element for chaining.
         */
        createInterval: function (callback, timeout) {
            let id;
            const $element = $(this);
            const args = Array.prototype.slice.call(arguments, 2);
            if (args.length) {
                const wrapper = function () {
                    callback.apply(this, args);
                };
                id = setInterval(wrapper, timeout);
            } else {
                id = setInterval(callback, timeout);
            }
            return $element.data('interval', id);
        },

        /**
         * Clear the timer interval (if any) of the element.
         * 
         * @return {jQuery} The jQuery element for chaining.
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
         * Clear the timer interval (if any) and create a timer interval within the element. Callback function parameters can be given after the callback and timeout values.
         * 
         * @param {function}
         *            _callback - The callback function that will be executed.
         * @param {int}
         *            _timeout - The intervals (in milliseconds) on how often to execute the callback.
         * @return {jQuery} The jQuery element for chaining.
         */
        updateInterval: function (_callback, _timeout) { // jshint ignore:line
            $(this).removeInterval();
            return $.fn.createInterval.apply(this, arguments);
        },

        /**
         * Sets or gets the value as integer.
         * 
         * @param {int}
         *            value - if present the value to set; otherwise return the value.
         * @return {int} The value if the value parameter is not set.
         */
        intVal: function (value) {
            // get?
            if (!arguments.length) {
                const parsedValue = Number.parseInt($(this).val(), 10);
                return Number.isNaN(parsedValue) ? 0 : parsedValue;
            }

            // set
            let parsedValue = Number.parseInt(value, 10);
            if (isNaN(parsedValue) || parsedValue === -0) {
                parsedValue = Number.parseInt(0, 10);
            }
            return $(this).val(parsedValue.toString());
        },

        /**
         * Sets or gets the value as float with 2 fixed decimals.
         * 
         * @param {number}
         *            value - if present the value to set; otherwise return the value.
         * @return {number} The value if the value parameter is not set.
         */
        floatVal: function (value) {
            // get?
            if (!arguments.length) {
                const parsedValue = Number.parseFloat($(this).val());
                return Number.isNaN(parsedValue) ? 0 : parsedValue;
            }

            // set
            let parsedValue = Number.parseFloat(value);
            if (Number.isNaN(parsedValue) || parsedValue === -0) {
                parsedValue = Number.parseFloat(0);
            }
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
         * @param {boolean}
         *            checked - the checked value to set.
         * @return {jQuery} The jQuery element for chaining.
         */
        setChecked: function (checked) {
            return $(this).each(function () {
                $(this).prop('checked', checked);
            });
        },

        /**
         * Toggle the checkbox checked value
         * 
         * @return {jQuery} The jQuery element for chaining.
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
                type = type.replace(/[\-\[\]\/\{\}\(\)\+\?\.\\\^\$\|]/g, '\\$&');
                type = type.replace(/,/g, '|');
                type = type.replace(/\/\*/g, '/.*');

                const pattern = '.?(' + type + ')$';
                const flags = 'i';
                const regex = new RegExp(pattern, flags);
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
         * @return {array} The selected files, if any; an empty array otherwise.
         */
        getInputFiles: function () {
            const files = this[0].files;
            return files && files.length ? files : [];
        },

        /**
         * Select content and set focus.
         * 
         * @return {jQuery} The jQuery element for chaining.
         */
        selectFocus: function () {
            $(this).focus().select();
            return $(this);
        },

        /**
         * Select the first option in the list.
         * 
         * @return {jQuery} The jQuery element for chaining.
         */
        selectFirstOption: function () {
            const value = $(this).find(':first').val();
            return $(this).val(value);
        },

        /**
         * Gets select option in the list.
         * 
         * @return {Object|null} the selected element, if any; null otherwise.
         */
        getSelectedOption: function () {
            return $(this).find(':selected');
        },

        /**
         * Get the descendants of each element in the current set of matched elements, filtered by a selector, jQuery object, or element.
         * 
         * @param {string}
         *            selector - a string containing a selector expression to match elements against.
         * @return {jQuery} the selected element or null if matching elements length is equal to 0.
         */
        findExists: function (selector) {
            const $elements = $(this).find(selector);
            return $elements.length ? $elements : null;
        },

        /**
         * Remove all 'data' attributes.
         * 
         * @return {jQuery} The jQuery element for chaining.
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
         * @return {jQuery} The jQuery element for chaining.
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
         * @param {boolean}
         *            state - true to add attribute (disabled); false to remove attribute (enabled).
         */
        toggleDisabled: function (state) {
            return this.each(function () {
                if (state) {
                    $(this).addClass('disabled').attr('disabled', 'disabled').attr('aria-disabled', 'true');
                } else {
                    $(this).removeClass('disabled').removeAttr('disabled aria-disabled');
                }
            });
        },

        /**
         * Returns if the given attribute exist and is not false or null.
         * 
         * @param {string}
         *            name - the attribute name to check existance for.
         * @return {boolean} true if the attribute name is set.
         */
        hasAttr: function (name) {
            const attr = $(this).attr(name);
            return typeof attr !== "undefined" && attr !== false && attr !== null;
        }
    });

}(jQuery));
