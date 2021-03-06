/**! compression tag for ftp-deployment */

/**
 * Returns this trimmed text content.
 * 
 * @returns {string} the trimmed text content.
 */
$.fn.trimmedText = function () {
    'use strict';
    const $this = $(this);
    if ($this.is("input")) {
        return $this.val().trim();
    }
    return $this.text().trim();
};

/**
 * Returns if this cell content is equal to 0.
 * 
 * @returns {boolean} true if empty.
 */
$.fn.isEmptyValue = function () {
    'use strict';
    const text = $(this).trimmedText();
    const value = parseFloat(text);
    return isNaN(value) || value === 0;
};

/**
 * Updates the rows and cells errors.
 * 
 * @returns {boolean} true if any error found.
 */
function updateErrors() {
    'use strict';

    const existing = [];
    let emptyFound = false;
    let duplicateFound = false;

    const emptyClass = "empty-cell";
    const duplicateClass = "duplicate-cell";

    $("#data-table-edit tbody tr").each(function () {
        const $row = $(this);

        // duplicate
        const $cell = $row.find("td:eq(0)");
        const key = $cell.trimmedText().toLowerCase();
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
        $row.find("td:eq(2), td:eq(3)").each(function () {
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
        $("#error-empty").toggleClass("d-none", !emptyFound);
        $("#error-duplicate").toggleClass("d-none", !duplicateFound);
        $("#error-all").removeClass("d-none").fadeIn();
    } else {
        $("#error-all").fadeOut();
    }

    return emptyFound || duplicateFound;
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // tooltip
    if ($('.btn-adjust').length) {
        $('.btn-adjust').tooltip();
    }
    $('body').tooltip({
        selector: '.has-tooltip',
        customClass: 'tooltip-danger overall-cell'
    });
    

    // errors
    if ($("#data-table-edit").length) {
        updateErrors();
    }
}(jQuery));
