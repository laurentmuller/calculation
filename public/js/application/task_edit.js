/**! compression tag for ftp-deployment */

/**
 * Update the user interface.
 */
function updateUI() {
    'use strict';

    // initialize the number input formats
    $("input[name$='[minimum]']").inputNumberFormat();
    $("input[name$='[maximum]']").inputNumberFormat();
    $("input[name$='[value]']").inputNumberFormat();

    // update tables
    $('.table-edit').each(function () {
        const $table = $(this);
        const rows = $table.find('tbody > tr').length;
        $table.toggleClass('d-none', rows === 0);
        $table.next('.empty-margins').toggleClass('d-none', rows !== 0);
        $table.parents('.form-group').find('.btn-sort-margin').toggleClass('disabled', rows < 2);

    });
    $('.empty-items').toggleClass('d-none', $('.item').length !== 0);
}

/**
 * Gets the maximum of the maximum column.
 * 
 * @param {JQuery}
 *            table - the parent table.
 * @returns float - the maximum value.
 */
function getMaxValue($table) {
    'use strict';

    let maximum = 0;
    $table.find("input[name$='[maximum]']").each(function () {
        maximum = Math.max(maximum, $(this).floatVal());
    });
    return maximum;
}

/**
 * Gets the minimum of the value column.
 * 
 * @param {JQuery}
 *            table - the parent table.
 * 
 * @returns float - the minimum amount value.
 */
function getMinValue($table) {
    'use strict';

    let minimum = Number.MAX_VALUE;
    $table.find("input[name$='[value]']").each(function () {
        minimum = Math.min(minimum, $(this).floatVal());
    });
    return minimum === Number.MAX_VALUE ? 1 : minimum;
}

/**
 * Gets the next available item index used for the prototype.
 * 
 * @returns int the next index.
 */
function getNextItemIndex() {
    'use strict';

    const $items = $('.items');
    const index = $items.data('item-index');
    $items.data('item-index', index + 1);

    return index;
}

/**
 * Gets the next available margin index used for the prototype.
 * 
 * @returns int the next index.
 */
function getNextMarginIndex() {
    'use strict';

    const $items = $('.items');
    const index = $items.data('margin-index');
    $items.data('margin-index', index + 1);

    return index;
}

/**
 * Gets the item prototype.
 * 
 * @returns string the prototype.
 */
function getItemPrototype() {
    'use strict';
    return $('.items').data('prototype');
}

/**
 * Gets the margin prototype.
 * 
 * @param {JQuery}
 *            table - the parent table.
 * @returns string the prototype.
 */
function getMarginPrototype($table) {
    'use strict';
    return $table.data('prototype');
}

/**
 * Adds a new item.
 */
function addItem() {
    'use strict';

    // create and add item
    const index = getNextItemIndex();
    const prototype = getItemPrototype();
    const $item = $(prototype.replace(/__itemIndex__/g, index));
    $('.items').append($item);

    // update UI
    updateUI();

    // focus
    $item.find("input[name$='[name]']:last").selectFocus();
}

/**
 * Adds a new margin.
 * 
 * @param {jQuery}
 *            $caller - the caller.
 */
function addMargin($caller) {
    'use strict';

    const $table = $caller.parents('.item').find('.table-edit');
    if ($table.length === 0) {
        return;
    }

    // get values before inserting the row
    const value = getMinValue($table);
    const minimum = getMaxValue($table);
    const maximum = Math.max(minimum * 2, 1);

    // create and add margin
    const index = getNextMarginIndex($table);
    const prototype = getMarginPrototype($table);
    const $row = $(prototype.replace(/__marginIndex__/g, index));
    $table.find('tbody').append($row);

    // update UI
    updateUI();

    // set values
    $table.find("input[name$='[minimum]']:last").floatVal(minimum).selectFocus();
    $table.find("input[name$='[maximum]']:last").floatVal(maximum);
    $table.find("input[name$='[value]']:last").floatVal(value);
}

/**
 * Remove a item.
 * 
 * @param {jQuery}
 *            $caller - the caller.
 */
function removeItem($caller) {
    'use strict';

    $caller.closest('.item').fadeOut(200, function () {
        $(this).remove();
        updateUI();
    });
}

/**
 * Remove a margin (row).
 * 
 * @param {jQuery}
 *            $caller - the caller.
 */
function removeMargin($caller) {
    'use strict';

    $caller.closest('tr').fadeOut(200, function () {
        $(this).remove();
        updateUI();
    });
}

/**
 * Sort margins.
 * 
 * @param {jQuery}
 *            $caller - the caller.
 */
function sortMargins($caller) {
    'use strict';

    const $table = $caller.parents('.item').find('.table-edit');
    if ($table.length === 0) {
        return;
    }    
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

    // handle buttons
    $('.btn-add-item').on('click', function (e) {
        e.preventDefault();
        addItem();
    });
    $('.items').on('click', '.btn-delete-item', function (e) {
        e.preventDefault();
        removeItem($(this));
    }).on('click', '.btn-add-margin', function (e) {
        e.preventDefault();
        addMargin($(this));
    }).on('click', '.btn-delete-margin', function (e) {
        e.preventDefault();
        removeMargin($(this));
    }).on('click', '.btn-sort-margin', function (e) {
        e.preventDefault();
        sortMargins($(this));
    });

    // update UI
    updateUI();

    // validation
    $('form').initValidator({
        'inline': true
    });
}(jQuery));
