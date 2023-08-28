/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // -----------------------------------
    // ColorPicker public class definition
    // -----------------------------------
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
            this.$dropdown.off('shown.bs.dropdown', () => this._onDropdownVisible());
            this.$dropdown.off('show.bs.dropdown', () => this._onDropdownShow());
            this.$label.off('click', (e) => this._onLabelClick(e));
            if (this.$parent) {
                this.$parent.before(this.$parent).remove();
            }
            this.$element.off('input', () => this._onElementInput());
            this.$element.css('display', 'block').removeData(ColorPicker.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize widget.
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

                // hide element and add parent
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
         * @param {Event} e - the event.
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
         * @param {Event} e - the event.
         * @private
         */
        _onColorButtonClick(e) {
            e.preventDefault();
            const $button = $(e.target);
            const oldValue = this.$element.val();
            const newValue = $button.data('value');
            if (!newValue.equalsIgnoreCase(oldValue)) {
                this.$element.val(newValue).trigger('input');
            } else {
                this._setFocus();
            }
        }

        /**
         * Handles the color button key down event.
         *
         * @param {KeyboardEvent} e - the event.
         * @private
         */
        _onColorButtonKeyUp(e) {
            const cols = this.cols;
            const lastCol = this.cols - 1;
            const lastRow = this.rows - 1;
            const $button = $(e.target);
            const selection = this._getSelection($button);
            const length = this.length;
            let index = selection.row * this.cols + selection.col;

            switch (e.key) {
                case 'Home':
                    selection.col = 0;
                    if (e.ctrlKey) {
                        selection.row = 0;
                    }
                    break;

                case 'End':
                    selection.col = lastCol;
                    if (e.ctrlKey || selection.row * cols + selection.col >= length) {
                        index = length - 1;
                        selection.row = Math.trunc(index / cols);
                        selection.col = index % this.cols;
                    }
                    break;

                case 'ArrowLeft':
                    selection.col = selection.col > 0 ? selection.col - 1 : lastCol;
                    if (selection.row * cols + selection.col >= length) {
                        index = length - 1;
                        selection.row = Math.trunc(index / cols);
                        selection.col = index % this.cols;
                    }
                    break;

                case 'ArrowUp': // up arrow
                    selection.row = selection.row > 0 ? selection.row - 1 : lastRow;
                    if (selection.row * cols + selection.col >= length) {
                        selection.row = lastRow - 1;
                    }
                    break;

                case 'ArrowRight':
                    selection.col = selection.col < lastCol ? selection.col + 1 : 0;
                    if (selection.row * cols + selection.col >= length) {
                        selection.col = 0;
                    }
                    break;

                case 'ArrowDown':
                    selection.row = selection.row < lastRow ? selection.row + 1 : 0;
                    if (selection.row * cols + selection.col >= length) {
                        selection.row = 0;
                    }
                    break;

                case '+':
                    if (index < length - 1) {
                        index++;
                        selection.row = Math.trunc(index / cols);
                        selection.col = index % cols;
                    } else {
                        selection.col = selection.row = 0;
                    }
                    break;

                case '-':
                    if (index > 0) {
                        index--;
                    } else {
                        index = length - 1;
                    }
                    selection.row = Math.trunc(index / cols);
                    selection.col = index % cols;
                    break;

                default:
                    return;
            }

            // update
            this._setSelection(selection);
            e.preventDefault();
        }

        /**
         * Handles the label click event.
         *
         * @param {Event} e - the event.
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
         * @returns {Object} the row and column index of the selected button.
         * @private
         */
        _getSelection($button) {
            // find button
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
         * @param {Object} selection - the selection to set (must contain a 'row' and a 'col' fields).
         * @returns {jQuery} the button, if found; null otherwise.
         * @private
         */
        _setSelection(selection) {
            const index = selection.row * this.cols + selection.col;
            const selector = `.btn-color[data-index="${index}"]`;
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
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    ColorPicker.DEFAULTS = {
        focus: false, // set focus to the control

        columns: 8, // the number of columns
        displayText: true, // false to hide text

        // texts
        customText: 'Custom', // the custom color text
        advancedText: 'Advanced...', // the advanced button's text

        // tooltip
        tooltipDisplay: true, // display
        tooltipPlacement: 'top', // placement
        tooltipContent: '{name} ({color})', // text format
        tooltipTrigger: 'hover', // trigger event

        // colors
        colors: {
            "Noir": "#000000",
            "Tundora": "#424242",
            "Colombe grise": "#636363",
            "Poussi\u00e8re d'\u00e9toile": "#9C9C94",
            "Ardoise": "#CEC6CE",
            "Galerie": "#EFEFEF",
            "Alb\u00e2tre": "#F7F7F7",
            "Blanc": "#FFFFFF",
            "Rouge": "#FF0000",
            "Pelure d'orange": "#FF9C00",
            "Jaune": "#FFFF00",
            "Vert": "#00FF00",
            "Cyan": "#00FFFF",
            "Bleu": "#0000FF",
            "Violet": "#9C00FF",
            "Magenta": "#FF00FF",
            "Azal\u00e9e": "#F7C6CE",
            "Karry": "#FFE7CE",
            "Blanc d'oeuf": "#FFEFC6",
            "Zanah": "#D6EFD6",
            "Botticelli": "#CEDEE7",
            "Bleu tropical": "#CEE7F7",
            "Mischka": "#D6D6E7",
            "Cr\u00e9puscule": "#E7D6DE",
            "Rose Tonys": "#E79C9C",
            "Orange p\u00eache": "#FFC69C",
            "Cr\u00e8me brul\u00e9e": "#FFE79C",
            "Germes": "#B5D6A5",
            "Casper": "#A5C6CE",
            "Perano": "#9CC6EF",
            "Violet froid": "#B5A5D6",
            "Rose Careys": "#D6A5BD",
            "Mandy": "#E76363",
            "Rajah": "#F7AD6B",
            "Pissenlit": "#FFD663",
            "Olivine": "#94BD7B",
            "Ruisseau": "#73A5AD",
            "Viking": "#6BADDE",
            "Blue Marguerite": "#8C7BC6",
            "Puce": "#C67BA5",
            "Gardien Rouge": "#CE0000",
            "Fire Bush": "#E79439",
            "R\u00eave d'or": "#EFC631",
            "Concombre": "#6BA54A",
            "Bleu slim": "#4A7B8C",
            "Bleu Boston": "#3984C6",
            "Papillon du Bush": "#634AA5",
            "Cadillac": "#A54A7B",
            "Sangria": "#9C0000",
            "Mai Tai": "#B56308",
            "Bouddha d'or": "#BD9400",
            "Vert for\u00eat": "#397B21",
            "Eden": "#104A5A",
            "Bleu Venise": "#085294",
            "M\u00e9t\u00e9orite": "#311873",
            "Bordeaux": "#731842",
            "Bois de rose": "#630000",
            "Cannelle": "#7B3900",
            "Olive": "#846300",
            "Persil": "#295218",
            "Tibre": "#083139",
            "Bleu Minuit": "#003163",
            "Valentino": "#21104A",
            "Loulou": "#4A1031"
        }
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

}(jQuery));
