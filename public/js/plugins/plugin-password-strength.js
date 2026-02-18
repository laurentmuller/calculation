/**
 * Ready function
 */
$(function () {
    'use strict';

    // ----------------------------------------------
    // PasswordStrength public class definition
    // ----------------------------------------------
    const PasswordStrength = class {
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
            this.options = $.extend(true, {}, PasswordStrength.DEFAULTS, options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            if (this.$progress) {
                this.$progress.remove();
                this.$progress = null;
            }
            if (this.$label) {
                this.$label.remove();
                this.$label = null;
            }
            this.$element.off('keyup', this.keyupProxy);
            this.$element.removeData(PasswordStrength.NAME);
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
            that.keyupProxy = () => that._onKeyup();
            that.$element.on('keyup', function () {
                $(this).updateTimer(that.keyupProxy, 250);
            });
            if (that.$element.val()) {
                that._onKeyup();
            }
        }

        /**
         * Handles the key up event.
         * @private
         */
        _onKeyup() {
            const that = this;
            const text = that.$element.val();
            if (!text.length) {
                that._handleResult(null);
                return;
            }
            const url = that.$element.data('url');
            if (!url) {
                that._handleResult(null);
                return;
            }
            const options = that.options;
            const strength = that.$element.data('strength') || 0;
            // request values
            const request = {
                password: text,
                strength: Math.max(strength, 0),
                user: that._getInputValue(options.userField),
                email: that._getInputValue(options.emailField)
            };
            $.ajaxSetup({global: false});
            $.post(url, request, function (data) {
                if (options.debug) {
                    window.console.log(data);
                }
                that._handleResult(data);
            }).always(function () {
                $.ajaxSetup({global: true});
            });
        }

        /**
         * Handle post call.
         * @param {Object} data
         * @private
         */
        _handleResult(data) {
            // get verdict
            const options = this.options;
            const verdict = this._getVerdict(data);

            // update UI
            const $progress = this._getProgress(options);
            if ($progress) {
                $progress.find('.progress').each(function (index, element) {
                    $(element).toggleClass('d-none', verdict.score < index);
                });
            }
            const $label = this._getLabel(options);
            if ($label) {
                $label.text(verdict.text);
            }
            if (options.hideOnEmpty) {
                this._toggleControl(verdict.result, $progress);
                this._toggleControl(verdict.result, $label);
            }
        }

        /**
         * Toggle the control visibility
         * @param {boolean} show
         * @param {jQuery|any|null} $control
         * @private
         */
        _toggleControl(show, $control) {
            if (!$control) {
                return;
            }
            if (show) {
                $control.show();
            } else {
                $control.hide();
            }
        }

        /**
         * Convert the result to a verdict.
         *
         * @param {Object} data - the result.
         * @return {{result: boolean, score: number, text: string}}
         * @private
         */
        _getVerdict(data) {
            // data?
            if (!data) {
                return {
                    result: false,
                    score: -1,
                    text: ''
                };
            }

            // get values
            const score = data.score;
            return {
                result: true,
                text: score.text,
                score: score.value
            };
        }

        /**
         * Gets the UI container.
         *
         * @param {Object} options - the plugin options.
         * @return {jQuery|any} the UI container.
         * @private
         */
        _getContainer(options) {
            // already set?
            if (this.$container) {
                return this.$container;
            }

            // find
            this.$container = $(options.container);
            if (!this.$container || this.$container.length === 0) {
                this.$container = this.$element.parents('.input-group');
            }

            return this.$container;
        }

        /**
         * Gets or create the progress bars container.
         *
         * @param {Object} options - the plugin options.
         * @return {jQuery|any|null} the progress bar container or null if none found.
         * @private
         */
        _getProgress(options) {
            // already created?
            const that = this;
            if (that.$progress) {
                return that.$progress;
            }

            // get the container
            /** @type {jQuery<HTMLElement>|any|null} */
            const $container = that._getContainer(options);
            if (!$container) {
                return null;
            }

            // get the progress container
            const $progressContainer = $container.findExists(options.progressContainer) || $container.findExists('div.password-strength-container') || options.progressContainer;
            if (!$progressContainer || $progressContainer.length === 0) {
                return null;
            }

            // create progress
            that.$progress = that._createControl('div', 'progress-stacked gap-1 flex-grow-1 bg-transparent', {'height': options.height})
                .appendTo($progressContainer);

            // create progress bars
            for (let i = 0; i < 5; i++) {
                const className = `progress-bar ${options.progressClasses[i]}`;
                that._createControl('div', 'progress w-20 d-none')
                    .append(that._createControl('div', className))
                    .appendTo(that.$progress);
            }

            return that.$progress;
        }

        /**
         * Gets or create the verdict label
         *
         * @param {Object} options - the plugin options.
         * @return {jQuery|any|null} the verdict label or null if none.
         * @private
         */
        _getLabel(options) {
            // option?
            if (!options.labelContainer) {
                return null;
            }

            // already created?
            if (this.$label) {
                return this.$label;
            }

            // get the container
            const $container = this._getContainer(options);
            if (!$container) {
                return null;
            }

            // get the label container
            /** @type {jQuery<HTMLElement>} */
            let $labelContainer;
            if ($container.is(options.labelContainer)) {
                $labelContainer = $container;
            } else {
                $labelContainer = $container.findExists(options.labelContainer) || options.labelContainer;
            }
            if (!$labelContainer || $labelContainer.length === 0) {
                return null;
            }

            // create
            this.$label = this._createControl('span', 'small text-body-secondary')
                .appendTo($labelContainer);

            return this.$label;
        }

        /**
         * Creates an HTML element.
         *
         * @param {string} type - the tag type.
         * @param {string} [className] - the class name.
         * @param {Object} [css] - the CSS style.
         * @return {jQuery|any} the newly created element.
         * @private
         */
        _createControl(type, className, css = {}) {
            const $element = $(`<${type}/>`);
            if (className) {
                $element.addClass(className);
            }
            if (css) {
                $element.css(css);
            }
            return $element;
        }

        /**
         * Gets the content (value or text) of the given named element.
         *
         * @param {string} name
         * @return {string|null}
         * @private
         */
        _getInputValue(name) {
            if (name) {
                return $(name).val() || $(name).text().trim() || null;
            }
            return null;
        }
    };

    // -----------------------------
    // Default options
    // -----------------------------
    PasswordStrength.DEFAULTS = {
        // true to debug the result
        debug: false,

        // user field selector
        userField: null,

        // email field selector
        emailField: null,

        // container
        container: null,

        // the progress container
        progressContainer: null,

        // the label container
        labelContainer: null,

        // hide container on empty password
        hideOnEmpty: true,

        // progress height in pixels
        height: 4,

        // progress bar classes
        progressClasses: ['bg-danger', 'bg-danger', 'bg-warning', 'bg-success', 'bg-primary'],
    };

    /**
     * The plugin name.
     */
    PasswordStrength.NAME = 'bs.password-strength';

    // -----------------------------------
    // PasswordStrength plugin definition
    // -----------------------------------
    const oldPasswordStrength = $.fn.passwordStrength;
    $.fn.passwordStrength = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(PasswordStrength.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(PasswordStrength.NAME, new PasswordStrength(this, settings));
            }
        });
    };
    $.fn.passwordStrength.Constructor = PasswordStrength;

    // ------------------------------------
    // PasswordStrength no conflict
    // ------------------------------------
    $.fn.passwordStrength.noConflict = function () {
        $.fn.passwordstrength = oldPasswordStrength;
        return this;
    };

});
