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
                $that.on('input', function () {
                    $that.valid();
                    if (typeof callback === 'function') {
                        callback($that);
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
                    $group.find('.file-input-filename,.file-input-exists').on('click', function () {
                        $that.trigger('focus');
                    });
                }
            });
        }
    });
}(jQuery));
