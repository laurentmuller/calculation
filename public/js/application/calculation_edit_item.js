/**! compression tag for ftp-deployment */

/* global EditDialog */

/**
 * Edit item dialog handler.
 *
 * @class EditItemDialog
 * @extends {EditDialog}
 */
class EditItemDialog extends EditDialog { // jshint ignore:line

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
        const total = $.roundValue(price * quantity);
        const description = that.$description.val();
        const unit = that.$unit.val();
        return {
            description: description,
            unit: unit,
            price: price,
            quantity: quantity,
            total: total
        };
    }

    /**
     * Initialize the dialog add.
     *
     * @param {JQuery} $row - the selected row.
     *
     * @return {EditDialog} This instance for chaining.
     */
    _initAdd($row) {
        'use strict';

        // update values
        if ($row) {
            const $input = $row.siblings(':first').findNamedInput('category');
            if ($input) {
                this.$category.val($input.val());
            }
        }

        // set values
        this.$price.floatVal(1);
        this.$quantity.floatVal(1);
        this.$total.text($.formatFloat(1));

        return super._initAdd($row);
    }

    /**
     * Initialize the dialog edit.
     *
     * @param {JQuery} $row - the selected row.
     *
     * @return {EditDialog} This instance for chaining.
     */
    _initEdit($row) {
        'use strict';

        // copy values
        this.$description.val($row.findNamedInput('description').val());
        this.$unit.val($row.findNamedInput('unit').val());
        this.$category.val($row.parent().findNamedInput('category').val());
        this.$price.floatVal($row.findNamedInput('price').floatVal());
        this.$quantity.floatVal($row.findNamedInput('quantity').floatVal());
        this.$total.text($.formatFloat($row.findNamedInput('total').floatVal()));

        return super._initEdit($row);
    }

    /**
     * Initialize.
     *
     * @return {EditDialog} This instance for chaining.
     */
    _init() {
        'use strict';

        // get elements
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

        // handle dialog events
        that._initDialog(that.$modal);

        // handle input events
        that.updateProxy = function() {
            that._updateTotal();
        };
        that.$price.on('input', that.updateProxy);
        that.$quantity.on('input', that.updateProxy);

        // handle delete button
        that.deleteProxy = function() {
            that._onDelete();
        };
        that.$deleteButton.on('click', that.deleteProxy);

        // init validator
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

        return super._init();
    }

    /**
     * Update the total line.
     */
    _updateTotal() {
        'use strict';
        const item = this.getItem();
        this.$total.text($.formatFloat(item.total));
    }

    /**
     * Handles to delete click event.
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
     *
     * @return {EditDialog} This instance for chaining.
     */
    _onDialogShow() {
        'use strict';
        if (this.$editingRow) {
            this.$searchRow.hide();
            this.$deleteButton.show();
        } else {
            this.$searchRow.show();
            this.$deleteButton.hide();
        }
        return super._onDialogShow();
    }

    /**
     * Handles the dialog visible event.
     *
     * @return {EditDialog} This instance for chaining.
     */
    _onDialogVisible() {
        'use strict';
        if (this.$price.attr('readonly')) {
            this.$cancelButton.trigger('focus');
        } else if (this.$editingRow) {
            if (this.$price.isEmptyValue()) {
                this.$price.selectFocus();
            } else {
                this.$quantity.selectFocus();
            }
        } else {
            this.$search.selectFocus();
        }
        return super._onDialogVisible();
    }
}
