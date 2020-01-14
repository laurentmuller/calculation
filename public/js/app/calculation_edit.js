/**! compression tag for ftp-deployment */

/* globals updateErrors, sortable, Toaster, MenuBuilder  */

/**
 * -------------- The type ahead search helper --------------
 */
var SearchHelper = {

    /**
     * Initialize type ahead searches.
     */
    init: function () {
        'use strict';

        this.initSearchCustomer();
        this.initSearchProduct();
        this.initSearchUnits();
    },

    /**
     * Initialize the type ahead search customers.
     * 
     * @return {Object} The type ahead instance.
     */
    initSearchCustomer: function () {
        'use strict';

        const $element = $('#calculation_customer');
        return $element.initSearch({
            url: $('#edit-form').data('search-customer'),
            error: $('#edit-form').data('error-customer')
        });
    },

    /**
     * Initialize the type ahead search products.
     * 
     * @return {Object} The type ahead instance.
     */
    initSearchProduct: function () {
        'use strict';

        const $element = $('#item_search_input');
        return $element.initSearch({
            alignWidth: false,
            valueField: 'description',
            displayField: 'description',

            url: $('#edit-form').data('search-product'),
            error: $('#edit-form').data('error-unit'),

            onSelect: function (item) {
                // copy values
                $('#item_description').val(item.description);
                $('#item_unit').val(item.unit);
                $('#item_category').val(item.categoryId);
                $('#item_price').floatVal(item.price);
                $('#item_price').trigger('input');

                // clear
                $element.val('');// .data('typeahead').query = '';

                // select
                if (item.price) {
                    $('#item_quantity').selectFocus();
                } else {
                    $('#item_price').selectFocus();
                }
            }
        });
    },

    /**
     * Initialize the type ahead search product units.
     * 
     * @return {Object} The type ahead instance.
     */
    initSearchUnits: function () {
        'use strict';

        const $element = $('#item_unit');
        return $element.initSearch({
            url: $('#edit-form').data('search-unit'),
            error: $('#edit-form').data('error-unit')
        });
    }
};

/**
 * -------------- The move rows handler --------------
 */
var MoveRowHandler = {

    /**
     * Initialize.
     */
    init: function () {
        'use strict';

        const that = this;
        $('#data-table-edit').on('click', '.btn-first-item', function (e) {
            e.preventDefault();
            that.moveFirst($(this).getParentRow());
        }).on('click', '.btn-up-item', function (e) {
            e.preventDefault();
            that.moveUp($(this).getParentRow());
        }).on('click', '.btn-down-item', function (e) {
            e.preventDefault();
            that.moveDown($(this).getParentRow());
        }).on('click', '.btn-last-item', function (e) {
            e.preventDefault();
            that.moveLast($(this).getParentRow());
        });
    },

    /**
     * Move a source row before or after the target row.
     * 
     * @param $source
     *            {JQuery} - the row to move.
     * @param $target
     *            {JQuery} - the target row.
     * @param up
     *            {boolean} - true to move before the target (up); false to move
     *            after (down).
     * 
     * @return {JQuery} - The moved row.
     */
    move: function ($source, $target, up) {
        'use strict';

        if ($source && $target) {
            if (up) {
                $source.insertBefore($target);
            } else {
                $source.insertAfter($target);
            }
            $source.swapIdAndNames($target).scrollInViewport().timeoutToggle('table-success');
        }
        return $source;
    },

    /**
     * Move a calculation item to the first position.
     * 
     * @param $row
     *            {JQuery} - the row to move.
     * 
     * @return {JQuery} - The parent row.
     */
    moveFirst: function ($row) {
        'use strict';

        const index = $row.index();
        if (index > 1 && $row.prev()) {
            const $target = $row.siblings(':nth-child(2)');
            return this.move($row, $target, true);
        }
        return $row;
    },

    /**
     * Move a calculation item to the last position.
     * 
     * @param $row
     *            {JQuery} - the row to move.
     * 
     * @return {JQuery} - The parent row.
     */
    moveLast: function ($row) {
        'use strict';

        const count = $row.siblings().length;
        const index = $row.index();
        if (index < count && $row.next()) {
            const $target = $row.siblings(':last');
            return this.move($row, $target, false);
        }
        return $row;
    },

    /**
     * Move up a calculation item.
     * 
     * @param $row
     *            {JQuery} - the row to move.
     * 
     * @return {JQuery} - The parent row.
     */
    moveUp: function ($row) {
        'use strict';

        const index = $row.index();
        if (index > 1 && $row.prev()) {
            const $target = $row.prev();
            return this.move($row, $target, true);
        }
        return $row;
    },

    /**
     * Move down a calculation item.
     * 
     * @param $row
     *            {JQuery} - the row to move.
     * 
     * @return {JQuery} - The parent row.
     */
    moveDown: function ($row) {
        'use strict';

        const count = $row.siblings().length;
        const index = $row.index();
        if (index < count && $row.next()) {
            const $target = $row.next();
            return this.move($row, $target, false);
        }
        return $row;
    }
};

