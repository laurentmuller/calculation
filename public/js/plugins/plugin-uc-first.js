/**! compression tag for ftp-deployment */

/**
 * Plugin to set the first character to uppercase.
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // UCFirst public class definition
    // ------------------------------------

    /**
     * @property {JQuery<HTMLInputElement>} $element
     */
    const UCFirst = class {

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
            this.options = $.extend(true, UCFirst.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            this.$element.off('focusout.bs.ucFirst', this.proxy)
                .removeData(UCFirst.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        _init() {
            this.proxy = () => this._focusout();
            this.$element.on('focusout.bs.ucFirst', this.proxy);
        }

        _focusout() {
            /** @type {string} */
            const oldValue = this.$element.val();
            if (!oldValue.length) {
                return;
            }
            let newValue = oldValue.charAt(0).toUpperCase() + oldValue.slice(1);
            if (this.options.endPoint && newValue.slice(-1) !== '.') {
                newValue = newValue + '.';
            }
            if (oldValue === newValue) {
                return;
            }
            this.$element.val(newValue)
                .trigger('input');
        }
    };

    // -----------------------------
    // Default options
    // -----------------------------
    UCFirst.DEFAULTS = {
        endPoint: false
    };

    // -------------------------------
    // The plugin name.
    // -------------------------------
    UCFirst.NAME = 'bs.uc-first';

    // -------------------------------
    // UCFirst plugin definition
    // -------------------------------
    const oldUcFirst = $.fn.ucFirst;
    $.fn.ucFirst = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(UCFirst.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(UCFirst.NAME, new UCFirst(this, settings));
            }
        });
    };
    $.fn.ucFirst.Constructor = UCFirst;

    // ------------------------------------
    // UCFirst no conflict
    // ------------------------------------
    $.fn.ucFirst.noConflict = function () {
        $.fn.ucFirst = oldUcFirst;
        return this;
    };

    // ------------------------------------
    // UCFirst data-api
    // ------------------------------------
    $(document).on('focusout.bs.ucFirst.data-api', '.uc-first', function () {
        const $this = $(this);
        if (!$this.data(UCFirst.NAME)) {
            $this.ucFirst($this.data());
            $this.trigger('focusout');
        }
    });

}(jQuery));
