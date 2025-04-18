/* globals EditDialog, Toaster */

/**
 * Edit task dialog class.
 *
 * @class EditTaskDialog
 * @extends {EditDialog}
 */
class EditTaskDialog extends EditDialog {

    /**
     * Gets the selected items.
     *
     * @return {Array.<{description: string, unit: string, price: number, quantity: number, total: number}>} the items.
     */
    getItems() {
        const that = this;
        /** @type {string} */
        const unit = that.$unit.val();
        /** @type {float} */
        const quantity = that.$quantity.floatVal();
        /** @type {string} */
        const task = that.$task.getSelectedOption().text();

        return that._getCheckedItems().map(function () {
            const $row = $(this).parents('.task-item-row');
            const text = $row.find('.form-check-label').text();
            const description = task + ' - ' + text;
            const price = $.parseFloat($row.find('.task_value').data('value'));
            const total = $.roundValue(price * quantity);
            /* eslint no-lone-blocks: "off" */
            return {
                description: description,
                unit: unit,
                price: price,
                quantity: quantity,
                total: total
            };
        }).get();
    }

    /**
     * Initialize.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _init() {
        // get elements
        const that = this;
        that.$form = $('#task_form');
        that.$modal = $('#task_modal');
        that.$task = $('#task_task');
        that.$unit = $('#task_unit');
        that.$quantity = $('#task_quantity').inputNumberFormat();
        that.$category = $('#task_category');
        that.$submit = $('#task_submit_button');
        that.$itemsEmpty = $('.task-items-empty');

        // handle type ahead search
        that._initSearchUnits(that.$unit);

        // handle input events
        that.taskProxy = () => that._onTaskChanged();
        that.updateProxy = () => that._update();
        that.$task.on('input', function () {
            $(this).updateTimer(that.taskProxy, 250);
        });
        that.$quantity.on('input', function () {
            $(this).updateTimer(that.updateProxy, 250);
        });
        $('.item-input').on('change', function () {
            $(this).updateTimer(that.updateProxy, 250);
        });

        // init validator
        const options = {
            showModification: false,
            submitHandler: function () {
                if (that.$editingRow) {
                    that.application.onEditTaskDialogSubmit();
                } else {
                    that.application.onAddTaskDialogSubmit();
                }
            },
            rules: {
                'task[quantity]': {
                    greaterThanEqualValue: 0
                }
            }
        };
        that.$form.initValidator(options);

        // update values
        that._update();

        return super._init();
    }

    /**
     * Abort the ajax call.
     *
     * @return {EditTaskDialog} This instance for chaining.
     * @private
     */
    _abort() {
        if (this.jqXHR) {
            this.jqXHR.abort();
            this.jqXHR = null;
        }
        return this;
    }

    /**
     * Send data to server and update UI.
     *
     * @param {Object} data - the data to send.
     *
     * @return {EditTaskDialog} This instance for chaining.
     * @private
     */
    _send(data) {
        const that = this;
        const url = that.$form.data('url');
        /**
         * @param {Object} response
         * @param {boolean} response.result
         * @param {string} response.message
         * @param {number} response.categoryId
         * @param {string} response.unit
         * @param {number} response.overall
         * @param {Array.<{id: Number, value: number, amount: number}>} response.items
         */
        that.jqXHR = $.post(url, data, function (response) {
            if (response.result) {
                // update
                response.items.forEach(function (item) {
                    that._updateValue('task_value_' + item.id, item.value);
                    that._updateValue('task_total_' + item.id, item.amount);
                });
                that.$unit.val(response.unit);
                that.$category.val(response.categoryId);
                that._updateValue('task_overall', response.overall);
                that.$submit.toggleDisabled(false);
            } else {
                that._showError(response.message);
            }
        }).fail(function (_jqXHR, textStatus) {
            if (textStatus !== 'abort') {
                that._showError(that.$form.data('failed'));
            }
        });
        return that;
    }