/**
 * -------------- The Application handler --------------
 */
var Application = {

    /**
     * Initialize.
     */
    init: function () {
        'use strict';

        this.initDragDrop(false).initMenus();
    },

    /**
     * Initialize the drag and drop.
     * 
     * @param destroy
     *            true to destroy the existing sortable.
     */
    initDragDrop: function (destroy) {
        'use strict';

        const that = this;
        const selector = '#data-table-edit tbody';
        const $bodies = $(selector);

        if (destroy) {
            // remove proxies
            $bodies.off('sortstart', that.dragStartProxy).off('sortupdate', that.dragStopProxy);
            sortable(selector, 'destroy');
        } else {
            // create proxies
            that.dragStartProxy = $.proxy(that.onDragStart, that);
            that.dragStopProxy = $.proxy(that.onDragStop, that);
        }

        // create
        sortable(selector, {
            items: 'tr:not(.drag-skip)',
            placeholderClass: 'table-primary',
            forcePlaceholderSize: false,
            acceptFrom: 'tbody'
        });

        // remove role attribute (aria)
        $('#data-table-edit tbody tr[role="option"').removeAttr('role');

        // add handlers
        $bodies.on('sortstart', that.dragStartProxy).on('sortupdate', that.dragStopProxy);

        return that;
    },

    /**
     * Initialize the edit dialog.
     */
    initItemDialog: function () {
        'use strict';

        // already initialized?
        const that = this;
        if (that.dialog) {
            return;
        }

        // dialog validator
        const options = {
            submitHandler: function () {
                if (that.$editingRow) {
                    that.onEditDialogSubmit();
                } else {
                    that.onAddDialogSubmit();
                }
            }
        };
        $('#item_form').initValidator(options);

        // dialog events
        $('#item_modal').on('show.bs.modal', function () {
            const key = that.$editingRow ? 'edit' : 'add';
            const title = $('#item_form').data(key);
            $('#dialog-title').html(title);
            if (that.$editingRow) {
                $('#item_search_row').hide();
                $('#item_delete_button').show();
            } else {
                $('#item_search_row').show();
                $('#item_delete_button').hide();
            }
        }).on('shown.bs.modal', function () {
            if (that.$editingRow) {
                if ($('#item_price').isEmptyValue()) {
                    $('#item_price').selectFocus();
                } else {
                    $('#item_quantity').selectFocus();
                }
                that.$editingRow.addClass('table-primary');
            } else {
                $('#item_search_input').selectFocus();
            }

        }).on('hide.bs.modal', function () {
            $('#data-table-edit tbody tr').removeClass('table-primary');
        });

        // buttons
        $('#item_delete_button').on('click', function () {
            $('#item_modal').modal('hide');
            if (that.$editingRow) {
                const button = that.$editingRow.findExists('.btn-delete-item');
                if (button) {
                    that.removeItem(button);
                }
            }
        });

        // widgets
        $('#item_price').inputNumberFormat();
        $('#item_quantity').inputNumberFormat();

        // bind
        const proxy = $.proxy(that.updateItemLine, that);
        $('#item_price, #item_quantity').on('input', proxy);

        // ok
        this.dialog = true;
        return that;
    },

    /**
     * Initialize group and item menus.
     */
    initMenus: function () {
        'use strict';

        const that = this;

        // adjust button
        $('.btn-adjust').on('click', function (e) {
            e.preventDefault();
            $(this).tooltip('hide');
            that.updateTotals(true);
        });

        // add item button
        $('#items-panel .btn-add-item').on('click', function (e) {
            e.preventDefault();
            that.showAddDialog();
        });

        // data table buttons
        $('#data-table-edit').on('click', '.btn-add-item', function (e) {
            e.preventDefault();
            that.showAddDialog();
        }).on('click', '.btn-edit-item', function (e) {
            e.preventDefault();
            that.showEditDialog($(this));
        }).on('click', '.btn-delete-item', function (e) {
            e.preventDefault();
            that.removeItem($(this));
        }).on('click', '.btn-delete-group', function (e) {
            e.preventDefault();
            that.removeGroup($(this));
        });

        return that;
    },

    /**
     * Reset the drag and drop handler.
     */
    resetHandler: function () {
        'use strict';

        return this.initDragDrop(true);
    },

    /**
     * Format a value with 2 fixed decimals and grouping separator.
     * 
     * @param value
     *            {Number} - the value to format.
     * @returns {String} - the formatted value.
     */
    toLocaleString: function (value) {
        'use strict';

        // get value
        let parsedValue = Number.parseFloat(value);
        if (Number.isNaN(parsedValue)) {
            parsedValue = Number.parseFloat(0);
        }

        // format
        let formatted = parsedValue.toLocaleString('de-CH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // replace grouping and separator
        const grouping = $('#edit-form').data('grouping');
        if (grouping) {
            formatted = formatted.replace(/’|'/g, grouping);
        }
        const decimal = $('#edit-form').data('decimal');
        if (decimal) {
            formatted = formatted.replace(/\./g, decimal);
        }

        return formatted;
    },

    /**
     * Update the total of the line in the item dialog.
     */
    updateItemLine: function () {
        'use strict';

        const that = this;
        const price = $('#item_price').floatVal();
        const quantity = $('#item_quantity').floatVal();
        const total = that.toLocaleString(price * quantity);
        $('#item_total').val(total);
    },

    /**
     * Update the move up/down buttons.
     */
    updateUpDownButton: function () {
        'use strict';

        // run hover bodies
        $('#data-table-edit tbody').each(function (index, element) {
            const $body = $(element);
            const $rows = $body.find('tr:not(:first)');
            const lastIndex = $rows.length - 1;

            // run over rows
            $rows.each(function (index, element) {
                const $row = $(element);
                const hideUp = index === 0;
                const hideDown = index === lastIndex;
                $row.find('.btn-first-item').updateClass('d-none', hideUp);
                $row.find('.btn-up-item').updateClass('d-none', hideUp);
                $row.find('.btn-down-item').updateClass('d-none', hideDown);
                $row.find('.btn-last-item').updateClass('d-none', hideDown);
                $row.find('.dropdown-divider:first').updateClass('d-none', hideUp && hideDown);
            });
        });

        return this;
    },

    /**
     * Update the totals.
     * 
     * @param adjust
     *            {bool} - true to adjust the user margin.
     * 
     * @return {Object} this instance.
     */
    updateTotals: function (adjust) {
        'use strict';

        const that = this;

        // show or hide empty items
        $('#empty-items').updateClass('d-none', $('#data-table-edit tbody').length !== 0);

        // validate user margin
        if (!$('#calculation_userMargin').valid()) {
            if ($('#user-margin-row').length === 0) {
                const $tr = $('<tr/>', {
                    'id': 'user-margin-row'
                });
                const $td = $('<td>', {
                    'class': 'text-muted',
                    'text': $('#edit-form').data('error-margin')
                });
                $tr.append($td);
                $('#totals-table tbody:first tr').remove();
                $('#totals-table tbody:first').append($tr);
            } else {
                $('#user-margin-row').removeClass('d-none');
            }
            $('.btn-adjust').attr('disabled', true);
            return that;
        }

        // abort
        if (that.jqXHR) {
            that.jqXHR.abort();
            that.jqXHR = null;
        }

        // parameters
        adjust = adjust || false;
        let data = $('#edit-form').serializeArray();
        if (adjust) {
            data.push({
                name: 'adjust',
                value: true
            });
        }

        // call
        const url = $('#edit-form').data('update');
        that.jqXHR = $.post(url, data, function (response) {
            const $totalPanel = $('#totals-panel');

            // OK?
            if (!response.result) {
                // hide
                $totalPanel.fadeOut();
                $(':submit').fadeOut();
                $('.btn-adjust').attr('disabled', true);

                // display error message
                const title = $('#edit-form').data('title');
                const message = $('#edit-form').data('error-update');
                Toaster.danger(message, title, $('#flashbags').data());
                return that;
            }

            // update content
            if (response.body) {
                $('#totals-table > tbody').html(response.body);
                $totalPanel.fadeIn();
            } else {
                $totalPanel.fadeOut();
            }
            if (adjust && response.margin) {
                $('#calculation_userMargin').intVal(response.margin).selectFocus();
            }
            if (response.below) {
                $('.btn-adjust').attr('disabled', false);
            } else {
                $('.btn-adjust').attr('disabled', true);
            }
            updateErrors();
            return that;
        });

        return that;
    },

    /**
     * Finds the table body for the given category.
     * 
     * @param id
     *            {int} - the category identifier.
     * @returns {JQuery} - the table body, if found; null otherwise.
     */
    findGroup: function (id) {
        'use strict';

        const $body = $("#data-table-edit tbody:has(input[name*='categoryId'][value=" + id + "])");
        if ($body.length) {
            return $body;
        }
        return null;
    },

    /**
     * Gets the edit dialog group.
     * 
     * @returns {Object} the group.
     */
    getDialogGroup: function () {
        'use strict';

        return {
            categoryId: $('#item_category').val(),
            code: $('#item_category :selected').text()
        };
    },

    /**
     * Gets the edit dialog item.
     * 
     * @returns {Object} the item.
     */
    getDialogItem: function () {
        'use strict';

        return {
            description: $('#item_description').val(),
            unit: $('#item_unit').val(),
            price: $('#item_price').val(),
            quantity: $('#item_quantity').val()
        };

    },

    /**
     * Sort groups by category name.
     */
    sortGroups: function () {
        'use strict';

        let $table = $('#data-table-edit');
        $table.find('tbody').sort(function (a, b) {
            return $('th:first', a).text().localeCompare($('th:first', b).text());
        }).appendTo($table);

        return this;
    },

    /**
     * Appends the given group to the table.
     * 
     * @param group
     *            {Object} - the group data used to update row.
     * @returns {JQuery} - the appended goup.
     */
    appendGroup: function (group) {
        'use strict';

        // get prototype
        const $parent = $('#data-table-edit');
        let prototype = $parent.getPrototype(/__groupIndex__/g, 'groupIndex');

        // append and update
        let $newGroup = $(prototype).appendTo($parent);
        $newGroup.find('tr:first th:first').text(group.code);
        $newGroup.findNamedInput('categoryId').val(group.categoryId);
        $newGroup.findNamedInput('code').val(group.code);

        // sort
        this.sortGroups();

        // reset the drag and drop handler.
        this.resetHandler();

        return $newGroup;
    },

    /**
     * Display the add item dialog.
     */
    showAddDialog: function () {
        'use strict';

        // initialize
        this.initItemDialog();

        // reset
        $('#item_form').resetValidator();

        // show
        this.$editingRow = null;
        $('tr.table-success').removeClass('table-success');
        $('#item_price').floatVal(1);
        $('#item_quantity').floatVal(1);
        $('#item_total').floatVal(1);
        $('#item_modal').modal('show');
    },

    /**
     * Handles the edit item event.
     * 
     * This function copy the element to the dialog and display it.
     * 
     * @param $element
     *            {JQuery} - the caller element (button).
     */
    showEditDialog: function ($element) {
        'use strict';

        // initialize
        this.initItemDialog();

        // reset
        $('#item_form').resetValidator();

        // copy
        const $row = $element.getParentRow();
        $('#item_description').val($row.findNamedInput('description').val());
        $('#item_unit').val($row.findNamedInput('unit').val());
        $('#item_category').val($row.parent().findNamedInput('categoryId').val());
        $('#item_price').floatVal($row.findNamedInput('price').val());
        $('#item_quantity').floatVal($row.findNamedInput('quantity').val());
        $('#item_total').floatVal($row.findNamedInput('total').val());
        $row.addClass('table-primary');

        // copy
        this.$editingRow = $row;

        $('#item_modal').modal('show');
    },

    /**
     * Remove a calculation group.
     * 
     * @param $element
     *            {JQuery} - the caller element (button).
     */
    removeGroup: function ($element) {
        'use strict';

        const that = this;
        $element.closest('tbody').removeFadeOut(function () {
            that.updateUpDownButton().updateTotals().resetHandler();
        });
    },

    /**
     * Remove a calculation item.
     * 
     * @param $element
     *            {JQuery} - the caller element (button).
     */
    removeItem: function ($element) {
        'use strict';

        // get row and body
        const that = this;
        let $row = $element.getParentRow();
        const $body = $row.parents('tbody');

        // if it is the last item then remove the group instead
        if ($body.children().length === 2) {
            $row = $body;
        }
        $row.removeFadeOut(function () {
            that.updateUpDownButton().updateTotals().resetHandler();
        });
    },

    /**
     * Handle the dialog form submit event when adding an item.
     */
    onAddDialogSubmit: function () {
        'use strict';

        // hide
        $('#item_modal').modal('hide');
        $('#empty-items').addClass('d-none');

        // get values
        const that = this;
        const group = that.getDialogGroup();
        const item = that.getDialogItem();

        // get or add group
        const $group = that.findGroup(group.categoryId) || that.appendGroup(group);

        // append
        const $item = $group.appendRow(item);

        // update total and scroll
        this.updateUpDownButton().updateTotals();
        $item.scrollInViewport().timeoutToggle('table-success');
    },

    /**
     * Handle the dialog form submit event when editing an item.
     */
    onEditDialogSubmit: function () {
        'use strict';

        // hide
        $('#item_modal').modal('hide');

        // row?
        const that = this;
        if (!that.$editingRow) {
            return;
        }

        // get values
        const group = that.getDialogGroup();
        const item = that.getDialogItem();

        let $oldBody = that.$editingRow.parents('tbody');
        let $oldGroup = that.$editingRow.parent().findNamedInput('categoryId');
        let oldCategoryId = $oldGroup.val();

        // same category?
        if (oldCategoryId !== group.categoryId) {
            // get or add group
            const $group = that.findGroup(group.categoryId) || that.appendGroup(group);

            // append
            const $row = $group.appendRow(item);

            // update callback
            const callback = function () {
                that.$editingRow.remove();
                that.$editingRow = null;
                that.updateUpDownButton().updateTotals();
                $row.scrollInViewport().timeoutToggle('table-success');
            };

            // remove old group if empty
            if ($oldBody.children().length === 2) {
                $oldBody.removeFadeOut(callback);
            } else {
                // update
                callback.call();
            }

        } else {
            // update
            that.$editingRow.updateRow(item);
            that.updateUpDownButton().updateTotals();
            that.$editingRow.timeoutToggle('table-success');
            that.$editingRow = null;
        }
    },

    /**
     * Handles the row drag start event.
     */
    onDragStart: function () {
        'use strict';
        $('tr.table-success').removeClass('table-success');
    },

    /**
     * Handles the row drag stop event.
     * 
     * @param e
     *            {Event} - the source event.
     */
    onDragStop: function (e) {
        'use strict';

        const that = this;
        const $row = $(e.detail.item);
        const origin = e.detail.origin;
        const destination = e.detail.destination;

        if (origin.container !== destination.container) {
            // -----------------------------
            // Moved to an other category
            // -----------------------------

            // create template and replace content
            const item = $row.getRowItem();
            const $newBody = $(destination.container);
            const $newRow = $newBody.appendRow(item);
            $row.replaceWith($newRow);

            // swap ids and names
            const rows = $newBody.children();
            for (let i = destination.index + 2, len = rows.length; i < len; i++) {
                const $source = $(rows[i - 1]);
                const $target = $(rows[i]);
                $source.swapIdAndNames($target);
            }

            // update callback
            const callback = function () {
                that.updateUpDownButton().updateTotals().resetHandler();
                $newRow.timeoutToggle('table-success');
            };

            // remove old group if empty
            const $oldBody = $(origin.container);
            if ($oldBody.children().length === 1) {
                $oldBody.removeFadeOut(callback);
            } else {
                // update
                callback.call();
            }

        } else if (origin.index !== destination.index) {
            // -----------------------------
            // Moved to a new position
            // -----------------------------
            const $target = origin.index < destination.index ? $row.prev() : $row.next();
            $row.swapIdAndNames($target).timeoutToggle('table-success');
        } else {
            // -----------------------------
            // No change
            // -----------------------------
            $row.timeoutToggle('table-success');
        }
    },
};

/**
 * -------------- JQuery extensions --------------
 */
$.fn.extend({

    /**
     * Swap id and name input attributes.
     * 
     * @param $target
     *            {JQuery} - the target row.
     * 
     * @return {JQuery} - The JQuery source row.
     */
    swapIdAndNames: function ($target) {
        'use strict';

        // get inputs
        const $source = $(this);
        const sourceInputs = $source.find('input');
        const targetInputs = $target.find('input');

        for (let i = 0, len = sourceInputs.length; i < len; i++) {
            // get source attributes
            const $sourceInput = $(sourceInputs[i]);
            const sourceId = $sourceInput.attr('id');
            const sourceName = $sourceInput.attr('name');

            // get target attributes
            const $targetInput = $(targetInputs[i]);
            const targetId = $targetInput.attr('id');
            const targetName = $targetInput.attr('name');

            // swap
            $targetInput.attr('id', sourceId).attr('name', sourceName);
            $sourceInput.attr('id', targetId).attr('name', targetName);
        }
        
        // update
        Application.updateUpDownButton();

        return $source;
    },

    /**
     * Finds an input element that have the name attribute within a given
     * substring.
     * 
     * @param name
     *            {string} - the partial attribute name.
     * 
     * @return {JQuery} - The input, if found; null otherwise.
     */
    findNamedInput: function (name) {
        'use strict';

        const selector = "input[name*='" + name + "']";
        const $result = $(this).find(selector);
        return $result.length ? $result : null;
    },

    /**
     * Fade out and remove the selected element.
     * 
     * @param callback
     *            {Function} - the function to call after the element is
     *            removed.
     * @return null
     */
    removeFadeOut: function (callback) {
        'use strict';

        const $element = $(this);
        $element.fadeOut(400, function () { // 400
            $element.remove();
            if ($.isFunction(callback)) {
                callback();
            }
        });
        return null;
    },

    /**
     * Gets the template prototype from the current element.
     * 
     * @param pattern
     *            {String} - the regex pattern used to replace the index.
     * @param key
     *            {String} - the data key used to retrieve and update the index.
     * @returns {String} - the template.
     */
    getPrototype: function (pattern, key) {
        'use strict';

        const $parent = $(this);

        // get and update index
        const $table = $('#data-table-edit');
        const index = $table.data(key);
        $table.data(key, index + 1);

        // get prototype
        const prototype = $parent.data('prototype');

        // replace index
        return prototype.replace(pattern, index);
    },

    /**
     * Gets item data from the current row.
     * 
     * @returns {Object} the item data.
     */
    getRowItem: function () {
        'use strict';

        const $row = $(this);
        return {
            description: $row.findNamedInput('description').val(),
            unit: $row.findNamedInput('unit').val(),
            price: $row.findNamedInput('price').val(),
            quantity: $row.findNamedInput('price').val(),
        };
    },

    /**
     * Create a new row and appends to this current parent group (tbody).
     * 
     * @param item
     *            {Object} - the row data used to update the row
     * @returns {JQuery} - the created row.
     */
    appendRow: function (item) {
        'use strict';

        // tbody
        const $parent = $(this);

        // get prototype
        const prototype = $parent.getPrototype(/__itemIndex__/g, 'itemIndex');

        // append and update
        return $(prototype).appendTo($parent).updateRow(item);
    },

    /**
     * Copy the values of the item to the current row.
     * 
     * @param row
     *            {JQuery} - the row to update.
     * @param item
     *            {Object} - the item to get values from.
     * @returns {JQuery} - The row.
     */
    updateRow: function (item) {
        'use strict';

        const $row = $(this);

        // update inputs
        $row.findNamedInput('description').val(item.description);
        $row.findNamedInput('unit').val(item.unit);
        $row.findNamedInput('price').val(item.price);
        $row.findNamedInput('quantity').val(item.quantity);
        $row.findNamedInput('total').val(item.price * item.quantity);

        // update cells
        $row.find('td:eq(0) .btn-edit-item').text(item.description);
        $row.find('td:eq(1)').text(item.unit);
        $row.find('td:eq(2)').text(Application.toLocaleString(item.price));
        $row.find('td:eq(3)').text(Application.toLocaleString(item.quantity));
        $row.find('td:eq(4)').text(Application.toLocaleString(item.price * item.quantity));

        return $row;
    },

    /**
     * Initialize a type ahead search.
     * 
     * @param options
     *            {Object} - the options to override.
     * 
     * @return {Object} The type ahead instance.
     */
    initSearch: function (options) {
        'use strict';

        const $element = $(this);

        // default options
        const defaults = {
            valueField: '',
            ajax: {
                url: options.url
            },
            // overridden functions (all are set in the server side)
            matcher: function () {
                return true;
            },
            grepper: function (data) {
                return data;
            },
            onSelect: function () {
                $element.select();
            },
            onError: function () {
                const message = options.error;
                const title = $('#edit-form').data('title');
                Toaster.danger(message, title, $('#flashbags').data());
            }
        };

        // merge
        const settings = $.extend(true, defaults, options);

        return $element.typeahead(settings);
    },

    /**
     * Gets the parent row.
     * 
     * @returns {JQuery} - The parent row.
     */
    getParentRow: function () {
        'use strict';

        return $(this).parents('tr:first');
    },

    /**
     * Creates the context menu items.
     * 
     * @returns {Object} the context menu items.
     */
    getContextMenuItems: function () {
        'use strict';

        const builder = new MenuBuilder();
        $(this).getParentRow().find('.dropdown-menu').children().each(function () {
            const $this = $(this);
            if ($this.hasClass('dropdown-divider')) {
                builder.addSeparator();
            } else if ($this.isSelectable()) { // dropdown-item
                builder.addItem($this);
            }
        });

        return builder.getItems();
    }
});

/**
 * Initialize the context menu for the table items.
 */
function initContextMenu() {
    'use strict';

    // build callback
    const callback = function ($element) {
        // get items
        const items = $element.getContextMenuItems();
        if ($.isEmptyObject(items)) {
            return false;
        }

        return {
            autoHide: true,
            zIndex: 1000,
            callback: function (key, options, e) {
                const item = options.items[key];
                if (item.link) {
                    e.stopPropagation();
                    item.link.get(0).click();
                    return true;
                }
            },
            events: {
                show: function () {
                    $('.dropdown-menu.show').removeClass('show');
                    $(this).parent().addClass('table-primary');
                },
                hide: function () {
                    $(this).parent().removeClass('table-primary');
                }
            },
            items: items
        };
    };

    // create
    $.contextMenu({
        build: callback,
        selector: '.table-edit th:not(.d-print-none), .table-edit td:not(.d-print-none)'
    });
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // searches
    SearchHelper.init();

    // move rows
    MoveRowHandler.init();

    // application
    Application.init();

    // context menu
    initContextMenu();

    // errors
    updateErrors();

    // main form validation
    $('#edit-form').initValidator();

    // user margin
    const $margin = $('#calculation_userMargin');
    $margin.on('input propertychange', function () {
        $margin.updateTimer(function () {
            Application.updateTotals();
        }, 250);
    });
});
