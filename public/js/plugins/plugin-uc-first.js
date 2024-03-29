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
            this.$element.off('focusout', this.blurProxy)
                .removeData(UCFirst.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        _init() {
            this.blurProxy = () => this._blur();
            this.$element.on('focusout', this.blurProxy);
        }

        _blur() {
            let value = String(this.$element.val());
            if (value.length) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
                this.$element.val(value);
            }
        }
    };

    // -----------------------------
    // Default options
    // -----------------------------
    UCFirst.DEFAULTS = {};

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

}(jQuery));
