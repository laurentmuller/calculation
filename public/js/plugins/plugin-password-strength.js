/**! compression tag for ftp-deployment */

/* globals zxcvbn */

/**
 * Ready function
 * @typedef {Object} ZXCVBNResult - the ZXCVBN result
 * @property {number} score
 */
(function ($) {
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
         * @param {HTMLElement} element - the element to handle.
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
            this.keyupProxy = () => this._onKeyup();
            this.$element.on('keyup', this.keyupProxy);
            if (this.$element.val()) {
                this._onKeyup();
            }
        }

        /**
         * Handles the password key up event.
         * @private
         */
        _onKeyup() {
            let result;
            const that = this;
            const text = that.$element.val();
            const options = that.options;
            if (text.length) {
                // add inputs
                const inputs = [];
                const user = that._getInputValue(options.userField);
                if (user) {
                    inputs.push(user);
                }
                const email = that._getInputValue(options.emailField);
                if (email) {
                    inputs.push(email);
                }

                // const url = that.$element.data('url');
                // if (url) {
                //     let strength = that.$element.data('strength') || 0;
                //     // request values
                //     const request = {
                //         password: text,
                //         strength: Math.max(strength, 0)
                //     };
                //     if (user) {
                //         request.user = user;
                //     }
                //     if (email) {
                //         request.email = email;
                //     }
                //     /**
                //      * @param {{result: boolean, score: number, scoreText: string, percent: number}} data
                //      */
                //     $.ajaxSetup({global: false});
                //     $.post(url, request, function (data) {
                //         window.console.log(data);
                //         // data.text = data.scoreText;
                //         // if (options.debug && window.console) {
                //         //
                //         // }
                //     }).always(function () {
                //         $.ajaxSetup({global: true});
                //     });
                // }

                // compute
                result = zxcvbn(text, inputs);

                // output
                if (options.debug && window.console) {
                    window.console.log(result);
                }
            }

            // get verdict
            const verdict = that._getVerdict(result, options);

            // update UI
            let updateUI = false;
            const $progress = that._getProgress(options);
            if ($progress) {
                updateUI = true;
                $progress.find('.progress').each(function (index, element) {
                    $(element).toggleClass('d-none', index > verdict.score);
                });
            }
            const $label = that._getLabel(options);
            if ($label) {
                updateUI = true;
                $label.text(verdict.text);
            }
            if (options.hideOnEmpty && $progress) {
                updateUI = true;
                if (result) {
                    $progress.show();
                } else {
                    $progress.hide();
                }
            }

            // copy
            that.verdict = verdict;

            // raise events
            if (updateUI && typeof options.onUpdateUI === 'function') {
                options.onUpdateUI(verdict);
            }
            if (typeof options.onScore === 'function') {
                options.onScore(verdict);
            }
        }

        /**
         * Convert the result to a verdict.
         *
         * @param {ZXCVBNResult} result - the score.
         * @param {Object} options - the plugin options.
         * @return {{score: number, text: string, percent: number}} the verdict
         * @private
         */
        _getVerdict(result, options) {
            // value?
            if (!result) {
                return {
                    score: 0,
                    percent: 0,
                    text: ''
                };
            }

            // get text
            const score = result.score;
            const key = options.verdictKeys[score];
            const text = typeof options.translate === 'function' ? options.translate(key) : key;
            return {
                score: score,
                percent: (score + 1) * 20,
                text: text
            };
        }

        /**
         * Gets the UI container.
         *
         * @param {Object} options - the plugin options.
         * @return {jQuery} the UI container.
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
                this.$container = this.$element.parents('.mb-3:first');
            }

            return this.$container;
        }

        /**
         * Gets or create the progress bars container.
         *
         * @param {Object} options - the plugin options.
         * @return {jQuery|null} the progress bar container or null if none.
         * @private
         */
        _getProgress(options) {
            // already created?
            const that = this;
            if (that.$progress) {
                return that.$progress;
            }

            // get the container
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
            that.$progress = that._createControl('div', 'progress-stacked gap-1 bg-transparent', {'height': options.height})
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
         * @return {jQuery|null} the verdict label or null if none.
         * @private
         */
        _getLabel(options) {
            // already created?
            const that = this;
            if (that.$label) {
                return that.$label;
            }

            // get the container
            const $container = that._getContainer(options);
            if (!$container) {
                return null;
            }

            // get the label container
            const $labelContainer = $container.findExists(options.labelContainer) || options.labelContainer;
            if (!($labelContainer && $labelContainer.length === 1)) {
                return null;
            }

            // create
            that.$label = that._createControl('span').appendTo($labelContainer);

            return that.$label;
        }

        /**
         * Creates an HTML element.
         *
         * @param {string} type - the tag type.
         * @param {string} [className] - the class name.
         * @param {Object} [css] - the css style.
         * @return {jQuery} the newly created element.
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
         * Gets the content of the given named input.
         *
         * @param {string} name
         * @return {string|boolean}
         * @private
         */
        _getInputValue(name) {
            if (name) {
                return $(name).val() || false;
            }
            return false;
        }
    };

    // -----------------------------
    // Default options
    // -----------------------------
    PasswordStrength.DEFAULTS = {
        // true to debug result
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

        // score change callback function
        onScore: null,

        // update UI callback function
        onUpdateUI: null,

        // translation function
        translate: null,

        // hide container on empty password
        hideOnEmpty: true,

        // progress height in pixels
        height: 4,

        // verdict keys
        verdictKeys: ['veryWeak', 'weak', 'normal', 'strong', 'veryStrong'],

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

}(jQuery));
