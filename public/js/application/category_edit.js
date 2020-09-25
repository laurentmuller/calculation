/**! compression tag for ftp-deployment */

/**
 * Gets the maximum value.
 * 
 * @returns the maximum value.
 */
function getMaxValue() {
    'use strict';

    let maximum = 0;
    $("input[name$='[maximum]']").each(function () {
        maximum = Math.max(maximum, $(this).floatVal());
    });
    return maximum;
}

/**
 * Gets the minimum margin value.
 * 
 * @returns the minimum margin value.
 */
function getMinMargin() {
    'use strict';

    let min = Number.MAX_VALUE;
    $("input[name$='[margin]']").each(function () {
        min = Math.min(min, $(this).intVal());
    });
    return min === Number.MAX_VALUE ? 0 : min;
}

/**
 * Adds a new margin row.
 * 
 * @param $collectionHolder
 *            the collection holder.
 */
function addMarginForm($collectionHolder) {
    'use strict';

    // get prototype and update index
    const prototype = $collectionHolder.data("prototype");
    const index = $collectionHolder.data("index");
    $collectionHolder.data("index", index + 1);

    // replace name
    const newForm = prototype.replace(/__name__/g, index);

    // get range
    const margin = getMinMargin();
    const minimum = getMaxValue();
    const maximum = Math.max(minimum * 2, 100);

    // add
    $("#data-table-edit > tbody").append(newForm);
    $("#data-table-edit").removeClass('d-none');

    // set values and add validation
    $("input[name$='[minimum]']:last").floatVal(minimum).inputNumberFormat().selectFocus();
    $("input[name$='[maximum]']:last").floatVal(maximum).inputNumberFormat();
    $("input[name$='[margin]']:last").intVal(margin).inputNumberFormat({
        'decimal': 0
    });
}

/**
 * Remove the parent row.
 * 
 * @param $this
 *            the caller.
 */
function removeMarginForm($this) {
    'use strict';

    // remove row
    const row = $this.closest("tr");
    row.fadeOut(200, function () {
        row.remove();
        if ($("#data-table-edit > tbody > tr").length === 0) {
            $("#data-table-edit").addClass('d-none');
        }
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // handle delete button
    const $table = $("#data-table-edit");
    $table.on("click", ".btn-delete", function (e) {
        e.preventDefault();
        removeMarginForm($(this));
    });

    // handle add button
    $(".btn-add").on("click", function (e) {
        e.preventDefault();
        addMarginForm($table);
    });

    // add numbers validation
    $("input[name$='[minimum]']").inputNumberFormat();
    $("input[name$='[maximum]']").inputNumberFormat();
    $("input[name$='[margin]']").inputNumberFormat({
        'decimal': 0
    });

    // validation
    $("form").initValidator();
}(jQuery));
