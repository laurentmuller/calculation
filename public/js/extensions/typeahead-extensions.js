/* globals Toaster */

(function ($) {
    'use strict';

    /**
     * -------------- Typeahead Extensions --------------
     */
    $(function () {

        /**
         * @typedef {Object} TypeaheadOptions
         * @property {string} url - The search URL.
         * @property {string} error - The error message.
         * @property {Object.<string, any>} [extra] extras - Any options to override.
         */

        /**
         * -------------- Functions extensions --------------
         */
        $.fn.extend({
            /**
             * Initialize a type ahead search.
             *
             * @param {TypeaheadOptions} options - the options to override.
             * @return {Typeahead} The type ahead instance.
             */
            initTypeahead: function (options) {
                const $element = $(this);
                const defaults = {
                    valueField: '',
                    ajax: {
                        url: options.url
                    },
                    onSelect: function () {
                        $element.trigger('select');
                    },
                    onError: function () {
                        const title = $('#edit-form').data('title') || $('.card-title').text();
                        const message = options.error || 'Une erreur inconnue s\'est produite !';
                        Toaster.danger(message, title);
                    }
                };
                const settings = $.extend(true, defaults, options);
                return $element.typeahead(settings);
            }
        });
    });
}(jQuery));
