/**! compression tag for ftp-deployment */

/* global grecaptcha */
/**
 * Plugin to handle recaptcha.
 */
$(function () {
    'use strict';

    // ----------------------------------------------
    // Recaptcha public class definition
    // ----------------------------------------------
    const Recaptcha = class {

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
            this.options = $.extend(true, {}, Recaptcha.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        destroy() {
            if (this.targetProxy && this.$target.length) {
                const options = this.options;
                this.$target.off(options.event, this.targetProxy);
            }
            this.$element.removeData(Recaptcha.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize the plugin.
         * @private
         */
        _init() {
            const options = this.options;
            this.$target = $(options.selector);
            if (!this.$target.length) {
                return;
            }
            this.targetProxy = (e) => this._onTargetEvent(e);
            this.$target.on(options.event, this.targetProxy);
        }

        /**
         * Handle the target event.
         * @param {Event} e the event.
         * @private
         */
        _onTargetEvent(e) {
            e.preventDefault();
            const options = this.options;
            const that = this;
            const $target = this.$target;
            const $element = this.$element;
            const cursor = $target.css('cursor');
            grecaptcha.ready(() => {
                $target.css('cursor', 'wait');
                grecaptcha.execute(options.key, {action: options.action}).then((token) => {
                    $target.css('cursor', cursor);
                    $element.val(token);
                    that._dispatchSubmit();
                });
            });
        }

        /**
         * Trigger submit event for the parent's form.
         * @private
         */
        _dispatchSubmit() {
            const $form = this.$element.closest('form');
            if ($form.length) {
                $form.trigger('submit');
            }
        }

    };

    /**
     * The default options.
     */
    Recaptcha.DEFAULTS = {
        key: '',
        action: 'login',
        event: 'click',
        selector: '[data-toggle="recaptcha"]',
    };

    /**
     * The plugin name.
     */
    Recaptcha.NAME = 'bs.recaptcha';

    // -----------------------------------
    // Recaptcha plugin definition
    // -----------------------------------
    const oldRecaptcha = $.fn.recaptcha;
    $.fn.recaptcha = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(Recaptcha.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(Recaptcha.NAME, new Recaptcha(this, settings));
            }
        });
    };
    $.fn.recaptcha.Constructor = Recaptcha;

    // ------------------------------------
    // Recaptcha no conflict
    // ------------------------------------
    $.fn.recaptcha.noConflict = function () {
        $.fn.recaptcha = oldRecaptcha;
        return this;
    };

    // --------------------------------
    // Recaptcha data-api
    // --------------------------------
    $(document).on('click',  Recaptcha.DEFAULTS.selector, function (e) {
        const $this = $(this);
        const $source = $this.parents('form').find('.recaptcha');
        if ($source.length && !$source.data(Recaptcha.NAME)) {
            e.preventDefault();
            $source.recaptcha();
            $this.trigger('click');
        }
    });
});
