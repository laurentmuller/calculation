/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * -------------- Typeahead Extensions --------------
 */
(function ($) {
    'use strict';

    /**
     * -------------- Functions extensions --------------
     */
    $.fn.extend({

        /**
         * Initialize a type ahead search.
         *
         * @param {Object} options - the options to override.
         * @return {Typeahead} The type ahead instance.
         */
        initTypeahead: function (options) {
            const $element = $(this);
            const defaults = {
                valueField: '',
                ajax: {
                    url: options.url
                },
                matcher: function () {
                    return true;
                },
                filter: function (data) {
                    return data;
                },
                onSelect: function () {
                    $element.select();
                },
                onError: function () {
                    const message = options.error;
                    const title = $('#edit-form').data('title');
                    Toaster.danger(message, title, $('#flashbags').data());
                }
            };
            const settings = $.extend(true, defaults, options);
            return $element.typeahead(settings);
        }
    });
}(jQuery));
