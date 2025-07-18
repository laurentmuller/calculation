/**
 * Plugin to validate and format number input.
 */
$(function () {
    'use strict';

    // ------------------------------------------
    // InputNumberFormat public class definition
    // ------------------------------------------

    /**
     * @property {JQuery<HTMLInputElement>} $element
     */
    const InputNumberFormat = class {
        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLInputElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, InputNumberFormat.DEFAULTS, options);
            this._init();
        }

        /**
         * Remove data and handlers.
         */
        destroy() {
            this.$element.removeData(InputNumberFormat.NAME)
                .off('blur', this.updateProxy)
                .off('change', this.updateProxy)
                .off('input', this.inputProxy)
                .off('keypress', this.keyPressProxy);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize this plugin.
         * @private
         */
        _init() {
            const options = this.options;
            const separators = '[' + options.separatorAuthorized.join('') + ']';

            // regex for input event
            this.inputRegex = new RegExp(separators);

            // regex for keypress event
            let pattern = '^[0-9]+';
            if (options.decimal) {
                pattern += separators + '?[0-9]{0,' + options.decimal + '}';
            }
            pattern += '$';
            this.keypressRegex = new RegExp(pattern);

            // create proxies
            this.keyPressProxy = (e) => this._keypress(e);
            this.updateProxy = () => this._update();
            this.inputProxy = (e) => this._input(e);

            // add handlers
            const $element = this.$element;
            $element.on('blur change', this.updateProxy);
            if ($element[0].selectionStart === null) {
                $element.on('input', this.inputProxy);
            } else {
                $element.on('keypress', this.keyPressProxy);
            }

            // format
            this._update();
        }

        /**
         * @param {KeyboardEvent} e
         * @private
         */
        _keypress(e) {
            if (e.ctrlKey || e.key.length > 1 || e.key === 'Enter') {
                return;
            }

            // applicable?
            const selectionStart = e.target.selectionStart;
            const selectionEnd = e.target.selectionEnd;
            if (selectionStart === null || selectionEnd === null) {
                return;
            }

            const $element = this.$element;
            /** @type {string} */
            const value = $element.val();
            const beginVal = value.substring(0, selectionStart);
            const endVal = value.substring(selectionEnd, value.length - 1);
            const newValue = beginVal + e.key + endVal;
            if (!newValue.match(this.keypressRegex)) {
                e.preventDefault();
            }
        }

        /**
         * Handle input event.
         * @param {Event} e
         * @private
         */
        _input(e) {
            const options = this.options;
            const decimal = options.decimal;
            const $element = this.$element;
            /** @type {string} */
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

        /**
         * Update input value.
         * @private
         */
        _update() {
            /** @type {string} */
            const oldValue = this.$element.val();
            const newValue = this._formatValue(oldValue);
            if (oldValue !== newValue) {
                this.$element.val(newValue);
            }
        }

        /**
         * Format the given value.
         * @param {string} value
         * @return {string}
         * @private
         */
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

    // -----------------------------
    // Default options
    // -----------------------------
    InputNumberFormat.DEFAULTS = {
        'decimal': 2,
        'decimalAuto': 2,
        'separator': '.',
        'separatorAuthorized': ['.', ',']
    };

    // -----------------------------
    // The plugin name.
    // -----------------------------
    InputNumberFormat.NAME = 'bs.input-number-format';

    // ------------------------------------
    // InputNumberFormat plugin definition
    // ------------------------------------
    const oldInputNumberFormat = $.fn.inputNumberFormat;
    $.fn.inputNumberFormat = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(InputNumberFormat.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(InputNumberFormat.NAME, new InputNumberFormat(this, settings));
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
});
