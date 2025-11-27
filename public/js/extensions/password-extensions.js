/**
 * Ready function
 */
$(function () {
    'use strict';

    /**
     * -------------- jQuery Extensions --------------
     */
    $.fn.extend({
        /**
         * Initialize the show/hide password.
         */
        togglePassword: function () {
            return this.each(function () {
                /** @var {jQuery|any} */
                const $element = $(this);
                const $button = $element.parents('.input-group').find('.btn-password');
                if ($button.length) {
                    const $icon = $button.find('i');
                    $button.on('mousedown', function (e) {
                        if (e.button === 0 && $element.prop('type') === 'password' && String($element.val()).length > 0) {
                            $element.prop('type', 'text');
                            $icon.toggleClass('fa-eye-slash fa-eye');
                        }
                    }).on('mouseup mouseout', function () {
                        if ($element.prop('type') === 'text') {
                            $element.prop('type', 'password').trigger('focus');
                            $icon.toggleClass('fa-eye-slash fa-eye');
                        }
                    });
                    $element.on('input', function () {
                        $button.toggleClass('disabled', String($element.val()).length === 0);
                    });
                }
            });
        },

        /**
         * Initialize the password strength.
         *
         * @param {Object} [options] - The options.
         */
        initPasswordStrength: function (options) {
            return this.each(function () {
                /** @type {jQuery|any} */
                const $element = $(this);
                const $parent = $element.parents('.input-group');
                const id = $element.attr('id') + '_passwordStrength';

                // find or create a UI container
                let $container = $parent.find('#' + id);
                if ($container.length === 0) {
                    $container = $('<div/>', {
                        'id': id,
                        'class': 'd-print-none d-flex gap-1 align-items-start w-100 password-strength'
                    }).appendTo($parent);
                }

                // default options
                const defaults = {
                    container: $container,
                    progressContainer: $container,
                    verdictKeys: $.validator.messages.passwordLevels,
                };

                // merge and initialize
                const settings = $.extend(true, defaults, options);
                $element.passwordStrength(settings);
            });
        }
    });

    // update password types
    $('input:password').togglePassword();
});
