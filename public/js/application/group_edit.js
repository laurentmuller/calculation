/**! compression tag for ftp-deployment */

/**
 * Update the user interface.
 */
function updateUI() {
    'use strict';
    // initialize the number input formats
    $("input[name$='[minimum]']").inputNumberFormat();
    $("input[name$='[maximum]']").inputNumberFormat();

    // show / hide elements
    const $table = $('#data-table-edit');
    const rows = $table.find('tbody > tr').length;
    $table.toggleClass('d-none', rows === 0);
    $('.btn-sort').toggleDisabled(rows < 2);
    $('#empty_margins').toggleClass('d-none', rows > 0);
}

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
    let minimum = Number.MAX_VALUE;
    $("input[name$='[margin]']").each(function () {
        minimum = Math.min(minimum, $(this).intVal());
    });
    return minimum === Number.MAX_VALUE ? 0 : minimum;
}

/**
 * Adds a new margin row.
 *
 * @param {JQuery} $table - the parent table.
 */
function addMargin($table) {
    'use strict';
    // get values before inserting the row
    const margin = getMinMargin();
    const minimum = getMaxValue();
    const maximum = Math.max(minimum * 2, 100);

    // get prototype and update index
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
    $("input[name$='[minimum]']:last").floatVal(minimum).selectFocus();
    $("input[name$='[maximum]']:last").floatVal(maximum);
    $("input[name$='[margin]']:last").intVal(margin);
}

/**
 * Remove the margin.
 *
 * @param {JQuery} $caller - the caller.
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
 *
 * @param {JQuery} $table - the parent table.
 */
function sortMargins($table) {
    'use strict';
    const $body = $table.find('tbody');
    const $rows = $body.find('tr');
    if ($rows.length < 2) {
        return;
    }

    $rows.sort(function (rowA, rowB) {
        const valueA = $(rowA).find("input[name$='[minimum]']").floatVal();
        const valueB = $(rowB).find("input[name$='[minimum]']").floatVal();
        if (valueA < valueB) {
            return -1;
        } else if (valueA > valueB) {
            return 1;
        } else {
            return 0;
        }
    }).appendTo($body);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    // handle delete button
    const $table = $('#data-table-edit');
    $table.on('click', '.btn-delete', function (e) {
        e.preventDefault();
        removeMargin($(this));
    });

    // handle add button
    $('.btn-add').on('click', function (e) {
        e.preventDefault();
        addMargin($table);
    });

    // handle sort button
    $('.btn-sort').on('click', function (e) {
        e.preventDefault();
        sortMargins($table);
    });

    // update UI
    updateUI();

    // validation
    $('form').initValidator();
}(jQuery));
