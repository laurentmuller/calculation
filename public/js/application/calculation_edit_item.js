/* globals EditDialog */

/**
 * Edit item dialog class.
 * @class EditItemDialog
 * @extends {EditDialog}
 */
class EditItemDialog extends EditDialog {

    /**
     * Gets the selected item.
     * @returns {{description: string, unit: string, price: number, quantity: number, total: number}} the item.
     */
    getItem() {
        const price = this.$price.floatVal();
        const quantity = this.$quantity.floatVal();
        const total = $.roundValue(price * quantity);
        const description = this.$description.val();
        const unit = this.$unit.val();
        /* eslint no-lone-blocks: "off" */
        return {
            description: description,
            unit: unit,
            price: price,
            quantity: quantity,
            total: total
        };
    }

    /**
     * Display the copy dialog.
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     */
    showCopy($row) {
        // loaded?
        if (!this._isDialogLoaded()) {
            this._loadDialog('showCopy', $row);
            return this;
        }

        // initialize
        this.$editingRow = $row;
        this._resetValidator();
        this._initEdit($row);
        this.$description.val(this.$description.val() + ' - Copie');
        this.copy = true;

        // show
        this.$modal.modal('show');

        return this;
    }

    /**
     * Initialize the dialog when adding a new item.
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     * @private
     */
    _initAdd($row) {
        // update values
        if ($row) {
            const $input = $row.siblings(':first').findNamedInput('category');
            if ($input) {
                this.$category.val($input.val());
            }
        }
        // set values
        this.copy = false;
        this.$price.floatVal(1);
        this.$quantity.floatVal(1);
        this.$total.text($.formatFloat(1));

        return super._initAdd($row);
    }

    /**
     * Initialize the dialog edit.
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     * @private
     */
    _initEdit($row) {
        // copy values
        this.copy = false;
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
     * @return {this} This instance for chaining.
     * @protected
     */
    _init() {
        // get elements
        const that = this;
        that.copy = false;
        that.$form = $('#item_form');
        that.$modal = $('#item_modal');
        that.$description = $('#item_description');
        that.$unit = $('#item_unit');
        that.$category = $('#item_category');
        that.$price = $('#item_price').inputNumberFormat();
        that.$quantity = $('#item_quantity').inputNumberFormat();
        that.$total = $('#item_total');
        that.$searchRow = $('#item_search_row');
        that.$deleteButton = $('#item_delete_button');
        that.$cancelButton = $('#item_cancel_button');
        that.$search = $('#item_search_input');
        that.$clearButton = $('#item_search_clear');

        // handle type ahead search
        that._initSearchProduct();
        that._initSearchUnits(that.$unit);

        // handle input events
        that.$price.on('input', () => that._updateTotal());
        that.$quantity.on('input', () => that._updateTotal());

        // handle delete button
        that.$deleteButton.on('click', () => that._onDelete());

        // init validator
        const options = {
            showModification: false,
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
     * @private
     */
    _updateTotal() {
        const item = this.getItem();
        this.$total.text($.formatFloat(item.total));
    }

    /**
     * Handles to delete click event.
     * @private
     */
    _onDelete() {
        this.hide();
        if (this.$editingRow) {
            const $button = this.$editingRow.findExists('.btn-delete-item');
            if ($button) {
                this.application.removeItem($button);
            }
        }
    }

    /**
     * Handles the dialog show event.
     * @return {this} This instance for chaining.
     * @private
     */
    _onDialogShow() {
        if (this.$editingRow) {
            this.$searchRow.hide();
            if (this.copy) {
                this.$editingRow = null;
                this.$deleteButton.hide();
            } else {
                this.$deleteButton.show();
            }
        } else {
            this.$searchRow.show();
            this.$deleteButton.hide();
        }
        return super._onDialogShow();
    }

    /**
     * Handles the dialog visible event.
     * @return {this} This instance for chaining.
     * @private
     */
    _onDialogVisible() {
        if (this.$price.attr('readonly')) {
            this.$cancelButton.trigger('focus');
        } else if (this.copy) {
            this.$description.selectFocus();
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

    /**
     * Returns if the dialog is loaded.
     * @return {boolean} true if loaded; false otherwise.
     * @protected
     */
    _isDialogLoaded() {
        return $('#item_modal').length !== 0;
    }

    /**
     * Gets the URL to load dialog content.
     * @return {string} - the URL.
     * @protected
     */
    _getDialogUrl() {
        return this.application.getItemDialogUrl();
    }

    /**
     * @return {string}
     * @private
     */
    _getDialogTitle() {
        if (this.copy) {
            return this.$form.data('copy');
        }
        return super._getDialogTitle();
    }

    /**
     * Initialize the type ahead search products.
     * @private
     */
    _initSearchProduct() {
        const $form = $('#edit-form');
        const that = this;
        that.$search.initTypeahead({
            copyText: false,
            alignWidth: false,
            valueField: 'description',
            displayField: 'description',
            url: $form.data('search-product'),
            error: $form.data('error-product'),
            empty: $form.data('item-empty'),

            /**
             * @param {Object} item
             * @param {string} item.description
             * @param {string} item.unit
             * @param {int} item.categoryId
             * @param {number} item.price
             */
            onSelect: function (item) {
                // copy values
                that.$description.val(item.description);
                that.$unit.val(item.unit);
                that.$category.val(item.categoryId);
                that.$price.floatVal(item.price);
                that.$price.trigger('input');
                that.$form.validateForm();

                // select
                if (item.price) {
                    that.$quantity.selectFocus();
                } else {
                    that.$price.selectFocus();
                }
            }
        });

        that.$search.on('input', () => {
            if ('' === that.$search.val()) {
                that.$clearButton.attr('disabled', 'disabled');
            } else {
                that.$clearButton.attr('disabled', null);
            }
        });
        that.$clearButton.on('click', () => {
            that.$search.val('').trigger('focus');
            that.$clearButton.attr('disabled', 'disabled');
        });
    }
}
