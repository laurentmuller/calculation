/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * -------------- JQuery Extensions --------------
     */
    $.fn.extend({

        /**
         * Initialize the show/hide password.
         */
        togglePassword: function () {
            return this.each(function () {
                const $element = $(this);
                const $button = $element.parents('.input-group').findExists('.btn-password');
                if ($button) {
                    $button.on('click', function () {
                        const type = $element.is(':text') ? 'password' : 'text';
                        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
                        $element.prop('type', type).select();
                    });
                }
            });
        },

        /**
         * Initialize the password strength.
         * 
         * @param {Object}
         *            options - The optional options.
         */
        initPasswordStrength: function (options) {
            return this.each(function () {
                // get parent
                const $element = $(this);
                const $parent = $element.parents('.form-group');
                const id = $element.attr('id') + "_passwordstrength";

                // find or create UI container
                let $container = $parent.findExists('#' + id);
                if (!$container) {
                    $container = $('<div/>', {
                        'id': id,
                        'class': 'd-print-none password-strength'
                    }).appendTo($parent);
                }

                // get groups or element
                const $left = $parent.findExists('.input-group-prepend .input-group-text:first') || $element;
                const $right = $parent.findExists('.input-group-append .input-group-text:last') || $element;

                // radius
                const leftRadius = $left.css('border-bottom-left-radius');
                const rightRadius = $right.css('border-bottom-right-radius');

                // default options
                const defaults = {
                    container: $container,
                    // labelContainer: $container,
                    progressContainer: $container,
                    verdictKeys: $.validator.messages.passwordLevels,
                    onUpdateUI: function (verdict) {
                        // update style
                        let left = 0;
                        let right = 0;
                        switch (verdict.percent) {
                        case 0:
                            // both
                            left = leftRadius;
                            right = rightRadius;
                            break;
                        case 100:
                            // none
                            break;
                        default: // 1-99
                            // right
                            right = rightRadius;
                            break;
                        }
                        $left.css('border-bottom-left-radius', left);
                        $right.css('border-bottom-right-radius', right);
                    }
                };

                // merge and initialize
                const settings = $.extend(true, defaults, options);
                $element.passwordstrength(settings);
            });
        }
    });

    // update password types
    $("input:password").togglePassword();

}(jQuery));