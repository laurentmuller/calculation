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
            this.options = $.extend(true, {}, Simulate.DEFAULTS, options);
            this._init();
        }

        destroy() {
            const $element = this.$element;
            if (this.inputProxy) {
                this.$simulate.off('input', this.inputProxy);
            }
            $element.removeData(Simulate.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        _init() {
            this.$simulate = this.$element.find(this.options.simulateSelector);
            this.$confirm = this.$element.find(this.options.confirmSelector);
            if (this.$simulate.length && this.$confirm.length) {
                this.inputProxy = () => this._onInput();
                this.$simulate.on('input', this.inputProxy);
            }
        }

        _onInput() {
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

    /**
     * The plugin name.
     */
    Simulate.NAME = 'bs.simulate';

    // -----------------------------------
    // Simulate plugin definition
    // -----------------------------------
    const oldSimulate = $.fn.simulate;

    $.fn.simulate = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(Simulate.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(Simulate.NAME, new Simulate(this, settings));
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
