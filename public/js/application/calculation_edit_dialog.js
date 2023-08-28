/**! compression tag for ftp-deployment */

/**
 * Abstract edit dialog class.
 *
 * @property {jQuery} $form
 * @property {jQuery} $modal
 * @property {jQuery} $category
 * @property {Application} application
 */
class EditDialog {

    /**
     * Constructor.
     *
     * @param {Application} application - the parent application.
     */
    constructor(application) {
        'use strict';
        if (this.constructor === EditDialog) {
            throw new TypeError('Abstract class "EditDialog" cannot be instantiated directly.');
        }
        this.application = application;
    }

    /**
     * Display the add item dialog.
     *
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     */
    showAdd($row) {
        'use strict';
        // loaded?
        if (!this._isDialogLoaded()) {
            this._loadDialog('showAdd', $row);
            return this;
        }

        // initialize
        this.$editingRow = null;
        this._resetValidator();
        this._initAdd($row);

        // show
        this.$modal.modal('show');

        return this;
    }

    /**
     * Display the edit dialog.
     *
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     */
    showEdit($row) {
        'use strict';
        // loaded?
        if (!this._isDialogLoaded()) {
            this._loadDialog('showEdit', $row);
            return this;
        }

        // initialize
        this.$editingRow = $row;
        this._resetValidator();
        this._initEdit($row);

        // show
        this.$modal.modal('show');

        return this;
    }

    /**
     * Hide the dialog.
     *
     * @return {this} This instance for chaining.
     */
    hide() {
        'use strict';
        this.$modal.modal('hide');
        return this;
    }

    /**
     * Gets the selected group.
     *
     * @returns {{id: number, code: string}}  the group.
     */
    getGroup() {
        'use strict';
        const $selection = this.$category.getSelectedOption();
        const id = $.parseInt($selection.data('groupId'));
        const code = $selection.data('groupCode');
        /* eslint no-lone-blocks: "off" */
        return {
            id: id,
            code: code
        };
    }

    /**
     * Gets the selected category.
     *
     * @returns {{id: number, code: string}} the category.
     */
    getCategory() {
        'use strict';
        const $selection = this.$category.getSelectedOption();
        const id = this.$category.intVal();
        const code = $selection.text();
        /* eslint no-lone-blocks: "off" */
        return {
            id: id,
            code: code
        };
    }

    /**
     * Gets the editing row.
     *
     * @return {jQuery} the row or null if none.
     */
    getEditingRow() {
        'use strict';
        return this.$editingRow;
    }

    /**
     * Initialize.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _init() {
        'use strict';
        // handle dialog events
        this._initDialog(this.$modal);
        return this;
    }

    /**
     * Initialize the modal dialog.
     *
     * @param {jQuery} $modal - the modal dialog.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _initDialog($modal) {
        'use strict';
        const that = this;
        $modal.on('show.bs.modal', function () {
            that._onDialogShow();
        }).on('shown.bs.modal', function () {
            that._onDialogVisible();
        }).on('hide.bs.modal', function () {
            that._onDialogHide();
        }).draggableModal({
            marginBottom: $('footer:visible').length ? $('footer').outerHeight() : 0,
            focusOnShow: true
        });

        return that;
    }

    /**
     * Initialize this dialog when adding a new item.
     *
     * @param {jQuery} _$row - the selected row.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _initAdd(_$row) {
        'use strict';
        return this;
    }

    /**
     * Initialize this dialog when editing an item.
     *
     * @param {jQuery} _$row - the selected row.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _initEdit(_$row) {
        'use strict';
        return this;
    }

    /**
     * Handles the dialog show event.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _onDialogShow() {
        'use strict';
        const key = this.$editingRow ? 'edit' : 'add';
        const title = this.$form.data(key);
        this.$modal.find('.dialog-title').text(String(title));
        return this;
    }

    /**
     * Handles the dialog visible event.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _onDialogVisible() {
        'use strict';
        if (this.$editingRow) {
            this.$editingRow.addClass('table-primary');
        }
        return this;
    }

    /**
     * Handles the dialog hide event.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _onDialogHide() {
        'use strict';
        $('tr.table-primary').removeClass('table-primary');
        return this;
    }

    /**
     * Reset the form validator.
     *
     * @return {this} This instance for chaining.
     * @protected
     */
    _resetValidator() {
        'use strict';
        this.$form.resetValidator();
        return this;
    }

    /**
     * Load the modal dialog.
     *
     * @param {string} callback - the function name to call after dialog is loaded.
     * @param {jQuery} $row - the editing row.
     * @protected
     */
    _loadDialog(callback, $row) {
        'use strict';
        const url = this._getDialogUrl();
        if (!url) {
            return;
        }
        const that = this;
        $.get(url, function (data) {
            const $dialog = $(data);
            $dialog.appendTo('.page-content');
            that._init();
            that[callback]($row);
        });
    }

    /**
     * Gets the URL to load dialog content.
     *
     * @return {string} - the URL.
     * @protected
     */
    _getDialogUrl() {
        'use strict';
        throw new Error("Method must be implemented by derived class.");
    }

    /**
     * Returns if the dialog is loaded.
     *
     * @return {boolean} true if loaded; false otherwise.
     * @protected
     */
    _isDialogLoaded() {
        'use strict';
        throw new Error("Method must be implemented by derived class.");
    }

    /**
     * Initialize the type ahead search units.
     *
     * @param {string} selector - the input selector.
     * @protected
     */
    _initSearchUnits(selector) {
        //task_unit
        'use strict';
        const $form = $('#edit-form');
        $(selector).initTypeahead({
            url: $form.data('search-unit'),
            error: $form.data('error-unit')
        });
    }
}
