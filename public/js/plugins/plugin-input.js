/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------------
    // InputNumberFormat public class definition
    // ------------------------------------------
    const InputNumberFormat = class {
        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, InputNumberFormat.DEFAULTS, options);
            this._init();
        }

        /**
         * Remove handlers and data.
         */
        destroy() {
            const $element = this.$element;
            $element.off('blur', this.updateProxy);
            $element.off('change', this.updateProxy);
            $element.off('input', this.inputProxy);
            $element.off('keypress', this.keyPressProxy);
            $element.removeData('input-number-format');
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize this plugin.
         * @private
         */
        _init() {
            const that = this;
            const options = that.options;
            const separators = '[' +  options.separatorAuthorized.join('') + ']';

            // regex for input event
            that.inputRegex = new RegExp(separators);

            // regex for keypress event
            let pattern = '^[0-9]+';
            if (options.decimal) {
                pattern += separators + '?[0-9]{0,' + options.decimal + '}';
            }
            pattern += '$';
            that.keypressRegex = new RegExp(pattern);

            // create proxies
            that.keyPressProxy = function (e) {
                that._keypress(e);
            };
            that.updateProxy = function () {
                that._update();
            };
            that.inputProxy = function (e) {
                that._input(e);
            };

            // add handlers
            const $element = this.$element;
            $element.on('blur', that.updateProxy);
            $element.on('change', that.updateProxy);
            if ($element[0].selectionStart === null) {
                $element.on('input', that.inputProxy);
            } else {
                $element.on('keypress', that.keyPressProxy);
            }

            // format
            that._update();
        }

        _keypress(e) {
            if (e.ctrlKey || e.key.length > 1 || e.keyCode === 13) {
                return;
            }

            // applicable?
            const selectionStart = e.target.selectionStart;
            const selectionEnd = e.target.selectionEnd;
            if (selectionStart === null || selectionEnd === null) {
                return;
            }

            const $element = this.$element;
            const value = $element.val();
            const beginVal = value.substring(0, selectionStart);
            const endVal = value.substring(selectionEnd, value.length - 1);
            const newValue = beginVal + e.key + endVal;
            if (!newValue.match(this.keypressRegex)) {
                e.preventDefault();
            }
        }

        _input(e) {
            const options = this.options;
            const decimal = options.decimal;
            const $element = this.$element;
            const value = $element.val();
            const parts = value.split(this.inputRegex);
            if (decimal > 0 && parts.length === 2 && parts[1].length > decimal) {
                $element.val(parts[0] + options.separator + parts[1].substring(0, decimal));
                e.preventDefault();
            } else if (decimal === 0 && parts.length > 1) {
                $element.val(parts[0]);
                e.preventDefault();
            }
        }

        _update() {
            const oldValue = this.$element.val();
            const newValue = this._formatValue(oldValue);
            if (oldValue !== newValue) {
                this.$element.val(newValue);
            }
        }

        _formatValue(value) {
            if (!value) {
                return value;
            }

            const options = this.options;
            const decimal = options.decimal;
            value = value.replace(',', options.separator);
            if (decimal && options.decimalAuto) {
                value = Math.round(value * Math.pow(10, decimal)) / Math.pow(10, decimal) + '';
                if (value.indexOf(options.separator) === -1) {
                    value += options.separator;
                }
                const decimals = options.decimalAuto - value.split(options.separator)[1].length;
                if (decimals > 0) {
                    value += '0'.repeat(decimals);
                }
            } else if (decimal === 0) {
                const index = value.indexOf(options.separator);
                if (index !== -1) {
                    value = value.substring(0, index);
                }
            }

            return value;
        }
    };

    InputNumberFormat.DEFAULTS = {
        'decimal': 2,
        'decimalAuto': 2,
        'separator': '.',
        'separatorAuthorized': ['.', ',']
    };

    // ------------------------------------
    // InputNumberFormat plugin definition
    // ------------------------------------
    const oldInputNumberFormat = $.fn.inputNumberFormat;

    $.fn.inputNumberFormat = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data('input-number-format')) {
                const settings = typeof options === 'object' && options;
                $this.data('input-number-format', new InputNumberFormat(this, settings));
            }
        });
    };

    $.fn.inputNumberFormat.Constructor = InputNumberFormat;

    // ------------------------------------
    // InputNumberFormat no conflict
    // ------------------------------------
    $.fn.inputNumberFormat.noConflict = function () {
        $.fn.inputNumberFormat = oldInputNumberFormat;
        return this;
    };
}(jQuery));
