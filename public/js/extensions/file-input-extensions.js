/**
 * jQuery file input extensions.
 */
$(function () {
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
            return $parent.findExists('.file-input.input-group, .file-input-preview.img-thumbnail');
        },


        /**
         * Initialize a file input.
         * @param {jQuery} [$delete] the delete button
         */
        initFileInput: function ($delete) {
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
