/**
 * jQuery file input extensions.
 */
(function ($) {
    'use strict';

    $(function () {
        /**
         * -------------- jQuery Extensions --------------
         */
        $.fn.extend({

            /**
             * Finds the image input container within the current element.
             */
            findImageInput() {
                const $parent = $(this).parents('.image-input');
                return $parent.findExists('.image-input-preview');
            },

            /**
             * Initialize an image input.
             * @param {jQuery} [$delete] the delete checkbox
             */
            initImageInput: function ($delete) {
                return this.each(function () {
                    const $that = $(this);
                    $that.on('input', function () {
                        $that.valid();
                        if ($delete && $delete.length) {
                            const source = $that.data('src') || '';
                            const target = $that.parents('.form-group').find('img').attr('src') || '';
                            $delete.setChecked(source !== target);
                        }
                    });
                });
            }
        });
    });
}(jQuery));
