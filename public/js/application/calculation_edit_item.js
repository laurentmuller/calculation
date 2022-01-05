/**! compression tag for ftp-deployment */

/**
 * Edit item dialog handler.
 */
const EditItemDialog = class { /* exported EditItemDialog */

    /**
     * Constructor.
     */
    constructor(application) {
        'use strict';
        this.application = application;
        this._init();
    }

    /**
     * Display the add item dialog.
     *
     * @param {jQuery}
     *            $row - the selected row.
     * @return {EditItemDialog} This instance for chaining.
     */
    showAdd($row) {
        'use strict';

        // initialize
        this.application.initDragDialog();
        this.$editingRow = null;

        // reset
        this.$form.resetValidator();

        // update values
        if ($row) {
            const $input = $row.siblings(':first').findNamedInput('category');
            if ($input) {
                this.$category.val($input.val());
            }
        }
        this.$price.floatVal(1);
        this.$quantity.floatVal(1);
        this.$total.text(this._formatValue(1));

        // show
        this.$modal.modal('show');

        return this;
    }

    /**
     * Display the edit item dialog. This function copy the element to the
     * dialog and display it.
     *
     * @param {jQuery}
     *            $row - the selected row.
     * @return {EditItemDialog} This instance for chaining.
     */
    showEdit($row) {
        'use strict';

        // initialize
        this.application.initDragDialog();
        this.$editingRow = $row;

        // reset
        this.$form.resetValidator();

        // copy values
        this.$description.val($row.findNamedInput('description').val());
        this.$unit.val($row.findNamedInput('unit').val());
        this.$category.val($row.parent().findNamedInput('category').val());
        this.$price.floatVal($row.findNamedInput('price').floatVal());
        this.$quantity.floatVal($row.findNamedInput('quantity').floatVal());
        this.$total.text(this._formatValue($row.findNamedInput('total').floatVal()));

        // show
        this.$modal.modal('show');

        return this;
    }

    /**
     * Hide the dialog.
     *
     * @return {EditItemDialog} This instance for chaining.
     */
    hide() {
        'use strict';
        this.$modal.modal('hide');
        return this;
    }

    /**
     * Gets the selected group.
     *
     * @returns {Object} the group.
     */
    getGroup() {
        'use strict';
        const $selection = this.$category.getSelectedOption();
        return {
            id: Number.parseInt($selection.data('groupId'), 10),
            code: $selection.data('groupCode')
        };
    }

    /**
     * Gets the selected category.
     *
     * @returns {Object} the category.
     */
    getCategory() {
        'use strict';
        const $selection = this.$category.getSelectedOption();
        return {
            id: this.$category.intVal(),
            code: $selection.text()
        };
    }

    /**
     * Gets the selected item.
     *
     * @returns {Object} the item.
     */
    getItem() {
        'use strict';
        const that = this;
        const price = that.$price.floatVal();
        const quantity = that.$quantity.floatVal();
        const total = that._roundValue(price * quantity);

        return {
            description: that.$description.val(),
            unit: that.$unit.val(),
            price: price,
            quantity: quantity,
            total: total
        };
    }

    /**
     * Gets the editing row.
     *
     * @return {JQuery} the row or null if none.
     */
    getEditingRow() {
        'use strict';
        return this.$editingRow;
    }

    /**
     * Initialize.
     *
     * @return {EditItemDialog} This instance for chaining.
     */
    _init() {
        'use strict';

        const that = this;
        that.$form = $('#item_form');
        that.$modal = $('#item_modal');

        that.$description = $('#item_description');
        that.$unit = $('#item_unit');
        that.$category = $('#item_category');
        that.$price = $('#item_price').inputNumberFormat();
        that.$quantity = $('#item_quantity').inputNumberFormat();
        that.$total = $('#item_total');
        that.$search = $('#item_search_input');
        that.$searchRow = $('#item_search_row');
        that.$cancelButton = $('#item_cancel_button');
        that.$deleteButton = $('#item_delete_button');

        // validator
        const options = {
            submitHandler: function () {
                if (that.$editingRow) {
                    that.application.onEditItemDialogSubmit();
                } else {
                    that.application.onAddItemDialogSubmit();
                }
            }
        };
        that.$form.initValidator(options);

        // handle dialog events
        that.$modal.on('show.bs.modal', $.proxy(that._onDialogShow, that));
        that.$modal.on('shown.bs.modal', $.proxy(that._onDialogVisible, that));
        that.$modal.on('hide.bs.modal', $.proxy(that._onDialogHide, that));

        // handle input events
        const updateProxy = $.proxy(that._updateTotal, that);
        that.$price.on('input', updateProxy);
        that.$quantity.on('input', updateProxy);

        // handle delete button
        that.$deleteButton.on('click', $.proxy(that._onDelete, that));

        return that;
    }

    /**
     * Update the total line.
     */
    _updateTotal() {
        'use strict';
        const item = this.getItem();
        this.$total.text(this._formatValue(item.total));
    }

    /**
     * Handles the delete click event.
     */
    _onDelete() {
        'use strict';
        this.hide();
        if (this.$editingRow) {
            const button = this.$editingRow.findExists('.btn-delete-item');
            if (button) {
                this.application.removeItem(button);
            }
        }
    }

    /**
     * Handles the dialog show event.
     */
    _onDialogShow() {
        'use strict';
        const key = this.$editingRow ? 'edit' : 'add';
        const title = this.$form.data(key);
        this.$modal.find('.dialog-title').text(title);
        if (this.$editingRow) {
            this.$searchRow.hide();
            this.$deleteButton.show();
        } else {
            this.$searchRow.show();
            this.$deleteButton.hide();
        }
    }

    /**
     * Handles the dialog visible event.
     */
    _onDialogVisible() {
        'use strict';
        if (this.$price.attr('readonly')) {
            this.$cancelButton.focus();
        } else if (this.$editingRow) {
            this.$editingRow.addClass('table-primary');
            if (this.$price.isEmptyValue()) {
                this.$price.selectFocus();
            } else {
                this.$quantity.selectFocus();
            }
        } else {
            this.$search.selectFocus();
        }
    }

    /**
     * Handles the dialog hide event.
     */
    _onDialogHide() {
        'use strict';
        $('tr.table-primary').removeClass('table-primary');
    }

    /**
     * Format a value with 2 fixed decimals and grouping separator.
     *
     * @param {Number}
     *            value - the value to format.
     * @returns {string} - the formatted value.
     */
    _formatValue(value) {
        'use strict';
        return this.application.formatValue(value);
    }

    /**
     * Rounds the given value with 2 decimals.
     *
     * @param {Number}
     *            value - the value to roud.
     * @returns {Number} - the rounded value.
     */
    _roundValue(value) {
        'use strict';
        return this.application.roundValue(value);
    }
};