    /**
     * Gets input values and send to the server.
     *
     * @return {EditTaskDialog} This instance for chaining.
     * @private
     */
    _update() {
        const that = this;
        // disable
        that.$submit.toggleDisabled(true);

        // valid?
        if (!that.$form.valid()) {
            return that._resetValues();
        }

        // get items
        const items = that._getItemValues();
        if (items.length === 0) {
            that.$itemsEmpty.removeClass('d-none');
            return that._resetValues();
        }
        that.$itemsEmpty.addClass('d-none');

        // get data
        const data = {
            'id': that.$task.intVal(),
            'quantity': $.roundValue(that.$quantity.floatVal()),
            'items': items
        };

        // cancel and send
        return that._abort()._send(data);
    }

    /**
     * Gets selected item identifiers.
     *
     * @return {array.<Number>} - the selected item identifiers.
     * @private
     */
    _getItemValues() {
        return this._getCheckedItems().map(function () {
            return $(this).intVal();
        }).get();
    }

    /**
     * Update a plain-text.
     *
     * @param {string} id - the plain-text identifier.
     * @param {number} value - the value.
     *
     * @return {EditTaskDialog} This instance for chaining.
     * @private
     */
    _updateValue(id, value) {
        const $item = $('#' + id);
        $item.data('value', value).text($.formatFloat(value));
        return this;
    }

    /**
     * Update all plain-texts to 0.00.
     *
     * @return {EditTaskDialog} This instance for chaining.
     * @private
     */
    _resetValues() {
        const value = $.formatFloat(0);
        this.$form.find('.form-control-read-only').text(value);
        return this;
    }

    /**
     * Shows the error.
     *
     * @return {EditTaskDialog} This instance for chaining.
     * @private
     */
    _showError(message) {
        this._resetValues();
        this.$submit.toggleDisabled(true);
        this.$modal.modal('hide');
        const title = this.$modal.find('.dialog-title').text();
        Toaster.danger(message || this.$form.data('failed'), title);
        return this;
    }

    /**
     * Handles the dialog visible event.
     *
     * @return {this} This instance for chaining.
     * @private
     */
    _onDialogVisible() {
        if (this.$editingRow) {
            if (this.$quantity.isEmptyValue()) {
                this.$quantity.selectFocus();
            } else {
                this.$task.trigger('focus');
            }
        } else {
            this.$task.trigger('focus');
        }
        return super._onDialogVisible();
    }

    /**
     * Handles the dialog show event.
     *
     * @return {this} This instance for chaining.
     */
    _onDialogShow() {
        // update because the task input is reset.
        if (this.mustReset) {
            this._onTaskChanged();
            this.mustReset = false;
        }
        return super._onDialogShow();
    }

    /**
     * Handle the task input event.
     *
     * @return {this} This instance for chaining.
     * @private
     */
    _onTaskChanged() {
        // toggle rows visibility
        const id = this.$task.intVal();
        $(`.task-item-row:not([data-id="${id}"])`).addClass('d-none');
        const $rows = $(`.task-item-row[data-id="${id}"]`).removeClass('d-none');

        // task items?
        const empty = $rows.length === 0;
        $('.task-row-table').toggleClass('d-none', empty);
        $('.task-row-empty').toggleClass('d-none', !empty);

        // submit
        if (empty) {
            this.$submit.toggleDisabled(true);
            return this;
        }
        return this._update();
    }

    _resetValidator() {
        this.mustReset = this.$task.getSelectedOption().index() !== 0;
        return super._resetValidator();
    }

    /**
     * Gets the checked items.
     *
     * @return {jQuery} the checked items.
     * @private
     */
    _getCheckedItems() {
        const id = this.$task.intVal();
        const selector = `#table-task-edit .task-item-row[data-id="${id}"] .item-input:checked`;
        return $(selector);
    }

    /**
     * Returns if the dialog is loaded.
     *
     * @return {boolean} true if loaded; false otherwise.
     * @protected
     */
    _isDialogLoaded() {
        return $('#task_modal').length !== 0;
    }

    /**
     * Gets the URL to load dialog content.
     *
     * @return {string} - the URL.
     * @protected
     */
    _getDialogUrl() {
        return this.application.getTaskDialogUrl();
    }
}
