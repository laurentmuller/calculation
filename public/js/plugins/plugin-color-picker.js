/**
 * Ready function
 */
$(function () {
    'use strict';

    // -----------------------------------
    // ColorPicker public class definition
    // -----------------------------------
    /**
     * @typedef  {Object}  SelectionType
     * @property {number}  col - the column index.
     * @property {number}  row - the row index.
     */
    const ColorPicker = class {

        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, ColorPicker.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            this.$dropdown.off('shown.bs.dropdown', this.dropdownShowProxy);
            this.$dropdown.off('show.bs.dropdown', this.dropdownVisibleProxy);
            this.$dropdown.off('hidden.bs.dropdown', this.dropdownHideProxy);
            this.$label.off('click.color', this.labelClickProxy);
            if (this.$parent) {
                this.$parent.before(this.$parent).remove();
            }
            this.$element.off('input.color', this.elementInputProxy);
            this.$element.css('display', 'block').removeData(ColorPicker.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize the widget.
         * @private
         */
        _init() {
            const $element = this.$element;
            const focused = $element.is(':focus');
            this.length = Object.keys(this.options.colors).length;
            this.cols = this.options.columns;
            this.rows = Math.ceil(this.length / this.cols);
            this.$element[0].removeAttribute('data-colors');

            // drop-down
            this._createDropDown();

            // proxy
            this.elementInputProxy = () => this._onElementInput();

            // add handler
            $element.on('input.color', this.elementInputProxy);
            this._updateUI();

            // focus
            if (focused || this.options.focus) {
                this._setFocus();
            }
        }

        /**
         * Creates the drop-down element, if applicable.
         * @private
         */
        _createDropDown() {
            // already created?
            const $existing = this.$element.siblings('[data-bs-toggle="dropdown"]');
            if ($existing.length) {
                this.$dropdown = $existing;
                this.$spanText = $existing.children('.dropdown-text:first');
                this.$spanColor = $existing.children('.dropdown-color:first');
                this.$dropdownMenu = $existing.siblings('.dropdown-menu:first');
            } else {
                // parent
                this.$parent = $('<div/>', {
                    'class': 'color-picker-parent'
                });
                // button
                this.$dropdown = $('<button/>', {
                    'type': 'button',
                    'role': 'combobox',
                    'aria-expanded': 'false',
                    'data-bs-toggle': 'dropdown',
                    'class': 'color-picker dropdown-toggle form-control d-flex align-items-center'
                }).appendTo(this.$parent);
                // span color
                this.$spanColor = $('<span/>', {
                    'class': 'dropdown-color border'
                }).appendTo(this.$dropdown);
                // span text
                this.$spanText = $('<span/>', {
                    'class': 'dropdown-text flex-fill text-start'
                }).appendTo(this.$dropdown);
                // menu
                this.$dropdownMenu = $('<div/>', {
                    'class': 'color-picker dropdown-menu text-center p-2'
                }).appendTo(this.$parent);
                // hide the element and add the parent
                this.$element.css('display', 'table-column')
                    .after(this.$parent)
                    .prependTo(this.$parent);
            }

            this.$label = this.$dropdownMenu.parents('.form-group').children('.form-label:first');

            // proxies
            this.dropdownShowProxy = () => this._onDropdownShow();
            this.dropdownVisibleProxy = () => this._onDropdownVisible();
            this.dropdownHideProxy = () => this._onDropdownHide();
            this.labelClickProxy = (e) => this._onLabelClick(e);

            // add handlers
            this.$dropdown.on('show.bs.dropdown', this.dropdownShowProxy);
            this.$dropdown.on('shown.bs.dropdown', this.dropdownVisibleProxy);
            this.$dropdown.on('hidden.bs.dropdown', this.dropdownHideProxy);
            this.$label.on('click.color', this.labelClickProxy);
        }

        /**
         * Create the palette element.
         * @private
         */
        _createPalette() {
            // already created?
            if (this.$dropdownMenu.children().length) {
                return;
            }

            // options
            const options = this.options;
            const colors = options.colors;
            const columns = options.columns;
            const titleText = options.titleText;

            // default buttons options
            let buttonOptions = {
                'type': 'button',
                'class': 'btn btn-color border'
            };

            // colors
            const that = this;
            Object.keys(colors).forEach(function (name, index) {
                const color = colors[name];
                buttonOptions.css = {
                    'background-color': color
                };
                buttonOptions['data-index'] = index;
                buttonOptions['data-value'] = color;
                buttonOptions.title = titleText.replace('{name}', name).replace('{color}', color);
                $('<button/>', buttonOptions)
                    .appendTo(that.$dropdownMenu);

                // separator
                if ((index + 1) % columns === 0) {
                    $('<br/>').appendTo(that.$dropdownMenu);
                }
            });

            // custom button
            this.$customButton = $('<button/>', {
                'type': 'button',
                'text': options.advancedText,
                'class': 'btn btn-sm btn-outline-secondary mt-1 w-100'
            }).appendTo(this.$dropdownMenu);

            // proxies
            this.customButtonClickProxy = (e) => this._onCustomButtonClick(e);
            this.colorButtonClickProxy = (e) => this._onColorButtonClick(e);

            // add handlers
            this.$customButton.on('click.color', this.customButtonClickProxy);
            this.$dropdownMenu.on('click.color', '.btn-color', this.colorButtonClickProxy);
        }

        // -----------------------------
        // Handlers functions
        // -----------------------------

        /**
         * Handles the color input event.
         * @private
         */
        _onElementInput() {
            this._updateUI();
            this._setFocus();
        }

        /**
         * Handles the dropdown before show event.
         * @private
         */
        _onDropdownShow() {
            this._createPalette();
        }

        /**
         * Handles the dropdown after show event.
         * @private
         */
        _onDropdownVisible() {
            const value = (this.$element.val() || '').toUpperCase();
            /** @var {jQuery<HTMLElement>|any} $button */
            const $button = this._findButton(`.btn-color[data-value="${value}"]`);
            if ($button) {
                $button.trigger('focus');
            } else {
                this._setSelection({col: 0, row: 0});
            }

            // proxy
            if (!this.colorButtonKeyUpProxy) {
                this.colorButtonKeyUpProxy = (e) => this._onColorButtonKeyUp(e);
            }
            this.$dropdownMenu.on('keyup.color', 'button.btn-color', this.colorButtonKeyUpProxy);
        }

        /**
         * Handles the dropdown hidden event.
         * @private
         */
        _onDropdownHide() {
            this.$dropdownMenu.off('keyup.color', 'button.btn-color', this.colorButtonKeyUpProxy);
        }

        /**
         * Handles the custom button click event.
         *
         * @param {MouseEvent} e - the event.
         * @private
         */
        _onCustomButtonClick(e) {
            e.preventDefault();
            this.$element.trigger('click');
            this._setFocus();
        }

        /**
         * Handles the color button click event.
         *
         * @param {MouseEvent} e - the event.
         * @private
         */
        _onColorButtonClick(e) {
            e.preventDefault();
            const $button = $(e.target);
            /** @type {string} */
            const oldValue = this.$element.val();
            const newValue = $button.data('value');
            if (!newValue.equalsIgnoreCase(oldValue)) {
                this.$element.val(newValue).trigger('input');
            } else {
                this._setFocus();
            }
        }

        /**
         * Handles the color button key up event.
         *
         * @param {KeyboardEvent} e - the event.
         * @private
         */
        _onColorButtonKeyUp(e) {
            const $button = $(e.target);
            const selection = this._getSelection($button);
            switch (e.key) {
                case 'Home':
                    this._selectFirst(e, selection);
                    break;
                case 'End':
                    this._selectLast(e, selection);
                    break;
                case 'ArrowLeft':
                    this._selectLeft(e, selection);
                    break;
                case 'ArrowRight':
                    this._selectRight(e, selection);
                    break;
                case 'ArrowUp':
                    this._selectUp(e, selection);
                    break;
                case 'ArrowDown':
                    this._selectDown(e, selection);
                    break;
                case '+':
                    this._selectNext(e, selection);
                    break;
                case '-':
                    this._selectPrevious(e, selection);
                    break;
            }
        }

        /**
         * Handles the label click event.
         *
         * @param {MouseEvent} e - the event.
         * @private
         */
        _onLabelClick(e) {
            e.stopPropagation();
            e.preventDefault();
            if (this.$dropdown.hasClass('show')) {
                this.$dropdown.dropdown('hide');
            }
            this._setFocus();
        }

        // -----------------------------
        // Utility functions
        // -----------------------------

        /**
         * Sets focus to the dropdown.
         * @private
         */
        _setFocus() {
            this.$dropdown.trigger('focus');
        }

        /**
         * Update the UI.
         * @private
         */
        _updateUI() {
            /** @type {string} */
            const value = this.$element.val();
            this.$spanColor.css('background-color', value);
            this.$spanText.text(this._getColorName(value));
        }

        /**
         * Gets the selected color button.
         *
         * @param {jQuery} $button - the clicked button element.
         * @returns {SelectionType} the row and column index of the selected button.
         * @private
         */
        _getSelection($button) {
            // find button
            /** @type {jQuery|any} */
            const $selection = this._findButton('.btn-color:focus') || $button;
            if ($selection && $selection.length) {
                const index = $selection.data('index');
                const col = index % this.cols;
                const row = Math.trunc(index / this.cols);
                return {
                    col: col,
                    row: row
                };
            }
            // first button
            return {
                col: 0,
                row: 0
            };
        }

        /**
         * Finds color buttons for the given selector.
         *
         * @param {string} selector - the button selector.
         * @returns {jQuery} the button, if found; null otherwise.
         * @private
         */
        _findButton(selector) {
            const $button = this.$dropdownMenu.find(selector);
            return $button.length ? $button : null;
        }

        /**
         * Sets the selected (focus) color button.
         *
         * @param {SelectionType} selection - the selection to set (must contain a 'row' and a 'col' fields).
         * @returns {jQuery} the button, if found; null otherwise.
         * @private
         */
        _setSelection(selection) {
            const index = selection.row * this.cols + selection.col;
            const selector = `.btn-color[data-index="${index}"]`;
            /** @var {jQuery<HTMLElement>|any} $button */
            const $button = this._findButton(selector);
            if ($button) {
                return $button.trigger('focus');
            }
            return null;
        }

        /**
         * Find the name for the given hexadecimal color.
         *
         * @param {?string} color - the hexadecimal color to search for.
         * @returns {string} the color name, if found; the custom text otherwise.
         * @private
         */
        _getColorName(color) {
            color = color || '';
            const colors = this.options.colors;
            /* @type {string[]} */
            const values = Object.values(colors);
            const index = values.findIndex((value) => value.equalsIgnoreCase(color));
            if (index !== -1) {
                return Object.keys(colors)[index];
            }
            // custom text
            return this.options.customText;
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectFirst(e, selection) {
            selection.col = 0;
            if (e.ctrlKey) {
                selection.row = 0;
            }
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectLast(e, selection) {
            const cols = this.cols;
            const length = this.length;
            selection.col = cols - 1;
            if (e.ctrlKey || selection.row * cols + selection.col >= length) {
                const index = length - 1;
                selection.row = Math.trunc(index / cols);
                selection.col = index % cols;
            }
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectLeft(e, selection) {
            const cols = this.cols;
            const length = this.length;
            selection.col = selection.col > 0 ? selection.col - 1 : cols - 1;
            if (selection.row * cols + selection.col >= length) {
                const index = length - 1;
                selection.row = Math.trunc(index / cols);
                selection.col = index % this.cols;
            }
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectRight(e, selection) {
            const cols = this.cols;
            const length = this.length;
            selection.col = selection.col < cols - 1 ? selection.col + 1 : 0;
            if (selection.row * cols + selection.col >= length) {
                selection.col = 0;
            }
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectUp(e, selection) {
            const cols = this.cols;
            const rows = this.rows;
            const length = this.length;
            selection.row = selection.row > 0 ? selection.row - 1 : rows - 1;
            if (selection.row * cols + selection.col >= length) {
                selection.row = rows - 2;
            }
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectDown(e, selection) {
            const cols = this.cols;
            const rows = this.rows;
            const length = this.length;
            selection.row = selection.row < rows - 1 ? selection.row + 1 : 0;
            if (selection.row * cols + selection.col >= length) {
                selection.row = 0;
            }
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectNext(e, selection) {
            const cols = this.cols;
            const length = this.length;
            let index = selection.row * cols + selection.col;
            if (index < length - 1) {
                index++;
                selection.row = Math.trunc(index / cols);
                selection.col = index % cols;
            } else {
                selection.col = selection.row = 0;
            }
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * @param {KeyboardEvent} e
         * @param {SelectionType} selection
         * @private
         */
        _selectPrevious(e, selection) {
            const cols = this.cols;
            const length = this.length;
            let index = selection.row * cols + selection.col;
            if (index > 0) {
                index--;
            } else {
                index = length - 1;
            }
            selection.row = Math.trunc(index / cols);
            selection.col = index % cols;
            this._setSelection(selection);
            e.preventDefault();
        }

    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    ColorPicker.DEFAULTS = {
        // set focus to the control
        focus: false,
        // the number of columns
        columns: 8,
        // the custom text
        customText: 'Custom',
        // the advanced button's text
        advancedText: 'Advanced...',
        // the title text format
        titleText: '{name} ({color})',
        // the colors to display
        colors: {}
    };

    /**
     * The plugin name.
     */
    ColorPicker.NAME = 'color-picker';

    // -----------------------------------
    // ColorPicker plugin definition
    // -----------------------------------
    const oldColorPicker = $.fn.colorpicker;
    $.fn.colorpicker = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(ColorPicker.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(ColorPicker.NAME, new ColorPicker(this, settings));
            }
        });
    };
    $.fn.colorpicker.Constructor = ColorPicker;

    // -----------------------------------
    // ColorPicker no conflict
    // -----------------------------------
    $.fn.colorpicker.noConflict = function () {
        $.fn.colorpicker = oldColorPicker;
        return this;
    };

});
