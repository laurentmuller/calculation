/**! compression tag for ftp-deployment */

/**
 * -------------- JQuery functions extensions --------------
 */
(function ($) {
    'use strict';

    $.fn.extend({
        /**
         * Returns this trimmed text or value content.
         *
         * @returns {string} the trimmed content.
         */
        trimText: function () {
            const $this = $(this);
            if ($this.is('input')) {
                return $this.val().trim();
            }
            return $this.text().trim();
        },

        /**
         * Returns if this cell content is equal to 0.
         *
         * @returns {boolean} true if empty.
         */
        isEmptyValue: function () {
            const text = $(this).trimText();
            return $.parseFloat(text) === 0;
        },

        /**
         * Updates the cells errors.
         *
         * @returns {boolean} true if any error is found.
         */
        updateErrors: function () {
            const existing = [];
            let emptyFound = false;
            let duplicateFound = false;

            const emptyClass = 'empty-cell';
            const duplicateClass = 'duplicate-cell';

            $(this).find('.item').each(function () {
                const $row = $(this);
                // duplicate
                const $cell = $row.find('td:eq(0)');
                const key = $cell.trimText().toLowerCase();
                $cell.removeClass(duplicateClass);
                if (key) {
                    if (key in existing) {
                        $cell.addClass(duplicateClass);
                        existing[key].addClass(duplicateClass);
                        duplicateFound = true;
                    } else {
                        existing[key] = $cell;
                    }
                }

                // empty
                $row.find('td:eq(2), td:eq(3)').each(function () {
                    const $cell = $(this);
                    if ($cell.isEmptyValue()) {
                        emptyFound = true;
                        $cell.addClass(emptyClass);
                    } else {
                        $cell.removeClass(emptyClass);
                    }
                });
            });

            // show or hide
            if (emptyFound || duplicateFound) {
                $("#error-empty").toggleClass('d-none', !emptyFound);
                $("#error-duplicate").toggleClass('d-none', !duplicateFound);
                $("#error-all").removeClass('d-none').fadeIn();
            } else {
                $("#error-all").fadeOut();
            }

            return emptyFound || duplicateFound;
        }
    });

    // tooltip
    $('body').tooltip({
        selector: '.has-tooltip',
        customClass: 'tooltip-danger overall-cell'
    });

    // errors
    $("#data-table-edit").updateErrors();

}(jQuery));
