/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // InputNumberFormat public class definition
    var InputNumberFormat = function (element, options) {
        this.$element = $(element);
        this.options = $.extend(true, {}, InputNumberFormat.DEFAULTS, options);
        this.init();
    };

    InputNumberFormat.DEFAULTS = {
        'decimal': 2,
        'decimalAuto': 2,
        'separator': '.',
        'separatorAuthorized': ['.', ',']
    };

    InputNumberFormat.prototype = {

        constructor: InputNumberFormat,

        init: function () {
            // build regex
            const options = this.options;
            var pattern = "^[0-9]+";
            if (options.decimal) {
                pattern += "[" + options.separatorAuthorized.join("") + "]?[0-9]{0," + options.decimal + "}";
            }
            pattern += "$";
            this.regex = new RegExp(pattern);

            // add handlers
            const $element = this.$element;
            $element.on('keypress', $.proxy(this.keypress, this));
            $element.on('blur', $.proxy(this.blur, this));
            $element.on('change', $.proxy(this.change, this));
        },

        destroy: function () {
            // remove handlers and data
            const $element = this.$element;
            $element.off('keypress', $.proxy(this.keypress, this));
            $element.off('blur', $.proxy(this.blur, this));
            $element.off('change', $.proxy(this.change, this));
            $element.removeData("inputNumberFormat");
        },

        formatValue: function (value) {
            if (!value) {
                return value;
            }

            const options = this.options;
            value = value.replace(",", options.separator);
            if (options.decimal && options.decimalAuto) {
                value = Math.round(value * Math.pow(10, options.decimal)) / Math.pow(10, options.decimal) + "";
                if (value.indexOf(options.separator) === -1) {
                    value += options.separator;
                }
                const decimalsToAdd = options.decimalAuto - value.split(options.separator)[1].length;
                for (let i = 1; i <= decimalsToAdd; i++) {
                    value += "0";
                }
            }

            return value;
        },

        keypress: function (e) {
            if (e.ctrlKey || e.key.length > 1 || e.keyCode === 13) {
                return;
            }

            const $element = this.$element;
            const value = $element.val();
            const beginVal = value.substr(0, e.target.selectionStart);
            const endVal = value.substr(e.target.selectionEnd, value.length - 1);
            const newValue = beginVal + e.key + endVal;
            if (!newValue.match(this.regex)) {
                e.stopPropagation();
                e.preventDefault();
                return;
            }
        },

        blur: function () {
            const oldValue = this.$element.val();
            const newValue = this.formatValue(oldValue);
            if (oldValue !== newValue) {
                this.$element.val(newValue);
            }
        },

        change: function () {
            const oldValue = this.$element.val();
            const newValue = this.formatValue(oldValue);
            if (oldValue !== newValue) {
                this.$element.val(newValue);
            }
        },
    };

    // InputNumberFormat plugin definition
    const oldInputNumberFormat = $.fn.inputNumberFormat;
    $.fn.inputNumberFormat = function (option) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data("inputNumberFormat");
            if (!data) {
                const options = typeof option === "object" && option;
                $this.data("inputNumberFormat", data = new InputNumberFormat(this, options));
            }
        });
    };
    $.fn.inputNumberFormat.Constructor = InputNumberFormat;

    // InputNumberFormat no conflict
    $.fn.inputNumberFormat.noConflict = function () {
        $.fn.inputNumberFormat = oldInputNumberFormat;
        return this;
    };
}(jQuery));