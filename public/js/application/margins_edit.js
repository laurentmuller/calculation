/*  globals
    getSortedMargins,
    addMarginsMethods,
    validateOtherMargins,
    getMinimumInput,
    getMaximumInput,
    getMinimumSelector,
    getMaximumSelector
*/

/**
 * Gets the value input selector.
 *
 * @return {string}
 */
function getMarginSelector() {
    'use strict';
    return 'input[name$=\'[margin]\']';
}

/**
 * Update the user interface.
 */
function updateUI() {
    'use strict';
    // initialize the number input formats
    $(getMinimumSelector()).inputNumberFormat();
    $(getMaximumSelector()).inputNumberFormat();
    $(getMarginSelector()).inputNumberFormat({
        decimal: 0,
        decimalAuto: 0
    });
    // show/hide elements
    const $table = $('#data-table-edit');
    const rows = $table.find('tbody > tr').length;
    $table.toggleClass('d-none', rows === 0);
    $('.btn-sort').toggleDisabled(rows < 2);
    $('#empty_margins').toggleClass('d-none', rows > 0);
    // update edit message
    $('#edit-form :input:first').trigger('input');
}

/**
 * Gets the maximum value of the maximum column.
 *
 * @returns {number} the maximum value.
 */
function getMaxValue() {
    'use strict';
    let maximum = 0;
    const selector = getMaximumSelector();
    $(selector).each(function () {
        maximum = Math.max(maximum, $(this).floatVal());
    });
    return maximum;
}

/**
 * Gets the minimum margin value of the minimum column.
 *
 * @returns {number} the minimum margin value.
 */
function getMinMargin() {
    'use strict';
    let minimum = Number.MAX_VALUE;
    const selector = getMarginSelector();
    $(selector).each(function () {
        minimum = Math.min(minimum, $(this).intVal());
    });
    return minimum === Number.MAX_VALUE ? 100 : minimum;
}

/**
 * Adds a new margin row.
 */
function addMargin() {
    'use strict';
    // get values before inserting the row
    const margin = getMinMargin();
    const minimum = getMaxValue();
    const maximum = Math.max(minimum * 2, 100);
    // get prototype and update index
    const $table = $('#data-table-edit');
    const prototype = $table.data('prototype');
    const index = $table.data('index');
    $table.data('index', index + 1);
    // replace name
    const $row = $(prototype.replace(/__name__/g, index));
    // add
    $table.find('tbody').append($row);
    // update UI
    updateUI();
    // set values
    $(`${getMinimumSelector()}:last`).floatVal(minimum).selectFocus();
    $(`${getMaximumSelector()}:last`).floatVal(maximum);
    $(`${getMarginSelector()}:last`).intVal(margin);
}

/**
 * Remove the margin.
 *
 * @param {jQuery} $caller the caller.
 */
function removeMargin($caller) {
    'use strict';
    $caller.closest('tr').fadeOut(200, function () {
        $(this).remove();
        updateUI();
    });
}

/**
 * Sorts the margins.
 * @return {jQuery} the sorted rows.
 */
function sortMargins() {
    'use strict';
    const $table = $('#data-table-edit');
    /** @type {JQuery|HTMLElement|*} */
    const $body = $table.find('tbody');
    if ($body.children('tr').length > 1) {
        getSortedMargins($body).appendTo($body);
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    // handle buttons
    $('.btn-add').on('click', function () {
        addMargin();
    });
    $('.btn-sort').on('click', function () {
        sortMargins();
    });
    $('#data-table-edit').on('click', '.btn-delete-margin', function () {
        removeMargin($(this));
    });

    // update UI
    updateUI();

    // validation
    addMarginsMethods();
    $('form').initValidator();
});
