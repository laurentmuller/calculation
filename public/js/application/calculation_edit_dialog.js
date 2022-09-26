/**! compression tag for ftp-deployment */

/**
 * Abstract edit dialog handler.
 *
 * @property {JQuery} $form
 * @property {JQuery} $modal
 * @property {JQuery} $category
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
        this._init();
    }

    /**
     * Display the add item dialog.
     *
     * @param {JQuery} $row - the selected row.
     * @return {EditDialog} This instance for chaining.
     */
    showAdd($row) {
        'use strict';

        // initialize
        this.$editingRow = null;
        this.$form.resetValidator();
        this._initAdd($row);

        // show
        this.$modal.modal('show');

        return this;
    }

    /**
     * Display the edit dialog.
     *
     * @param {JQuery} $row - the selected row.
     * @return {EditDialog} This instance for chaining.
     */
    showEdit($row) {
        'use strict';

        // initialize
        this.$editingRow = $row;
        this.$form.resetValidator();
        this._initEdit($row);

        // show
        this.$modal.modal('show');

        return this;
    }

    /**
     * Hide the dialog.
     *
     * @return {EditDialog} This instance for chaining.
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
        const id = $.parseInt($selection.data('groupId'));
        const code = $selection.data('groupCode');
        return {
            id: id,
            code: code
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
        const id = this.$category.intVal();
        const code = $selection.text();
        return {
            id: id,
            code: code
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
     * @return {EditDialog} This instance for chaining.
     */
    _init() {
        'use strict';

        return this;
    }

    /**
     * Initialize the modal dialog.
     *
     * @param {JQuery} $modal - the modal dialog.
     *
     * @return {EditDialog} This instance for chaining.
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
     *
     * @param {JQuery} _$row - the selected row.
     *
     * @return {EditDialog} This instance for chaining.
     */
    _initAdd(_$row) { // jshint ignore:line
        'use strict';
        return this;
    }

    /**
     * Initialize this dialog when editing an item.
     *
     * @param {JQuery} _$row - the selected row.
     *
     * @return {EditDialog} This instance for chaining.
     */
    _initEdit(_$row) { // jshint ignore:line
        'use strict';
        return this;
    }

    /**
     * Handles the dialog show event.
     *
     * @return {EditDialog} This instance for chaining.
     */
    _onDialogShow() {
        'use strict';
        const key = this.$editingRow ? 'edit' : 'add';
        const title = this.$form.data(key);
        this.$modal.find('.dialog-title').text(title);
        return this;
    }

    /**
     * Handles the dialog visible event.
     *
     * @return {EditDialog} This instance for chaining.
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
     * @return {EditDialog} This instance for chaining.
     */
    _onDialogHide() {
        'use strict';
        $('tr.table-primary').removeClass('table-primary');
        return this;
    }
}
