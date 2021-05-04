/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Edit task dialog handler.
 */
var EditTaskDialog = function (application) {
    'use strict';
    this.application = application;
    this._init();
};

EditTaskDialog.prototype = {

    /**
     * Constructor.
     */
    constructor: EditTaskDialog,

    /**
     * Display the add task dialog.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    showAdd: function () {
        'use strict';
        // initialize
        this.application.initDragDialog();
        this.$editingRow = null;

        // reset
        this.$form.resetValidator();

        // show
        this.$modal.modal('show');

        return this;
    },

    /**
     * Hide the dialog.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    hide: function () {
        'use strict';
        this.$modal.modal('hide');
        return this;
    },

    /**
     * Gets the selected group.
     *
     * @returns {Object} the group.
     */
    getGroup: function () {
        'use strict';
        const $selection = this.$category.getSelectedOption();
        return {
            id: parseInt($selection.data('groupId'), 10),
            code: $selection.data('groupCode')
        };
    },

    /**
     * Gets the selected category.
     *
     * @returns {Object} the category.
     */
    getCategory: function () {
        'use strict';
        const $selection = this.$category.getSelectedOption();
        return {
            id: this.$category.intVal(),
            code: $selection.text()
        };
    },

    /**
     * Gets the selected items.
     *
     * @return {Object} the items.
     */
    getItems: function () {
        'use strict';

        const quantity = this.$quantity.floatVal();
        const task = this.$task.getSelectedOption().text();
        const unit = this.$unit.val();

        return $('#table-task-edit > tbody > tr:not(.d-none) .item-input:checked').map(function () {
            const $row = $(this).parents('.task-item-row');
            const text = $row.find('.custom-control-label').text();
            const price = parseFloat($row.find('.task_value').text());
            const total = Math.round(price * quantity * 100 + Number.EPSILON) / 100;
            return {
                description: task + ' - ' + text,
                unit: unit,
                price: price,
                quantity: quantity,
                total: total
            };
        }).get();
    },

    /**
     * Initialize.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _init: function () {
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

        // handld dialog events
        that.$modal.on('show.bs.modal', $.proxy(that._onDialogShow, that));
        that.$modal.on('shown.bs.modal', $.proxy(that._onDialogVisible, that));
        that.$modal.on('hide.bs.modal', $.proxy(that._onDialogHide, that));

        // handld input events
        const taskProxy = $.proxy(that._onTaskChanged, that);
        that.$task.on('input', function () {
            $(this).updateTimer(taskProxy, 250);
        });
        const updateProxy = $.proxy(that._update, that);
        that.$quantity.on('input', function () {
            $(this).updateTimer(updateProxy, 250);
        });
        $('.item-input').on('change', function () {
            $(this).updateTimer(updateProxy, 250);
        });

        // validator
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
                    greaterThanValue: 0
                }
            }
        };
        that.$form.initValidator(options);
        that._update();

        return that;
    },

    /**
     * Abort the ajax call.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _abort: function () {
        'use strict';
        if (this.jqXHR) {
            this.jqXHR.abort();
            this.jqXHR = null;
        }
        return this;
    },

    /**
     * Send data to server and update UI.
     *
     * @param {Object}
     *            data - the data to send.
     * @return {EditTaskDialog} This instance for chaining.
     */
    _send: function (data) {
        'use strict';
        const that = this;
        const url = that.$form.data('url');
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
        }).fail(function (jqXHR, textStatus) {
            if (textStatus !== 'abort') {
                that.showError(that.$form.data('failed'));
            }
        });
        return that;
    },

    /**
     * Gets UI values and send to the server.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _update: function () {
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
            'quantity': that.$quantity.floatVal().toFixed(2),
            'items': items
        };

        // cancel and send
        return that._abort()._send(data);
    },

    /**
     * Format a value with 2 fixed decimals and grouping separator.
     *
     * @param {Number}
     *            value - the value to format.
     * @returns {string} - the formatted value.
     */
    _formatValue: function (value) {
        'use strict';
        return this.application.formatValue(value);
    },

    /**
     * Gets selected items.
     *
     * @return {array} - the selected item identifiers.
     */
    _getItems: function () {
        'use strict';
        return $('#table-task-edit > tbody > tr:not(.d-none) .item-input:checked').map(function () {
            return Number.parseInt($(this).attr('value'), 10);
        }).get();
    },

    /**
     * Gets the selected category identifier.
     *
     * @return {int} the category identifier.
     */
    _getCategory: function () {
        'use strict';
        const $selection = this.$task.getSelectedOption();
        return $selection.data('categoryId');
    },

    /**
     * Gets the selected unit.
     *
     * @return {string} the unit.
     */
    _getUnit: function () {
        'use strict';
        const $selection = this.$task.getSelectedOption();
        return $selection.data('unit');
    },

    /**
     * Update a plain-text.
     *
     * @param {string}
     *            id - the plain-text identifier.
     * @param {number}
     *            value - the value to format.
     * @return {EditTaskDialog} This instance for chaining.
     */
    _updateValue: function (id, value) {
        'use strict';
        $('#' + id).text(this._formatValue(value));
        return this;
    },

    /**
     * Update all plain-texts to 0.00.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _resetValues: function () {
        'use strict';
        const value = this._formatValue(0);
        this.$form.find('.form-control-plaintext').text(value);
        return this;
    },

    /**
     * Shows the error.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _showError: function (message) {
        'use strict';
        this.resetValues();
        this.$submit.toggleDisabled(true);
        this.$modal.modal('hide');
        const title = this.$modal.find('.dialog-title').text();
        Toaster.danger(message || this.$form.data('failed'), title, $('#flashbags').data());
        return this;
    },

    /**
     * Handles the dialog show event.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _onDialogShow: function () {
        'use strict';
        const key = this.$editingRow ? 'edit' : 'add';
        const title = this.$form.data(key);
        this.$modal.find('.dialog-title').text(title);
        return this;
    },

    /**
     * Handles the dialog visible event.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _onDialogVisible: function () {
        'use strict';
        if (this.$editingRow) {
            this.$editingRow.addClass('table-primary');
            if (this.$quantity.isEmptyValue()) {
                this.$quantity.selectFocus();
            } else {
                this.$task.focus();
            }
        } else {
            this.$task.focus();
        }
        return this;
    },

    /**
     * Handles the dialog hide event.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _onDialogHide: function () {
        'use strict';
        // this.$editingRow = null;
        $('tr.table-primary').removeClass('table-primary');
        return this;
    },

    /**
     * Handle the task input event.
     *
     * @return {EditTaskDialog} This instance for chaining.
     */
    _onTaskChanged: function () {
        'use strict';

        // toogle rows visibility
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
};
