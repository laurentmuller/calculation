/**! compression tag for ftp-deployment */

/**
 * Plugin to force user to confirm an operation.
 */
(function ($) {
    'use strict';

    // ----------------------------------------------
    // Simulate public class definition
    // ----------------------------------------------
    const Simulate = class {

        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, Simulate.DEFAULTS, options);
            this._init();
        }

        destroy() {
            // remove handlers and data
            const $element = this.$element;
            if (this.inputProxy) {
                this.$simulate.off('input', this.inputProxy);
            }
            $element.removeData('simulate');
        }

        _init() {
            const that = this;
            that.$simulate = that.$element.find(that.options.simulateSelector);
            that.$confirm = that.$element.find(that.options.confirmSelector);
            if (that.$simulate.length && that.$confirm.length) {
                that.inputProxy = function () {
                    that._onInput();
                };
                that.$simulate.on('input', that.inputProxy);
            }
        }

        _onInput () {
            if (this.$simulate.isChecked()) {
                this.$confirm.toggleDisabled(true).removeValidation();
                if (this.options.uncheck) {
                    this.$confirm.setChecked(false);
                }
            } else {
                this.$confirm.toggleDisabled(false);
            }
        }
    };

    Simulate.DEFAULTS = {
        simulateSelector: '#form_simulate',
        confirmSelector: '#form_confirm',
        uncheck: true
    };

    // -----------------------------------
    // Simulate plugin definition
    // -----------------------------------
    const oldSimulate = $.fn.simulate;

    $.fn.simulate = function (options) {
        return this.each(function () {
            const $this = $(this);
            const data = $this.data('simulate');
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data('simulate', new Simulate(this, settings));
            }
        });
    };

    $.fn.simulate.Constructor = Simulate;

    // Simulate no conflict
    $.fn.simulate.noConflict = function () {
        $.fn.simulate = oldSimulate;
        return this;
    };
}(jQuery));
