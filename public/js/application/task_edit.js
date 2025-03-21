/*  globals
    sortable,
    getSortedMargins,
    addMarginsMethods,
    validateOtherMargins,
    getMinimumInput, getMaximumInput,
    getMinimumSelector, getMaximumSelector,
*/

/**
 * Gets the value input selector.
 *
 * @return {string}
 */
function getValueSelector() {
    'use strict';
    return '#items input[name$="[value]"]';
}

/**
 * Update the user interface.
 */
function updateUI() {
    'use strict';
    // initialize the number and first char
    $(getMinimumSelector()).inputNumberFormat();
    $(getMaximumSelector()).inputNumberFormat();
    $(getValueSelector()).inputNumberFormat();

    // update tables
    $('#items .table-edit').each(function () {
        /** @type {JQuery|HTMLTableElement|*} */
        const $table = $(this);
        const rows = $table.find('tbody > tr').length;
        $table.toggleClass('d-none', rows === 0);
        $table.next('.empty-margins').toggleClass('d-none', rows !== 0);
        $table.parents('.item').find('.btn-sort-margin').toggleDisabled(rows < 2);
    });
    const $items = $('#items .item');
    $('.empty-items').toggleClass('d-none', $items.length !== 0);

    // update actions, rules and positions
    const isValidator = $('#edit-form').getValidator() || false;
    $items.each(function (index, item) {
        const $item = $(item);
        $item.find('.btn-up-item').toggleDisabled($item.is(':first-of-type'));
        $item.find('.btn-down-item').toggleDisabled($item.is(':last-of-type'));
        $item.find('input[name$="[position]"]').val(index);
        if (isValidator) {
            $item.find('.unique-name').rules('add', {unique: '.unique-name'});
        }
    });

    // update edit message
    $('#edit-form :input:first').trigger('input');
    // validateOtherMargins();
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
        sortable($items, {
            items: '.item',
            handle: '.item-header',
            forcePlaceholderSize: true,
            placeholderClass: 'border border-primary'
        });
        $items.on('sortupdate', updateUI)
            .data('sortable', true)
            .find('.item').removeAttr('role');
    }
}

/**
 * Gets the maximum of the maximum column.
 *
 * @param {jQuery} $table - the parent table.
 * @returns {number} - the maximum.
 */
function getMaxValue($table) {
    'use strict';
    let maximum = 0;
    const selector = getMaximumSelector();
    $table.find(selector).each(function () {
        maximum = Math.max(maximum, $(this).floatVal());
    });
    return maximum;
}

/**
 * Gets the minimum value of the value column.
 *
 * @param {jQuery} $table - the parent table.
 * @returns {number} - the minimum.
 */
function getMinValue($table) {
    'use strict';
    let minimum = Number.MAX_VALUE;
    $table.find(getValueSelector()).each(function () {
        minimum = Math.min(minimum, $(this).floatVal());
    });
    return minimum === Number.MAX_VALUE ? 1 : minimum;
}

/**
 * Gets the next available item index used for the prototype.
 *
 * @returns {number} the next index.
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
 * @returns {int} the next index.
 */
function getNextMarginIndex() {
    'use strict';
    const $items = $('#items');
    const index = $items.data('margin-index');
    $items.data('margin-index', index + 1);
    return index;
}

/**
 * Adds a new item.
 *
 * @return {jQuery} the newly created item for chaining.
 */
function addItem() {
    'use strict';
    // create item
    const $items = $('#items');
    const index = getNextItemIndex();
    const prototype = $items.data('prototype');
    const $item = $(prototype.replace(/__itemIndex__/g, index));

    // append
    $items.append($item);

    // update UI
    updateUI();

    // hide all except last
    $('#items .collapse.show:not(:last)').collapse('hide');

    // expand
    $('#items .collapse:last').collapse('show');

    // focus
    $item.find('input[name$="[name]"]:last').selectFocus().scrollInViewport();

    // drag and drop
    startDragItems();
    return $item;
}

/**
 * Remove an item.
 *
 * @param {jQuery} $caller - the caller (normally a button).
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
 * @param {jQuery} $caller - the caller (normally a button).
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
 * @param {jQuery} $caller - the caller (normally a button).
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
 * @param {jQuery} $caller - the caller (normally a button).
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
    const index = getNextMarginIndex();
    const prototype = $table.data('prototype');
    const $row = $(prototype.replace(/__marginIndex__/g, index));
    $table.find('tbody').append($row);
    // update UI
    updateUI();
    // set values
    $(`${getMinimumSelector()}:last`).floatVal(minimum)
        .selectFocus().scrollInViewport();
    $(`${getMaximumSelector()}:last`).floatVal(maximum);
    $(`${getValueSelector()}:last`).floatVal(value);
}

/**
 * Remove a margin (row).
 *
 * @param {jQuery} $caller - the caller.
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
 * @param {jQuery} $caller - the caller.
 */
function sortMargins($caller) {
    'use strict';
    const $table = $caller.parents('.item').find('.table-edit');
    if ($table.length === 0) {
        return;
    }
    const $body = $table.find('tbody');
    if ($body.children('tr').length > 1) {
        getSortedMargins($body).appendTo($body);
    }
}

/**
 * Update the toggle label title.
 *
 * @param {jQuery} $caller - the caller
 * @param {boolean} show - true if shown, false if hidden.
 */
function updateToggle($caller, show) {
    'use strict';
    const $form = $('#edit-form');
    const $label = $caller.parents('.item').find('.label-toggle');
    const title = show ? $form.data('show') : $form.data('hide');
    $label.attr('title', title);
}

/**
 * Collapse all shown margins.
 */
function collapseAll() {
    'use strict';
    $('#items .collapse.show').collapse('hide');
}

/**
 * Expand collapsed margins.
 *
 * @param $caller {jQuery} $caller - the caller
 */
function expand($caller) {
    'use strict';
    const $collapse = $caller.parents('.item').children('.collapse');
    if ($collapse.length && !$collapse.is('.show')) {
        $collapse.collapse('show');
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    // handle add button
    $('.btn-add-item').on('click', function () {
        addItem();
    });

    // handle item buttons
    $('#items').on('click', '.btn-delete-item', function () {
        removeItem($(this));
    }).on('click', '.btn-up-item', function () {
        moveUpItem($(this));
    }).on('click', '.btn-down-item', function () {
        moveDownItem($(this));
    }).on('click', '.btn-add-margin', function () {
        addMargin($(this));
    }).on('click', '.btn-delete-margin', function () {
        removeMargin($(this));
    }).on('click', '.btn-sort-margin', function () {
        sortMargins($(this));
    }).on('show.bs.collapse', '.collapse', function () {
        collapseAll();
    }).on('shown.bs.collapse', '.collapse', function () {
        updateToggle($(this), false);
    }).on('hide.bs.collapse', '.collapse', function () {
        updateToggle($(this), true);
    }).on('focus', '.unique-name', function () {
        expand($(this));
    }).on('dblclick', '.item-header', function () {
        $(this).parents('.item').find('.collapse').collapse('toggle');
    });

    // initialize search
    const $form = $('#edit-form');
    $('#task_unit').initTypeahead({
        url: $form.data('unit-search'),
        error: $form.data('unit-error')
    });
    $('#task_supplier').initTypeahead({
        url: $form.data('supplier-search'),
        error: $form.data('supplier-error')
    });

    // start drag and drop
    startDragItems();

    // update UI
    updateUI();

    // initialize validation
    addMarginsMethods();
    $form.initValidator();

    // $('#task_name').on('keydown', (e) =>  window.console.log(e.key));

});
