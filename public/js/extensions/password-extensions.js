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
                const $button = $element.siblings('.btn-password');
                if (!$button.length) {
                    return true;
                }
                const $icon = $button.find('i');
                $button.on('click', function (e) {
                    e.stopPropagation();
                    if ($element.prop('type') === 'password') {
                        $element.prop('type', 'text');
                        $icon.attr('class', $element.data('hide-icon'));
                        $button.attr('title', $element.data('hide-title'));
                    } else {
                        $element.prop('type', 'password');
                        $icon.attr('class', $element.data('show-icon'));
                        $button.attr('title', $element.data('show-title'));
                    }
                    $element.selectFocus();
                });
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
