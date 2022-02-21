    /**! compression tag for ftp-deployment */

    /**
     * Abstract edit dialog handler.
     */
    class EditDialog {

        /**
         * Constructor.
         *
         * @param {Application}
         *            application - the parent application.
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
         * @param {jQuery}
         *            $row - the selected row.
         * @return {EditDialog} This instance for chaining.
         */
        showAdd($row) {
            'use strict';

            // initialize
            this.application.initDragDialog();
            this.$editingRow = null;

            // reset
            this.$form.resetValidator();

            // initialize
            this._initAdd($row);

            // show
            this.$modal.modal('show');

            return this;
        }

        /**
         * Display the edit dialog.
         *
         * @param {jQuery}
         *            $row - the selected row.
         * @return {EditDialog} This instance for chaining.
         */
        showEdit($row) {
            'use strict';

            // initialize
            this.application.initDragDialog();
            this.$editingRow = $row;

            // reset
            this.$form.resetValidator();

            // initialize
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
            const id = Number.parseInt($selection.data('groupId'), 10);
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
         * @param {jQuery}
         *            $modal - the modal dialog.
         *
         * @return {EditDialog} This instance for chaining.
         */
        _initDialog($modal) {
            // handle dialog events
            const that = this;
            $modal.on('show.bs.modal', $.proxy(that._onDialogShow, that));
            $modal.on('shown.bs.modal', $.proxy(that._onDialogVisible, that));
            $modal.on('hide.bs.modal', $.proxy(that._onDialogHide, that));
            return that;
        }

        /**
         * Initialize the dialog add.
         *
         * @param {jQuery}
         *            _$row - the selected row.
         *
         * @return {EditDialog} This instance for chaining.
         */
        _initAdd(_$row) { // jshint ignore:line
            'use strict';
            return this;
        }

        /**
         * Initialize the dialog edit.
         *
         * @param {jQuery}
         *            _$row - the selected row.
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

        /**
         * Parse the given value as float.
         *
         * @param {string}
         *            value - the value to parse.
         * @returns {number} the parsed value.
         */
        _parseFloat(value) {
            'use strict';
            return this.application.roundValue(value);
        }
    }
