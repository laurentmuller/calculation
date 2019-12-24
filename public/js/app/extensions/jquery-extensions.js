/**! compression tag for ftp-deployment */

/**
 * -------------- JQuery Extensions --------------
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
         * 
         * @return {boolean} true if a string.
         */
        isString: function (data) {
            return $.type(data) === "string";
        },

        /**
         * Returns if the given data is an object.
         * 
         * @param {any}
         *            data - The data to evaluate.
         * 
         * @return {boolean} true if an object.
         */
        isObject: function (data) {
            return $.type(data) === "object";
        },

        /**
         * Returns if the given data is a boolean.
         * 
         * @param {any}
         *            data - The data to evaluate.
         * 
         * @return {boolean} true if a boolean.
         */
        isBoolean: function (data) {
            return $.type(data) === "boolean";
        },

        /**
         * Returns if the given data is undefined.
         * 
         * @param {any}
         *            data - The data to evaluate.
         * 
         * @return {boolean} true if undefined.
         */
        isUndefined: function (data) {
            return $.type(data) === "undefined"; // || data === null
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
         *            bottomMargin - The bottom margin.
         * 
         * @return {boolean} true if visible, false if not.
         */
        isInViewport: function (bottomMargin) {
            const $this = $(this);
            if ($this.length) {
                if ($.isUndefined(bottomMargin)) {
                    bottomMargin = 0;
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
         *            delay - The scroll animation delay in milliseconds.
         * 
         * @param {int}
         *            bottomMargin - The bottom margin.
         * 
         * @return {Object} The JQuery element.
         */
        scrollInViewport: function (delay, bottomMargin) {
            const $this = $(this);
            try {
                if ($this.length && !$this.isInViewport(bottomMargin)) {
                    if ($.isUndefined(delay)) {
                        delay = 350;
                    }
                    const top = $this.offset().top;
                    const $target = $('html, body');
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
         * Add the given class to the element and remove it after a delay of
         * 1500 ms.
         * 
         * @param {string}
         *            className - The class name to toggle.
         * @param {function}
         *            callback - The function to call after the class has been
         *            removed.
         * @return {Object} The JQuery element.
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
         * Add or remove the given class to the element.
         * 
         * @param {string}
         *            className - The class name to add or remove.
         * @param {boolean}
         *            add - true to add the class, false to remove.
         * @return {Object} The JQuery element.
         */
        updateClass: function (className, add) {
            return $(this).each(function () {
                if (add) {
                    $(this).addClass(className);
                } else {
                    $(this).removeClass(className);
                }
            });
        },

        /**
         * Sets the given class to the element.
         * 
         * @param {string}
         *            className - The class name to set.
         * @return {Object} The JQuery element.
         */
        setClass: function (className) {
            return $(this).each(function () {
                $(this).attr('class', className);
            });
        },

        /**
         * Create a timer within the element. Callback function parameters can
         * be given after the callback and timeout values.
         * 
         * @param {function}
         *            callback - The callback function that will be executed
         *            after the timer expires.
         * @param {int}
         *            timeout - The number of milliseconds to wait before
         *            executing the callback.
         * @return {Object} The JQuery element.
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
         * @return {Object} The JQuery element.
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
         * element.
         * 
         * @param {function}
         *            callback - The callback function that will be executed
         *            after the timer expires.
         * @param {int}
         *            timeout - The number of milliseconds to wait before
         *            executing the callback.
         * @return {Object} The JQuery element.
         */
        updateTimer: function (callback, timeout) {// jshint ignore:line
            $(this).removeTimer();
            return $.fn.createTimer.apply(this, arguments);
        },

        /**
         * Create a timer interval within the element.
         * 
         * Callback function parameters can be given after the callback and
         * timeout values.
         * 
         * @param {function}
         *            callback - The callback function that will be executed.
         * @param {int}
         *            timeout - The intervals (in milliseconds) on how often to
         *            execute the callback.
         * @return {Object} The JQuery element.
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
         * @return {Object} The JQuery element.
         */
        removeInterval: function () {
            const $element = $(this);
            const interval = $element.data('interval');
            if (interval) {
                $element.removeData('interval');
                clearInterval(interval);
            }
            return $element;
        },

        /**
         * Clear the timer interval (if any) and create a timer interval within
         * the element. Callback function parameters can be given after the
         * callback and timeout values.
         * 
         * @param {function}
         *            callback - The callback function that will be executed.
         * @param {int}
         *            timeout - The intervals (in milliseconds) on how often to
         *            execute the callback.
         * 
         * @return {Object} The JQuery element.
         */
        updateInterval: function (callback, timeout) { // jshint ignore:line
            $(this).removeInterval();
            return $.fn.createInterval.apply(this, arguments);
        },

        /**
         * Sets or gets the value as integer.
         * 
         * @param {int}
         *            value - if present the value to set; otherwise return the
         *            value.
         * @return {int} The value if the value parameter is not set.
         */
        intVal: function (value) {
            if (!arguments.length) {
                const parsedValue = Number.parseInt($(this).val(), 10);
                return Number.isNaN(parsedValue) ? 0 : parsedValue;
            } else {
                let parsedValue = Number.parseInt(value, 10);
                if (Number.isNaN(parsedValue)) {
                    parsedValue = Number.parseInt(0, 10);
                }
                return $(this).val(parsedValue.toString());
            }
        },

        /**
         * Sets or gets the value as float with 2 fixed decimals.
         * 
         * @param {number}
         *            value - if present the value to set; otherwise return the
         *            value.
         * @return {number} The value if the value parameter is not set.
         */
        floatVal: function (value) {
            if (!arguments.length) {
                const parsedValue = Number.parseFloat($(this).val());
                return Number.isNaN(parsedValue) ? 0 : parsedValue;
            } else {
                let parsedValue = Number.parseFloat(value);
                if (Number.isNaN(parsedValue)) {
                    parsedValue = Number.parseFloat(0);
                }
                return $(this).val(parsedValue.toFixed(2));
            }
        },

        /**
         * Returns if the checkbox is checked.
         * 
         * @return {boolean} The checked value.
         */
        isChecked: function () {
            return $(this).is(":checked"); // .prop('checked');
        },

        /**
         * Sets the checkbox checked value.
         * 
         * @param {boolean}
         *            the checked value to set.
         */
        setChecked: function (checked) {
            return $(this).each(function () {
                $(this).prop('checked', checked);
            });
        },

        /**
         * Toggle the checkbox checked value
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
         * @return {file|boolean} The first selected file, if any; false if none
         *         or if not valid.
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

                const regex = new RegExp('.?(' + type + ')$', 'i');
                for (let i = 0; i < files.length; i++) {
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
         * Returns the label text of the given input element. Return false if
         * not found.
         * 
         * @return {JQuery|boolean} the label element, if found; false
         *         otherwise.
         */
        getLabelText: function () {
            const $element = $(this);
            const $parent = $element.parents('.form-group');
            if ($parent.length) {
                let $label = $parent.children('label:first');
                if ($label.length) {
                    return $label.text();
                }
                $label = $parent.children('legend:first');
                if ($label.length) {
                    return $label.text();
                }
            }
            return false;
        },

        /**
         * Select content and set focus.
         */
        selectFocus: function () {
            return $(this).select().focus();
        },

        /**
         * Select the first option in the list.
         */
        selectFirstOption: function () {
            const value = $(this).find(':first').val();
            return $(this).val(value);
        },

        /**
         * Gets select option in the list.
         */
        getSelectedOption: function () {
            return $(this).find(':selected');
        },

        /**
         * Get the descendants of each element in the current set of matched
         * elements, filtered by a selector, jQuery object, or element.
         * 
         * Return {JQuery} the selected element or null if matching elements
         * length is equal to 0.
         */
        findExists: function (selector) {
            const $elements = $(this).find(selector);
            return $elements.length ? $elements : null;
        },

        /**
         * Remove all 'data-xxx' attributes.
         * 
         * @returns {JQuery} the caller.
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
         * Creates a custom tooltip.
         * 
         * The options must contains a "className" value used used to update the
         * tooltip class.
         * <p>
         * The className can be: "tooltip-danger", "tooltip-warning",
         * "tooltip-success", "tooltip-info", "tooltip-primary",
         * "tooltip-secondary" or "tooltip-dark".
         * </p>
         * 
         * @param {Object}
         *            options - the additional tooltip options.
         * @returns {JQuery} the caller.
         */
        customTooltip: function (options) {
            if (options.className) {
                const oldClass = '"tooltip"';
                const newClass = '"tooltip ' + options.className + '"';
                const template = $.fn.tooltip.Constructor.Default.template;
                options.template = template.replace(oldClass, newClass);
                delete options.className;
            }
            return $(this).tooltip(options);
        },

        /**
         * Creates a custom popover.
         * 
         * The options must contains a "type" value used to update the popoover
         * class.
         * <p>
         * The allowed type is: "danger", "warning", "success", "info",
         * "primary", "secondary" or "dark".
         * </p>
         * 
         * @param {Object}
         *            options - the additional popover options.
         * @returns {JQuery} the caller.
         */
        customPopover: function (options) {
            if (options.type) {
                let oldClass, newClass;

                const type = options.type;
                let template = $.fn.popover.Constructor.Default.template;

                oldClass = '"popover"';
                newClass = '"popover popover-' + type + '"';
                template = template.replace(oldClass, newClass);

                oldClass = '"arrow"';
                newClass = '"arrow arrow-' + type + '"';
                template = template.replace(oldClass, newClass);

                oldClass = '"popover-header"';
                newClass = '"popover-header popover-header-' + type + '"';
                template = template.replace(oldClass, newClass);

                options.template = template;
                delete options.type;
            }
            return $(this).popover(options);
        }
    });

})(jQuery);
