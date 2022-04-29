/**! compression tag for ftp-deployment */

/**
 * jQuery file input extensions.
 */
(function ($) {
    'use strict';

    /**
     * -------------- jQuery Extensions --------------
     */
    $.fn.extend({

        /**
         * Finds the file input container within the current element.
         */
        findFileInput() {
            const $parent =  $(this).parents('.form-group');
            return $parent.findExists('.fileinput.input-group, .fileinput-preview.img-thumbnail');
        },


        /**
         * Initialize a file input.
         *
         * @param {function} [callback] - the optional callback function to use after change.
         */
        initFileInput: function (callback) {
            return this.each(function () {
                const $that = $(this);
                const isThumbnail = $that.parents('.form-group').findExists('.img-thumbnail');
                $that.on('change', function () {
                    $that.valid();
                    if (typeof callback === 'function') {
                        callback($that);
                    }
                    if (!isThumbnail) {
                        const isFiles = $that.getInputFiles().length !== 0;
                        $that.parent().toggleClass('rounded-right', !isFiles).css('border-right', isFiles  ? '' : '0');
                    }
                });

                // find group
                const $group = $that.findFileInput();
                if ($group) {
                    // update class
                    $that.on('focus', function () {
                        if ($that.hasClass('is-invalid')) {
                            $group.addClass('field-invalid');
                        } else {
                            $group.addClass('field-valid');
                        }
                    }).on('blur', function () {
                        $group.removeClass('field-valid field-invalid');
                    });

                    // focus when select file
                    $group.find('.fileinput-filename,.fileinput-exists').on('click', function () {
                        $that.focus();
                    });
                }
            });
        }
    });
}(jQuery));
