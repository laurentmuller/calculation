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
            this.$dropdown.before(this.$dropdown).remove();
            this.$element.removeClass('d-none').removeData('color-picker');
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

            $element.removeDataAttributes();
            const length = Object.keys(that.options.colors).length;
            that.cols = that.options.columns;
            that.rows = Math.trunc(length / that.cols);
            if (length % that.cols !== 0) {
                that.rows++;
            }

            // drop-down
            that._createDropDown();

            // add handler
            $element.on('input', function () {
                that._onElementInput();
            });
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
            if (that.$element.parents('.dropdown').length) {
                // find elements
                that.$dropdown = that.$element.parents('.dropdown');
                that.$dropdownToggle = that.$dropdown.find('.dropdown-toggle');
                that.$spanColor = that.$dropdown.find('.dropdown-color');
                that.$spanText = that.$dropdown.find('.dropdown-text');
                that.$dropdownMenu = that.$dropdown.find('.dropdown-menu');
            } else {
                // create
                that.$dropdown = $('<div/>', {
                    'class': 'dropdown color-picker ' + (options.dropdownClass || '')
                });

                that.$dropdownToggle = $('<button/>', {
                    'type': 'button',
                    'class': 'dropdown-toggle ' + (options.dropdownToggleClass || ''),
                    'data-toggle': 'dropdown',
                    'aria-haspopup': 'true',
                    'aria-expanded': 'false'
                }).appendTo(that.$dropdown);

                that.$spanColor = $('<span/>', {
                    'class': 'dropdown-color border'
                }).appendTo(that.$dropdownToggle);

                if (options.displayText) {
                    that.$spanText = $('<span/>', {
                        'class': 'dropdown-text'
                    }).appendTo(that.$dropdownToggle);
                }

                that.$dropdownMenu = $('<div/>', {
                    'class': 'dropdown-menu d-print-none'
                }).appendTo(that.$dropdown);

                // hide element and add dropdown
                that.$element.addClass('d-none').after(that.$dropdown).prependTo(that.$dropdown);
            }

            // add handlers
            that.$dropdown.on('show.bs.dropdown', function () {
                that._onDropdownBeforeVisible($(this));
            });
            that.$dropdown.on('shown.bs.dropdown', function () {
                that._onDropdownAfterVisible($(this));
            });
            that.$dropdown.parents('.form-group').find('label').on('click', function () {
                that._setFocus();
            });
        }

        /**
         * Create the palette element.
         * @private
         */
        _createPalette() {
            const that = this;
            const options = that.options;
            const colors = options.colors;
            const columns = options.columns;

            // get tooltip options
            const display = options.tooltipDisplay;
            const content = options.tooltipContent;

            // default buttons options
            let buttonOptions = {
                'type': 'button',
                'class': 'border btn btn-color',
            };
            if (display) {
                buttonOptions['data-toggle'] = 'tooltip';
                buttonOptions['data-trigger'] = options.tooltipTrigger;
                buttonOptions['data-placement'] = options.tooltipPlacement;
            }

            // palette
            that.$palette = $('<div/>', {
                'class': 'color-palette'
            });

            // colors
            let $row;
            Object.keys(colors).forEach(function (name, index) {
                // row
                if (index % columns === 0) {
                    $row = $('<div/>', {
                        'class': 'color-row'
                    });
                    $row.appendTo(that.$palette);
                }
                // button
                const color = colors[name];
                buttonOptions.css = {
                    'background-color': color
                };
                buttonOptions['data-value'] = color;
                buttonOptions.title = content.replace('{name}', name).replace('{color}', color);
                $('<button/>', buttonOptions).appendTo($row);
            });

            // custom button
            that.$customButton = $('<button/>', {
                'type': 'button',
                'text': options.advancedText,
                'class': 'btn btn-sm btn-outline-secondary mt-1 w-100'
            }).appendTo(that.$palette);

            // append
            that.$palette.appendTo(that.$dropdownMenu);

            // tooltip
            if (display) {
                that._findButton('.btn-color').tooltip();
            }

            // add handlers
            that.$customButton.on('click', function (e) {
                that._onCustomButtonClick(e);
            });
            that.$palette.on('click', '.btn-color', function (e) {
                that._onColorButtonClick(e);
            });
            that.$palette.on('keydown', '.btn-color', function (e) {
                that._onColorButtonKeyDown(e);
            });
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
        _onDropdownBeforeVisible() {
            if (!this.$palette) {
                this._createPalette();
            }
        }

        /**
         * Handles the dropdown after show event.
         * @private
         */
        _onDropdownAfterVisible() {
            // get value
            const value = (this.$element.val() || '').toUpperCase();

            // find button
            const $button = this._findButton('.btn-color[data-value="' + value + '"]:first');
            if ($button) {
                $button.trigger('focus');
            } else {
                this._setSelection({
                    col: 0,
                    row: 0
                });
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
                this.$element.val(newValue);
                this.$element.trigger('input');
            }
        }

        /**
         * Handles the color button key down event.
         *
         * @param {KeyboardEvent} e - the event.
         * @private
         */
        _onColorButtonKeyDown(e) {
            const cols = this.cols;
            const lastCol = this.cols - 1;
            const lastRow = this.rows - 1;
            const $button = $(e.target);
            const selection = this._getSelection($button);
            const count = this.$palette.find('.btn-color').length;
            let index = selection.row * this.cols + selection.col;

            switch (e.which) {
                case 35: // end
                    selection.col = lastCol;
                    if (e.ctrlKey || selection.row * cols + selection.col >= count) {
                        index = count - 1;
                        selection.row = Math.trunc(index / cols);
                        selection.col = index % this.cols;
                    }
                    break;

                case 36: // home
                    selection.col = 0;
                    if (e.ctrlKey) {
                        selection.row = 0;
                    }
                    break;

                case 37: // left arrow
                    selection.col = selection.col > 0 ? selection.col - 1 : lastCol;
                    if (selection.row * cols + selection.col >= count) {
                        index = count - 1;
                        selection.row = Math.trunc(index / cols);
                        selection.col = index % this.cols;
                    }
                    break;

                case 38: // up arrow
                    selection.row = selection.row > 0 ? selection.row - 1 : lastRow;
                    if (selection.row * cols + selection.col >= count) {
                        selection.row = lastRow - 1;
                    }
                    break;

                case 39: // right arrow
                    selection.col = selection.col < lastCol ? selection.col + 1 : 0;
                    if (selection.row * cols + selection.col >= count) {
                        selection.col = 0;
                    }
                    break;

                case 40: // down arrow
                    selection.row = selection.row < lastRow ? selection.row + 1 : 0;
                    if (selection.row * cols + selection.col >= count) {
                        selection.row = 0;
                    }
                    break;

                case 107: // add
                    if (index < count - 1) {
                        index++;
                        selection.row = Math.trunc(index / cols);
                        selection.col = index % cols;
                    } else {
                        selection.col = selection.row = 0;
                    }
                    break;

                case 109: // subtract
                    if (index > 0) {
                        index--;
                    } else {
                        index = count - 1;
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

        // -----------------------------
        // Utility functions
        // -----------------------------

        /**
         * Sets focus to the dropdown toggle.
         * @private
         */
        _setFocus() {
            this.$dropdownToggle.trigger('focus');
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
         * @param {JQuery} $button - the clicked button element.
         * @returns {Object} the row and column index of the selected button.
         * @private
         */
        _getSelection($button) {
            // find focus
            const $focused = this._findButton('.btn-color:focus');
            if ($focused) {
                return {
                    col: $focused.index(),
                    row: $focused.parent().index()

                };
            } else if ($button && $button.length) {
                return {
                    col: $button.index(),
                    row: $button.parent().index()
                };
            } else {
                // first button
                return {
                    col: 0,
                    row: 0
                };
            }
        }

        /**
         * Finds color buttons for the given selector.
         *
         * @param {string} selector - the button selector.
         * @returns {JQuery} the buttons, if found; null otherwise.
         * @private
         */
        _findButton(selector) {
            const $button = this.$palette.find(selector);
            return $button.length ? $button : null;
        }

        /**
         * Sets the selected (focus) color button.
         *
         * @param {Object} selection - the selection to set (must contain a 'row' and a 'col' fields).
         * @returns {JQuery} the button, if found; null otherwise.
         * @private
         */
        _setSelection(selection) {
            const selector = '.color-row:eq(' + selection.row + ') .btn-color:eq(' + selection.col + ')';
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

        // classes
        dropdownClass: 'form-control',
        dropdownToggleClass: 'btn',

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
            "Poussière d'étoile": "#9C9C94",
            "Ardoise": "#CEC6CE",
            "Galerie": "#EFEFEF",
            "Albâtre": "#F7F7F7",
            "Blanc": "#FFFFFF",
            "Rouge": "#FF0000",
            "Pelure d'orange": "#FF9C00",
            "Jaune": "#FFFF00",
            "Vert": "#00FF00",
            "Cyan": "#00FFFF",
            "Bleu": "#0000FF",
            "Violet": "#9C00FF",
            "Magenta": "#FF00FF",
            "Azalée": "#F7C6CE",
            "Karry": "#FFE7CE",
            "Blanc d'oeuf": "#FFEFC6",
            "Zanah": "#D6EFD6",
            "Botticelli": "#CEDEE7",
            "Bleu tropical": "#CEE7F7",
            "Mischka": "#D6D6E7",
            "Crépuscule": "#E7D6DE",
            "Rose Tonys": "#E79C9C",
            "Orange pêche": "#FFC69C",
            "Crème brulée": "#FFE79C",
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
            "Rêve d'or": "#EFC631",
            "Concombre": "#6BA54A",
            "Bleu slim": "#4A7B8C",
            "Bleu Boston": "#3984C6",
            "Papillon du Bush": "#634AA5",
            "Cadillac": "#A54A7B",
            "Sangria": "#9C0000",
            "Mai Tai": "#B56308",
            "Bouddha d'or": "#BD9400",
            "Vert forêt": "#397B21",
            "Eden": "#104A5A",
            "Bleu Venise": "#085294",
            "Météorite": "#311873",
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

    // -----------------------------------
    // ColorPicker plugin definition
    // -----------------------------------
    const oldColorPicker = $.fn.colorpicker;

    $.fn.colorpicker = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data('color-picker')) {
                const settings = typeof options === 'object' && options;
                $this.data('color-picker', new ColorPicker(this, settings));
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
