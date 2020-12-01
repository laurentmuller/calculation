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
 * @param {jQuery}
 *            $table - the parent table.
 */
function addMargin($table) {
    'use strict';

    // get prototype and update index
    const prototype = $table.data('prototype');
    const index = $table.data('index');
    $table.data('index', index + 1);

    // replace name
    const newForm = prototype.replace(/__name__/g, index);

    // get range
    const margin = getMinMargin();
    const minimum = getMaxValue();
    const maximum = Math.max(minimum * 2, 100);

    // add
    $('#data-table-edit > tbody').append(newForm);
    $('#data-table-edit').removeClass('d-none');
    $('#empty_margins').addClass('d-none');

    // set values and add validation
    $("input[name$='[minimum]']:last").floatVal(minimum).inputNumberFormat().selectFocus();
    $("input[name$='[maximum]']:last").floatVal(maximum).inputNumberFormat();
    $("input[name$='[margin]']:last").intVal(margin).inputNumberFormat({
        'decimal': 0
    });

    // update sort button
    if ($('#data-table-edit > tbody > tr').length > 1) {
        $('.btn-sort').removeClass('disabled');
    }
}

/**
 * Remove the margin.
 * 
 * @param {jQuery}
 *            $caller - the caller.
 */
function removeMargin($caller) {
    'use strict';

    // remove row
    const row = $caller.closest('tr');
    row.fadeOut(200, function () {
        row.remove();
        const length = $('#data-table-edit > tbody > tr').length;
        if (length === 0) {
            $('#data-table-edit').addClass('d-none');
            $('#empty_margins').removeClass('d-none');
        }
        if (length < 2) {
            $('.btn-sort').addClass('disabled');
        }
    });
}

/**
 * Sorts the margins.
 * 
 * @param {jQuery}
 *            $table - the parent table.
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

    // add numbers validation
    $("input[name$='[minimum]']").inputNumberFormat();
    $("input[name$='[maximum]']").inputNumberFormat();
    $("input[name$='[margin]']").inputNumberFormat({
        'decimal': 0
    });

    // validation
    $('form').initValidator();
}(jQuery));
