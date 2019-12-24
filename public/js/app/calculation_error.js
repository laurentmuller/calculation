/**! compression tag for ftp-deployment */

/**
 * Returns this trimmed text content.
 * 
 * @returns the trimmed text content.
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
 * @returns true if empty.
 */
$.fn.isEmptyValue = function () {
    'use strict';
    const text = $(this).trimmedText();
    const value = Number.parseFloat(text);
    return Number.isNaN(value) || value === 0;
};

/**
 * Updates the rows and cells errors.
 * 
 * @returns true if any error found.
 */
function updateErrors() {
    'use strict';

    let existing = [];
    let selection = [];
    let emptyFound = false;
    let duplicateFound = false;

    const emptyClass = "empty-cell";
    const duplicateClass = "duplicate-cell";

    $("#data-table-edit tbody tr").each(function () {
        const $row = $(this);

        // duplicate
        const $cell = $row.find("td:eq(0)");
        const key = $cell.trimmedText();
        $cell.removeClass(duplicateClass);
        if (key) {
            if (key in existing) {
                $cell.addClass(duplicateClass);
                existing[key].addClass(duplicateClass);
                selection.push($cell);
                selection.push(existing[key]);
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
    $("#error-empty").updateClass("d-none", !emptyFound);
    $("#error-duplicate").updateClass("d-none", !duplicateFound);
    $("#error-all").updateClass("d-none", !(emptyFound || duplicateFound));

    return emptyFound || duplicateFound;
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // tooltip
    if ($('.btn-adjust').length) {
        $('.btn-adjust').tooltip();
    }
    $('body').customTooltip({
        selector: '.has-tooltip',
        className: 'tooltip-danger overall-cell'
    });

    // errors
    if ($("#data-table-edit").length) {
        updateErrors();
    }
});