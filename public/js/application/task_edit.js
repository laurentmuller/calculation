/**! compression tag for ftp-deployment */

/* globals sortable */

/**
 * Update the user interface.
 */
function updateUI() {
    'use strict';

    // initialize the number input formats
    $('#items input[name$="[minimum]"]').inputNumberFormat();
    $('#items input[name$="[maximum]"]').inputNumberFormat();
    $('#items input[name$="[value]"]').inputNumberFormat();

    // update tables
    $('#items .table-edit').each(function () {
        const $table = $(this);
        const rows = $table.find('tbody > tr').length;
        $table.toggleClass('d-none', rows === 0);
        $table.next('.empty-margins').toggleClass('d-none', rows !== 0);
        $table.parents('.item').find('.btn-sort-margin').toggleDisabled(rows < 2);
    });
    $('.empty-items').toggleClass('d-none', $('#items .item').length !== 0);

    // update actions, rules and positions
    let position = 0;
    $('#items .item').each(function (_index, item) {
        const $item = $(item);
        $item.find('.btn-up-item').toggleDisabled($item.is(':first-of-type'));
        $item.find('.btn-down-item').toggleDisabled($item.is(':last-of-type'));
        $item.find('.unique-name').rules('add', {
            unique: '.unique-name'
        });
        $item.find('input[name$="[position]"]').val(position++);
    });
}

/**
 * Starts the drag and drop of items.
 */
function startDragItems() {
    'use strict';
    const $items = $('#items');

    // destroy
    if ($items.data('sortable')) {
        $items.off('sortupdate', updateUI);
        $items.data('sortable', false);
        sortable($items, 'destroy');
    }

    // items?
    if ($items.find('.item').length > 1) {
        const isRadius = $('.card:first').css('border-radius') !== '0px';
        const placeholderClass = 'border border-primary' + (isRadius ? ' rounded' : '');
        sortable($items, {
            items: '.item',
            handle: '.stretched-link',
            forcePlaceholderSize: true,
            placeholderClass: placeholderClass
        });
        $items.on('sortupdate', updateUI);
        $items.data('sortable', true);
    }
}

/**
 * Gets the maximum of the maximum column.
 *
 * @param {JQuery}
 *            table - the parent table.
 * @returns float - the maximum.
 */
function getMaxValue($table) {
    'use strict';

    let maximum = 0;
    $table.find('input[name$="[maximum]"]').each(function () {
        maximum = Math.max(maximum, $(this).floatVal());
    });
    return maximum;
}

/**
 * Gets the minimum of the value column.
 *
 * @param {JQuery}
 *            table - the parent table.
 * @returns float - the minimum.
 */
function getMinValue($table) {
    'use strict';

    let minimum = Number.MAX_VALUE;
    $table.find('input[name$="[value]"]').each(function () {
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

    const $items = $('#items');
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

    const $items = $('#items');
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
    return $('#items').data('prototype');
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
 *
 * @return {jQuery} the newly created item for chaining.
 */
function addItem() {
    'use strict';

    // create item
    const index = getNextItemIndex();
    const prototype = getItemPrototype();
    const $item = $(prototype.replace(/__itemIndex__/g, index));

    // append
    $('#items').append($item);

    // update UI
    updateUI();

    // focus
    $item.find('input[name$="[name]"]:last').selectFocus().scrollInViewport();

    // hide others
    $('#items').find('.collapse:not(:last)').collapse('hide');

    // expand
    $('#items').find('.collapse:last').addClass('show');

    // drag and drop
    startDragItems();

    return $item;
}

/**
 * Remove an item.
 *
 * @param {jQuery}
 *            $caller - the caller (normally a button).
 */
function removeItem($caller) {
    'use strict';

    $caller.closest('.item').fadeOut(200, function () {
        $(this).remove();
        updateUI();
        startDragItems();
    });
}

/**
 * Move up an item.
 *
 * @param {jQuery}
 *            $caller - the caller (normally a button).
 * @return {jQuery} the item for chaining.
 */
function moveUpItem($caller) {
    'use strict';

    // first?
    const $source = $caller.closest('.item');
    if ($source.is(':first-of-type')) {
        return $source;
    }

    // previous?
    const $target = $source.prev('.item');
    if (0 === $target.length || $source === $target) {
        return $source;
    }

    // move
    $target.insertAfter($source);
    updateUI();

    return $source;
}

/**
 * Move down an item.
 *
 * @param {jQuery}
 *            $caller - the caller (normally a button).
 * @return {jQuery} the item for chaining.
 */
function moveDownItem($caller) {
    'use strict';

    // last?
    const $source = $caller.closest('.item');
    if ($source.is(':last-of-type')) {
        return $source;
    }

    // next?
    const $target = $source.next('.item');
    if (0 === $target.length || $source === $target) {
        return $source;
    }

    // move
    $target.insertBefore($source);
    updateUI();

    return $source;
}

/**
 * Adds a new margin.
 *
 * @param {jQuery}
 *            $caller - the caller (normally a button).
 */
function addMargin($caller) {
    'use strict';

    // get table
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
    $table.find('input[name$="[minimum]"]:last').floatVal(minimum).selectFocus().scrollInViewport();
    $table.find('input[name$="[maximum]"]:last').floatVal(maximum);
    $table.find('input[name$="[value]"]:last').floatVal(value);
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
        const valueA = $(rowA).find('input[name$="[minimum]"]').floatVal();
        const valueB = $(rowB).find('input[name$="[minimum]"]').floatVal();
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

    // handle add button
    $('.btn-add-item').on('click', function (e) {
        e.preventDefault();
        addItem();
    });

    // handle item buttons
    $('#items').on('click', '.btn-delete-item', function (e) {
        e.preventDefault();
        removeItem($(this));
    }).on('click', '.btn-up-item', function (e) {
        e.preventDefault();
        moveUpItem($(this));
    }).on('click', '.btn-down-item', function (e) {
        e.preventDefault();
        moveDownItem($(this));
    }).on('click', '.btn-add-margin', function (e) {
        e.preventDefault();
        addMargin($(this));
    }).on('click', '.btn-delete-margin', function (e) {
        e.preventDefault();
        removeMargin($(this));
    }).on('click', '.btn-sort-margin', function (e) {
        e.preventDefault();
        sortMargins($(this));
    }).on('show.bs.collapse', '.collapse', function () {
        const $link = $(this).parents('.item').find('.stretched-link');
        $link.attr('title', $('#edit-form').data('hide'));
        $link.find('i').toggleClass('fa-caret-down fa-caret-right');
    }).on('hide.bs.collapse', '.collapse', function () {
        const $link = $(this).parents('.item').find('.stretched-link');
        $link.attr('title', $('#edit-form').data('show'));
        $link.find('i').toggleClass('fa-caret-down fa-caret-right');
    }).on('focus', '.unique-name', function () {
        $(this).parents('.card').children('.collapse').collapse('show');
    });

    // initalize search
    const $form = $("#edit-form");
    $("#task_unit").initTypeahead({
        url: $form.data("unit-search"),
        error: $form.data("unit-error")
    });
    $("#task_supplier").initTypeahead({
        url: $form.data("supplier-search"),
        error: $form.data("supplier-error")
    });

    // start drag & drop
    startDragItems();

    // initalize validation
    $form.initValidator();

    // update UI
    updateUI();
}(jQuery));
