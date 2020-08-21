/**! compression tag for ftp-deployment */

/**
 * JQuery
 */
(function ($) {
    'use strict';

    $.fn.extend({
        inputNumberFormat: function (options) {
            this.defaultOptions = {
                'decimal': 2,
                'decimalAuto': 2,
                'separator': '.',
                'separatorAuthorized': ['.', ',']
            };

            const settings = $.extend({}, this.defaultOptions, options);

            var matchValue = function (value, options) {
                var pattern = "^[0-9]+";
                if (options.decimal) {
                    pattern += "[" + options.separatorAuthorized.join("") + "]?[0-9]{0," + options.decimal + "}";
                }
                pattern += "$";
                const regex = new RegExp(pattern);
                return value.match(regex);
            };

            var formatValue = function (value, options) {
                let formatedValue = value;
                if (!formatedValue) {
                    return formatedValue;
                }

                formatedValue = formatedValue.replace(",", options.separator);
                if (options.decimal && options.decimalAuto) {
                    formatedValue = Math.round(formatedValue * Math.pow(10, options.decimal)) / Math.pow(10, options.decimal) + "";
                    if (formatedValue.indexOf(options.separator) === -1) {
                        formatedValue += options.separator;
                    }
                    const decimalsToAdd = options.decimalAuto - formatedValue.split(options.separator)[1].length;
                    for (let i = 1; i <= decimalsToAdd; i++) {
                        formatedValue += "0";
                    }
                }

                return formatedValue;
            };

            return this.each(function () {
                const $this = $(this);
                const options = $.extend({}, settings, $(this).data());

                $this.on('keypress', function (e) {
                    if (e.ctrlKey || e.key.length > 1 || e.keyCode === 13) {
                        return;
                    }

                    const beginVal = $(this).val().substr(0, e.target.selectionStart);
                    const endVal = $(this).val().substr(e.target.selectionEnd, $(this).val().length - 1);
                    const val = beginVal + e.key + endVal;
                    if (!matchValue(val, options)) {
                        e.stopPropagation();
                        e.preventDefault();
                        return;
                    }
                });

                $this.on('blur', function () {
                    $(this).val(formatValue($(this).val(), options));
                });

                $this.on('change', function () {
                    $(this).val(formatValue($(this).val(), options));
                });
            });
        }
    });

}(jQuery));
