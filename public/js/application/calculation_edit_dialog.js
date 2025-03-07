/* eslint no-unused-vars: ["error", { "varsIgnorePattern": "EditDialog" }] */

/**
 * Abstract edit dialog class.
 * @property {jQuery<HTMLFormElement>} $form
 * @property {jQuery<HTMLDialogElement>} $modal
 * @property {jQuery<HTMLInputElement>} $category
 * @property {Application} application
 */
class EditDialog {

    /**
     * Constructor.
     * @param {Application} application - the parent application.
     */
    constructor(application) {
        if (this.constructor === EditDialog) {
            throw new TypeError('Abstract class "EditDialog" cannot be instantiated directly.');
        }
        this.application = application;
    }

    /**
     * Display the dialog to add item.
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     */
    showAdd($row) {
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
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     */
    showEdit($row) {
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
     * @return {this} This instance for chaining.
     */
    hide() {
        this.$modal.modal('hide');
        return this;
    }

    /**
     * Gets the selected group.
     * @returns {{id: number, code: string}}  the group.
     */
    getGroup() {
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
     * @returns {{id: number, code: string}} the category.
     */
    getCategory() {
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
     * @return {jQuery} the row or null if none.
     */
    getEditingRow() {
        return this.$editingRow;
    }

    /**
     * Initialize.
     * @return {this} This instance for chaining.
     * @protected
     */
    _init() {
        // handle dialog events
        this._initDialog(this.$modal);
        return this;
    }

    /**
     * Initialize the modal dialog.
     * @param {jQuery} $modal - the modal dialog.
     * @return {this} This instance for chaining.
     * @protected
     */
    _initDialog($modal) {
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
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     * @protected
     */

    /*eslint no-unused-vars: ["error", { "args": "none" }]*/
    _initAdd($row) {
        return this;
    }

    /**
     * Initialize this dialog when editing an item.
     * @param {jQuery} $row - the selected row.
     * @return {this} This instance for chaining.
     * @protected
     */

    /*eslint no-unused-vars: ["error", { "args": "none" }]*/
    _initEdit($row) {
        return this;
    }

    /**
     * Handles the dialog show event.
     * @return {this} This instance for chaining.
     * @protected
     */
    _onDialogShow() {
        const title = this._getDialogTitle();
        this.$modal.find('.dialog-title').text(title);
        return this;
    }

    /**
     * @return {string}
     * @protected
     */
    _getDialogTitle() {
        const key = this.$editingRow ? 'edit' : 'add';
        return this.$form.data(key);
    }

    /**
     * Handles the dialog visible event.
     * @return {this} This instance for chaining.
     * @protected
     */
    _onDialogVisible() {
        if (this.$editingRow) {
            this.$editingRow.addClass('table-primary');
        }
        return this;
    }

    /**
     * Handles the dialog hide event.
     * @return {this} This instance for chaining.
     * @protected
     */
    _onDialogHide() {
        $('tr.table-primary').removeClass('table-primary');
        return this;
    }

    /**
     * Reset the form validator.
     * @return {this} This instance for chaining.
     * @protected
     */
    _resetValidator() {
        this.$form.resetValidator();
        return this;
    }

    /**
     * Load the modal dialog.
     * @param {string} callback - the function name to call after the dialog is loaded.
     * @param {jQuery} $row - the editing row.
     * @protected
     */
    _loadDialog(callback, $row) {
        const url = this._getDialogUrl();
        if (!url) {
            return;
        }
        const that = this;
        $.getJSON(url, function (data) {
            const $dialog = $(data);
            $dialog.appendTo('.page-content');
            that._init();
            that[callback]($row);
        });
    }

    /**
     * Gets the URL to load dialog content.
     * @return {string} - the URL.
     * @protected
     */
    _getDialogUrl() {
        throw new Error('Method must be implemented by derived class.');
    }

    /**
     * Returns if the dialog is loaded.
     * @return {boolean} true if loaded; false otherwise.
     * @protected
     */
    _isDialogLoaded() {
        throw new Error('Method must be implemented by derived class.');
    }

    /**
     * Initialize the type ahead search units.
     * @param {jQuery} $input - the input.
     * @protected
     */
    _initSearchUnits($input) {
        const $editForm = $('#edit-form');
        $input.initTypeahead({
            url: $editForm.data('search-unit'),
            error: $editForm.data('error-unit')
        });
    }
}
