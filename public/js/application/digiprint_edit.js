/**! compression tag for ftp-deployment */

/**
 * Initialize the number input formats.
 */
function initInputFormat() {
    'use strict';

    const options = {
        'decimal': 0
    };        
    $("#digi_print_width").inputNumberFormat(options);
    $("#digi_print_height").inputNumberFormat(options);
    $("input[name$='[minimum]']").inputNumberFormat(options);
    $("input[name$='[maximum]']").inputNumberFormat(options);
    $("input[name$='[amount]']").inputNumberFormat();
}

/**
 * Gets the maximum quantity.
 * 
 * @param {JQuery}
 *            table - the parent table.
 * @returns integer - the maximum quantity.
 */
function getMaxQuantity($table) {
    'use strict';

    let maximum = 0;
    $table.find("input[name$='[maximum]']").each(function () {
        maximum = Math.max(maximum, $(this).intVal());
    });
    return maximum;
}

/**
 * Gets the minimum amount value.
 * 
 * @param {JQuery}
 *            table - the parent table.
 * 
 * @returns float - the minimum amount value.
 */
function getMinAmount($table) {
    'use strict';

    let minimum = Number.MAX_VALUE;
    $table.find("input[name$='[amount]']").each(function () {
        minimum = Math.min(minimum, $(this).floatVal());
    });
    return minimum === Number.MAX_VALUE ? 0 : minimum;
}

/**
 * Gets the next available index used for the prototype.
 * 
 * @returns int the next index.
 */
function getNextIndex() {
    'use strict';

    const $prototype = $('#prototype');
    const index = $prototype.data('index');
    $prototype.data('index', index + 1);

    return index;
}

/**
 * Gets the new row prototype.
 * 
 * @returns string the prototype.
 */
function getPrototype() {
    'use strict';
    return $('#prototype').data('prototype');
}

/**
 * Update the user interface.
 */
function updateUI() {
    'use strict';

    initInputFormat();

    let disabled = true;
    $('.table-edit').each(function () {
        const $table = $(this);
        const rows = $table.find('tbody > tr').length;
        $table.toggleClass('d-none', rows === 0);
        $('#empty-' + $table.attr('id')).toggleClass('d-none', rows !== 0);
        if (disabled) {
            disabled = rows < 2;
        }
    });
    $('.btn-sort').attr('disabled', disabled);
}

/**
 * Remove an item (row).
 * 
 * @param {jQuery}
 *            $caller - the caller.
 */
function removeItem($caller) {
    'use strict';
    const $row = $caller.closest('tr');
    $row.fadeOut(200, function () {
        $row.remove();
        updateUI();
    });
}

/**
 * Adds a new item (row).
 * 
 * @param {jQuery}
 *            $caller - the caller button.
 */
function addItem($caller) {
    'use strict';
    const $table = $('#' + $caller.data('table'));
    if ($table.length === 0) {
        return;
    }
    // get values before inserting the row
    const amount = getMinAmount($table);
    const minimum = getMaxQuantity($table);
    const maximum = Math.max(minimum * 2, 10);

    // add row
    const index = getNextIndex();
    const prototype = getPrototype();
    const $row = $(prototype.replace(/__name__/g, index));
    $table.find('tbody').append($row);

    // update UI
    updateUI();

    // set values
    $table.find("input[name$='[type]']:last").intVal($table.data('type'));
    $table.find("input[name$='[minimum]']:last").intVal(minimum).selectFocus();
    $table.find("input[name$='[maximum]']:last").intVal(maximum);
    $table.find("input[name$='[amount]']:last").floatVal(amount);
}

/**
 * Sorts items in the tables.
 * 
 * @returns {jQuery} - the sorted tables.
 */
function sortTables() {
    'use strict';

    return $('.table-edit').each(function () {
        const $table = $(this);
        const $body = $table.find('tbody');
        const $rows = $body.find('tr');
        if ($rows.length > 1) {
            $rows.sort(function (rowA, rowB) {
                const valueA = $(rowA).find("input[name$='[minimum]']").intVal();
                const valueB = $(rowB).find("input[name$='[minimum]']").intVal();
                if (valueA < valueB) {
                    return -1;
                } else if (valueA > valueB) {
                    return 1;
                } else {
                    return 0;
                }
            }).appendTo($body);
        }
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // handle delete button
    const $table = $('.table-edit');
    $table.on('click', '.btn-delete', function (e) {
        e.preventDefault();
        removeItem($(this));
    });

    // handle add button
    $('.btn-add').on('click', function (e) {
        e.preventDefault();
        addItem($(this));
    });

    // handle sort button
    $('.btn-sort').on('click', function (e) {
        e.preventDefault();
        sortTables();
    });

    // update UI
    updateUI();

    // validation
    $('form').initValidator();
}(jQuery));
