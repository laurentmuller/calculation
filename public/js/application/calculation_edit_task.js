/**! compression tag for ftp-deployment */

/* globals EditDialog, Toaster */

/**
 * Edit task dialog handler.
 *
 * @class EditTaskDialog
 * @extends {EditDialog}
 */
class EditTaskDialog extends EditDialog { // jshint ignore:line

    /**
     * Gets the selected items.
     *
     * @return {Object} the items.
     */
    getItems() {
        'use strict';

        const that = this;
        const quantity = that.$quantity.floatVal();
        const task = that.$task.getSelectedOption().text();
        const unit = that.$unit.val();

        return $('#table-task-edit > tbody > tr:not(.d-none) .item-input:checked').map(function () {
            const $row = $(this).parents('.task-item-row');
            const text = $row.find('.custom-control-label').text();
            const price = $.parseFloat($row.find('.task_value').data('value'));
            const total = $.roundValue(price * quantity);
            const description = task + ' - ' + text;
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
     * @return {EditDialog} This instance for chaining.
     */
    _init() {
        'use strict';

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

        // handle dialog events
        that._initDialog(that.$modal);

        // handle input events
        that.taskProxy = function () {
            that._onTaskChanged();
        };
        that.updateProxy = function () {
            that._update();
        };
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
     */
    _abort() {
        'use strict';
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
     * @return {EditTaskDialog} This instance for chaining.
     */
    _send(data) {
        'use strict';
        const that = this;
        const url = that.$form.data('url');
        /**
         * @param {Object} response
         * @param {boolean} response.result
         * @param {string} response.message
         * @param {string} response.unit
         * @param {number} response.categoryId
         * @param {number} response.overall
         */
        that.jqXHR = $.post(url, data, function (response) {
            if (response.result) {
                // update
                response.results.forEach(function (item) {
                    that._updateValue('task_value_' + item.id, item.value);
                    that._updateValue('task_total_' + item.id, item.amount);
                });
                that.$unit.val(response.unit);
                that.$category.val(response.categoryId);
                that._updateValue('task_overall', response.overall);
                that.$submit.toggleDisabled(false);
            } else {
                that.showError(response.message);
            }
        }).fail(function (_jqXHR, textStatus) {
            if (textStatus !== 'abort') {
                that.showError(that.$form.data('failed'));
            }
        });
        return that;
    }

    /**
     * Gets input values and send to the server.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _update() {
        'use strict';

        const that = this;

        // disable
        that.$submit.toggleDisabled(true);

        // valid?
        if (!that.$form.valid()) {
            return that._resetValues();
        }

        // get items
        const items = that._getItems();
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
     * @return {array} - the selected item identifiers.
     */
    _getItems() {
        'use strict';
        return $('#table-task-edit > tbody > tr:not(.d-none) .item-input:checked').map(function () {
            return $.parseInt($(this).attr('value'));
        }).get();
    }

    /**
     * Update a plain-text.
     *
     * @param {string} id - the plain-text identifier.
     * @param {number} value - the value.
     * @return {EditTaskDialog} This instance for chaining.
     */
    _updateValue(id, value) {
        'use strict';
        const $item = $('#' + id);
        $item.data('value', value).text($.formatFloat(value));
        return this;
    }

    /**
     * Update all plain-texts to 0.00.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _resetValues() {
        'use strict';
        const value = $.formatFloat(0);
        this.$form.find('.form-control-plaintext').text(value);
        return this;
    }

    /**
     * Shows the error.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _showError(message) {
        'use strict';
        this.resetValues();
        this.$submit.toggleDisabled(true);
        this.$modal.modal('hide');
        const title = this.$modal.find('.dialog-title').text();
        Toaster.danger(message || this.$form.data('failed'), title, $('#flashbags').data());
        return this;
    }

    /**
     * Handles the dialog visible event.
     */
    _onDialogVisible() {
        'use strict';
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
     * Handle the task input event.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _onTaskChanged() {
        'use strict';

        // toggle rows visibility
        const id = this.$task.intVal();
        const selector = '[task-id="' + id + '"]';
        $('.task-item-row' + selector).removeClass('d-none');
        $('.task-item-row:not(' + selector + ')').addClass('d-none');

        // task items?
        const empty = $('.task-item-row:not(.d-none)').length === 0;
        $('.task-row-table').toggleClass('d-none', empty);
        $('.task-row-empty').toggleClass('d-none', !empty);

        // submit
        if (empty) {
            this.$submit.toggleDisabled(true);
            return this;
        }
        return this._update();
    }
}
