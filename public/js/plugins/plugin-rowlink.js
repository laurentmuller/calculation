/**
 * Ready function
 */
$(function () {
    'use strict';

    // ------------------------------------
    // Rowlink public class definition
    // ------------------------------------
    const Rowlink = class {

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
            this.enabled = false;
            this.$element = $(element);
            this.options = $.extend({}, Rowlink.DEFAULTS, options);
            this.proxy = (e) => this._click(e);
            this.enable();
        }

        enable() {
            if (!this.enabled) {
                this.$element.on('click.bs.rowlink', 'td:not(.rowlink-skip)', this.proxy);
                this.enabled = true;
            }
        }

        disable() {
            if (this.enabled) {
                this.$element.off('click.bs.rowlink', 'td:not(.rowlink-skip)', this.proxy);
                this.enabled = false;
            }
        }

        destroy() {
            this.disable();
            this.$element.removeData(Rowlink.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------
        /**
         * Handle the click event.
         *
         * @param {MouseEvent} e - the event.
         * @param {boolean} [ctrlKey] - the control key value.
         * @private
         */
        _click(e, ctrlKey) {
            const target = $(e.currentTarget).closest('tr').find(this.options.target)[0];
            if (typeof target === 'undefined' || $(e.target)[0] === target) {
                return;
            }

            e.preventDefault();
            ctrlKey = ctrlKey || e.ctrlKey || e.type === 'mouseup' && e.button === 0;
            if (!ctrlKey && target.click) {
                target.click();
            } else if (document.createEvent) {
                const evt = new MouseEvent('click', {
                    view: window,
                    bubbles: true,
                    cancelable: true,
                    ctrlKey: ctrlKey
                });
                target.dispatchEvent(evt);
            }
        }
    };

    Rowlink.DEFAULTS = {
        target: 'a'
    };

    /**
     * The plugin name.
     */
    Rowlink.NAME = 'bs.rowlink';

    // ------------------------------------
    // Rowlink plugin definition
    // ------------------------------------
    const oldRowlink = $.fn.rowlink;
    $.fn.rowlink = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(Rowlink.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(Rowlink.NAME, new Rowlink(this, settings));
            }
        });
    };
    $.fn.rowlink.Constructor = Rowlink;

    // ------------------------------------
    // Rowlink no conflict
    // ------------------------------------
    $.fn.rowlink.noConflict = function () {
        $.fn.rowlink = oldRowlink;
        return this;
    };

    // ------------------------------------
    // Rowlink data-api
    // ------------------------------------
    /** @param {MouseEvent} e */
    $(document).on('click.bs.rowlink.data-api', '[data-link="row"]', function (e) {
        // check event
        if (e.type === 'mouseup' && e.button !== 0) {
            return;
        }
        if ($(e.target).closest('.rowlink-skip').length !== 0) {
            return;
        }

        // already initialized?
        const $this = $(this);
        if ($this.data(Rowlink.NAME)) {
            return;
        }

        // initialize
        $this.rowlink($this.data());
        $(e.target).trigger('click.bs.rowlink', [e.ctrlKey]);
    });
});
