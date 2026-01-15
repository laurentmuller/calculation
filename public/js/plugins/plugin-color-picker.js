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
            this.$dropdown.off('shown.bs.dropdown', this._onDropdownVisible);
            this.$dropdown.off('show.bs.dropdown', this._onDropdownShow);
            this.$label.off('click', this._onLabelClick);
            if (this.$parent) {
                this.$parent.before(this.$parent).remove();
            }
            this.$element.off('input', this._onElementInput);
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
            const that = this;
            const $element = that.$element;
            const focused = $element.is(':focus');
            that.length = Object.keys(that.options.colors).length;
            that.cols = that.options.columns;
            that.rows = Math.ceil(that.length / that.cols);

            // drop-down
            that._createDropDown();

            // add handler
            $element.on('input', () => that._onElementInput());
            that._updateUI();

            // focus
            if (focused || that.options.focus) {
                that._setFocus();
            }
        }

        /**
         * Creates the drop-down element, if applicable.
         * @private
         */
        _createDropDown() {
            const that = this;
            const options = that.options;

            // already created?
            const $existing = that.$element.siblings('[data-bs-toggle="dropdown"]');
            if ($existing.length) {
                that.$dropdown = $existing;
                that.$spanText = $existing.children('.dropdown-text:first');
                that.$spanColor = $existing.children('.dropdown-color:first');
                that.$dropdownMenu = $existing.siblings('.dropdown-menu:first');
            } else {
                // parent
                that.$parent = $('<div/>', {
                    'class': 'color-picker-parent'
                });

                // button
                that.$dropdown = $('<button/>', {
                    'type': 'button',
                    'role': 'combobox',
                    'aria-expanded': 'false',
                    'data-bs-toggle': 'dropdown',
                    'class': 'color-picker dropdown-toggle form-control d-flex align-items-center'
                }).appendTo(that.$parent);

                // span color
                that.$spanColor = $('<span/>', {
                    'class': 'dropdown-color border'
                }).appendTo(that.$dropdown);

                // span text
                if (options.displayText) {
                    that.$spanText = $('<span/>', {
                        'class': 'dropdown-text flex-fill text-start'
                    }).appendTo(that.$dropdown);
                }

                // menu
                that.$dropdownMenu = $('<div/>', {
                    'class': 'color-picker dropdown-menu text-center p-2'
                }).appendTo(that.$parent);

                // hide the element and add the parent
                that.$element.css('display', 'table-column').after(that.$parent).prependTo(that.$parent);
            }

            that.$label = that.$dropdownMenu.parents('.form-group').children('.form-label:first');

            // add handlers
            that.$dropdown.on('show.bs.dropdown', () => that._onDropdownShow());
            that.$dropdown.on('shown.bs.dropdown', () => that._onDropdownVisible());
            that.$label.on('click', (e) => that._onLabelClick(e));
        }

        /**
         * Create the palette element.
         * @private
         */
        _createPalette() {
            // already created?
            const that = this;
            if (that.$dropdownMenu.children().length) {
                return;
            }

            // options
            const options = that.options;
            const colors = options.colors;
            const columns = options.columns;

            // default buttons options
            let buttonOptions = {
                'type': 'button',
                'class': 'btn btn-color border'
            };
            if (options.tooltipDisplay) {
                buttonOptions['data-bs-toggle'] = 'tooltip';
                buttonOptions['data-bs-trigger'] = options.tooltipTrigger;
                buttonOptions['data-bs-placement'] = options.tooltipPlacement;
            }

            // colors
            Object.keys(colors).forEach(function (name, index) {
                const color = colors[name];
                buttonOptions.css = {
                    'background-color': color
                };
                buttonOptions['data-index'] = index;
                buttonOptions['data-value'] = color;
                buttonOptions.title = options.tooltipContent.replace('{name}', name).replace('{color}', color);
                $('<button/>', buttonOptions).appendTo(that.$dropdownMenu);

                // separator
                if ((index + 1) % columns === 0) {
                    $('<br/>').appendTo(that.$dropdownMenu);
                }
            });

            // custom button
            that.$customButton = $('<button/>', {
                'type': 'button',
                'text': options.advancedText,
                'class': 'btn btn-sm btn-outline-secondary mt-1 w-100'
            }).appendTo(that.$dropdownMenu);

            // tooltip
            if (options.tooltipDisplay) {
                that._findButton('.btn-color').tooltip();
            }

            // add handlers
            that.$customButton.on('click', (e) => that._onCustomButtonClick(e));
            that.$dropdownMenu.on('click', '.btn-color', (e) => that._onColorButtonClick(e));
            that.$dropdownMenu.on('keyup', '.btn-color', (e) => that._onColorButtonKeyUp(e));
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
            if (this.$spanText) {
                const text = this._getColorName(value);
                this.$spanText.text(text);
            }
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
            /** @type {Window.jQuery|any} */
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
         * @returns {jQuery} the buttons, if found; null otherwise.
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
         * @param {string} color - the hexadecimal color to search for.
         * @returns {string} the color name, if found; the custom text otherwise.
         * @private
         */
        _getColorName(color) {
            color = color || '';
            const colors = this.options.colors;
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
        // false to hide text
        displayText: true,
        // the custom color text
        customText: 'Custom',
        // the advanced button's text
        advancedText: 'Advanced...',
        // the tooltip displayed
        tooltipDisplay: true,
        // the tooltip placement
        tooltipPlacement: 'top',
        // the tooltip text format
        tooltipContent: '{name} ({color})',
        // the tooltip trigger event
        tooltipTrigger: 'hover',
        // the displayed colors
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
