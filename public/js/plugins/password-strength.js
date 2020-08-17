/**! compression tag for ftp-deployment */

/*globals zxcvbn*/

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ----------------------------------------------
    // PasswordStrength public class definition
    // ----------------------------------------------
    var PasswordStrength = function (element, options) {
        const that = this;
        that.$element = $(element);
        that.options = $.extend(true, {}, PasswordStrength.DEFAULTS, options);

        // add handler
        that.$element.on('keyup', function () {
            that.onKeyup();
        });
        if (that.$element.val()) {
            that.onKeyup();
        }
    };

    // -----------------------------
    // Prototype functions
    // -----------------------------
    PasswordStrength.prototype = {

        /**
         * Constrcutor.
         */
        constructor: PasswordStrength,

        /**
         * Handles the password key up event.
         */
        onKeyup: function () {
            let result;
            const that = this;
            const text = that.$element.val();
            const options = that.options;
            if (text.length) {
                // add inputs
                const inputs = [];
                const user = that.getInputValue(options.userField);
                if (user) {
                    inputs.push(user);
                }
                const email = that.getInputValue(options.emailField);
                if (email) {
                    inputs.push(email);
                }

                // compute
                result = zxcvbn(text, inputs);

                // output
                if (options.debug && console) {
                    console.log(result);
                }
            }

            // get verdict
            const verdict = that.getVerdict(result, options);

            // update UI
            let updateUI = false;
            const $progress = that.getProgress(options);
            if ($progress) {
                updateUI = true;
                const $bars = $progress.find('.progress-bar');
                $bars.each(function (index, element) {
                    if (index <= verdict.score) {
                        $(element).removeClass('d-none');
                    } else {
                        $(element).addClass('d-none');
                    }
                });
            }
            const $label = that.getLabel(options);
            if ($label) {
                updateUI = true;
                $label.text(verdict.text);
            }
            if (options.hideOnEmpty) {
                const $container = that.getContainer(options);
                if ($container) {
                    updateUI = true;
                    if (result) {
                        $container.show();
                    } else {
                        $container.hide();
                    }
                }
            }

            // copy
            that.verdict = verdict;

            // raise events
            if (updateUI && $.isFunction(options.onUpdateUI)) {
                options.onUpdateUI(verdict);
            }
            if ($.isFunction(options.onScore)) {
                options.onScore(verdict);
            }
        },

        /**
         * Gets the verdict.
         */
        getVerdict: function (result, options) {
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
            const text = $.isFunction(options.translate) ? options.translate(key) : key;

            return {
                score: score,
                percent: (score + 1) * 20,
                text: text
            };
        },

        /**
         * Gets the UI container.
         */
        getContainer: function (options) {
            // already set?
            const that = this;
            if (that.$container) {
                return that.$container;
            }

            // find
            that.$container = $(options.container);
            if (!that.$container || that.$container.length === 0) {
                that.$container = that.$element.parent();
            }

            return that.$container;
        },

        /**
         * Gets or create the progress bars container.
         */
        getProgress: function (options) {
            // already created?
            const that = this;
            if (that.$progress) {
                return that.$progress;
            }

            // get container
            const $container = that.getContainer(options);
            if (!$container) {
                return null;
            }

            // get progress container
            const $progressContainer = $container.findExists(options.progressContainer) || options.progressContainer;
            if (!($progressContainer && $progressContainer.length === 1)) {
                return null;
            }

            // progress
            that.$progress = that.createControl('div', 'progress bg-transparent').css({ // rounded-bottom
                'height': options.height + 'px',
                'border-radius': 0
            }).appendTo($progressContainer);

            // progress bars
            for (let i = 0; i < 5; i++) {
                const className = 'progress-bar d-none ' + options.progressClasses[i];
                that.createControl('div', className).css({
                    'width': '20%',
                    'margin-right': i < 4 ? '2px' : 0
                }).appendTo(that.$progress);
            }

            return that.$progress;
        },

        /**
         * Gets or create the verdict label
         */
        getLabel: function (options) {
            // already created?
            const that = this;
            if (that.$label) {
                return that.$label;
            }

            // get container
            const $container = that.getContainer(options);
            if (!$container) {
                return null;
            }

            // get vedict container
            const $labelContainer = $container.findExists(options.labelContainer) || options.labelContainer;
            if (!($labelContainer && $labelContainer.length === 1)) {
                return null;
            }

            // create
            that.$label = that.createControl('span').appendTo($labelContainer);

            return that.$label;

        },

        /**
         * Creates a HTML element.
         */
        createControl: function (type, className) {
            const $element = $('<' + type + '/>');
            if (className) {
                $element.addClass(className);
            }
            return $element;
        },

        /**
         * Gets the content of the given named input.
         */
        getInputValue: function (name) {
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
        // true to debug zxcvbn result
        debug: false,
        // user field selector
        userField: null,
        // email field selector
        emailField: null,

        // container
        container: null,
        // progress container
        progressContainer: null,
        // label container
        labelContainer: null,

        // score change callback function
        onScore: null,
        // update UI callback function
        onUpdateUI: null,
        // translation function
        translate: null,

        // hide container on empty password
        hideOnEmpty: true,
        // progress height
        height: 4,

        // verdict keys
        verdictKeys: ["veryWeak", "weak", "normal", "strong", "veryStrong"],

        // progress bar classes
        progressClasses: ["bg-danger", "bg-danger", "bg-warning", "bg-success", "bg-primary"],
    };

    // -----------------------------------
    // PasswordStrength plugin definition
    // -----------------------------------
    const oldPasswordStrength = $.fn.passwordstrength;

    $.fn.passwordstrength = function (option) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data("passwordstrength");
            if (!data) {
                const options = typeof option === "object" && option;
                $this.data("passwordstrength", data = new PasswordStrength(this, options));
            }
        });
    };

    $.fn.passwordstrength.Constructor = PasswordStrength;

    // ------------------------------------
    // PasswordStrength no conflict
    // ------------------------------------
    $.fn.passwordstrength.noConflict = function () {
        $.fn.passwordstrength = oldPasswordStrength;
        return this;
    };

}(jQuery));
