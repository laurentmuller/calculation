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
                    const show = function () {
                        $element.prop('type', 'text');
                        $button.children().removeClass('fa-eye').addClass('fa-eye-slash');
                        $button.data('display', true);
                    };
                    const hide = function () {
                        $element.prop('type', 'password');
                        $button.children().removeClass('fa-eye-slash').addClass('fa-eye');
                    };
                    const toggle = function () {
                        if ($button.data('display') || false) {
                            $button.data('display', false);
                        } else {
                            show();
                            $button.data('display', false);
                            $button.updateTimer(hide, 250);
                        }
                    };
                    $button.on('mousedown', show).on('mouseup', hide).on('click', toggle);
                }
            });
        },

        /**
         * Initialize the password strength.
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
                    progressContainer: $container,
                    verdictKeys: $.validator.messages.password_levels,
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