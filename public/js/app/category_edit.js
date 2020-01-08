/**! compression tag for ftp-deployment */

/**
 * Gets the maximum value.
 * 
 * @returns the maximum value.
 */
function getMaxValue() {
    'use strict';

    let maximum = 0;
    $("input[name*='[maximum]']").each(function () {
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
    $("input[name*='[margin]']").each(function () {
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

    // set default values
    $("input[name*='minimum']").last().floatVal(minimum).selectFocus();
    $("input[name*='maximum']").last().floatVal(maximum);
    $("input[name*='margin']").last().intVal(margin);
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
    let row = $this.closest("tr");
    row.fadeOut(200, function () {
        row.remove();
        if ($("#data-table-edit > tbody > tr").length === 0) {
            $("#data-table-edit").addClass('d-none');
        }
        // $(".btn-add").focus();
    });
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // validation
    $("form").initValidator();

    // handle add button
    const $collectionHolder = $("#data-table-edit");
    $(".btn-add").on("click", function (e) {
        e.preventDefault();
        addMarginForm($collectionHolder);
    });

    // handle delete button
    $("#data-table-edit").on("click", ".btn-delete", function (e) {
        e.preventDefault();
        removeMarginForm($(this));
    });
});
