/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // -----------------------------
    // Initialization
    // -----------------------------
    var ColorPicker = function (element, options) {
        this.$element = $(element);
        this.options = $.extend(true, {}, ColorPicker.DEFAULTS, this.$element.data(), options);
        this.$element.removeDataAttributes();
        this.init();
    };

    // -----------------------------
    // Prototype functions
    // -----------------------------
    ColorPicker.prototype = {

        /**
         * Constrcutor.
         */
        constructor: ColorPicker,

        /**
         * Initialize widget.
         */
        init: function () {
            const that = this;
            const $element = that.$element;
            const focused = $element.is(':focus');
            that.rows = that.options.names.length;
            that.cols = that.options.names[0].length;

            // drop-down
            that.createDropDown();

            // add handler
            $element.on('input', function () {
                that.onElementInput();
            });
            that.updateUI();

            // focus
            if (focused || that.options.focus) {
                that.setFocus();
            }
        },

        /**
         * Destroy color-picker.
         */
        destroy: function () {
            this.$dropdown.before(this.$dropdown).remove();
            this.$element.removeClasss('d-none').removeData('colorpicker');
        },

        /**
         * Creates the drop-down element, if applicable.
         */
        createDropDown: function () {
            const that = this;

            // already creatd?
            if (that.$element.parents('.dropdown').length) {
                // find elements
                that.$dropdown = that.$element.parents('.dropdown');
                that.$dropdownToggle = that.$dropdown.find('.dropdown-toggle');
                that.$spanColor = that.$dropdown.find('.drowpdown-color');
                that.$spanText = that.$dropdown.find('.drowpdown-text');
                that.$dropdownMenu = that.$dropdown.find('.dropdown-menu');
            } else {
                // create
                that.$dropdown = $('<div/>', {
                    'class': 'color-picker dropdown form-control'
                });

                that.$dropdownToggle = $('<button/>', {
                    'type': 'button',
                    'class': 'dropdown-toggle btn',
                    'data-toggle': 'dropdown',
                    'aria-haspopup': 'true',
                    'aria-expanded': 'false'
                }).appendTo(this.$dropdown);

                that.$spanColor = $('<span/>', {
                    'class': 'drowpdown-color border border-secondary'
                }).appendTo(this.$dropdownToggle);

                that.$spanText = $('<span/>', {
                    'class': 'drowpdown-text'
                }).appendTo(this.$dropdownToggle);

                that.$dropdownMenu = $('<div/>', {
                    'class': 'dropdown-menu d-print-none'
                }).appendTo(this.$dropdown);

                // hide element and add dropdown
                that.$element.addClass('d-none').after(that.$dropdown).prependTo(that.$dropdown);
            }

            // add handlers
            that.$dropdown.on('show.bs.dropdown', function () {
                that.onDropdownBeforeVisible($(this));
            });
            that.$dropdown.on('shown.bs.dropdown', function () {
                that.onDropdownAfterVisible($(this));
            });
            that.$dropdown.parents('.form-group').find('label').on('click', function () {
                that.setFocus();
            });
        },

        /**
         * Create the palette element.
         */
        createPalette: function () {
            const that = this;
            const options = that.options;

            // get tooltip options
            const display = options.tooltipDisplay;
            const content = options.tooltipContent;

            // default buttons options
            let buttonOptions = {
                'type': 'button',
                'class': 'btn color-button',
            };
            if (display) {
                buttonOptions['data-toggle'] = 'tooltip';
                buttonOptions['data-trigger'] = options.tooltipTrigger;
                buttonOptions['data-placement'] = options.tooltipPlacement;
            }

            // palette
            that.$palette = $('<div/>', {
                'class': 'color-palette',
            });

            // rows
            for (let row = 0; row < that.rows; row++) {
                // row
                const $row = $('<div/>', {
                    'class': 'color-row'
                });

                // buttons
                const names = options.names[row];
                const colors = options.colors[row];
                for (let col = 0; col < that.cols; col++) {
                    const name = names[col];
                    const color = colors[col].toUpperCase();

                    buttonOptions.css = {
                        'background-color': color
                    };
                    buttonOptions['data-value'] = color;
                    buttonOptions.title = content.replace('{name}', name).replace('{color}', color);
                    $('<button/>', buttonOptions).appendTo($row);
                }
                $row.appendTo(that.$palette);
            }

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
                that.findButton('.color-button').tooltip();
            }

            // add handlers
            that.$customButton.on('click', function (e) {
                that.onCustomButtonClick(e);
            });
            that.$palette.on('click', '.color-button', function (e) {
                that.onColorButtonClick(e);
            });
            that.$palette.on('keydown', '.color-button', function (e) {
                that.onColorButtonKeyDown(e);
            });
        },

        // -----------------------------
        // Handlers functions
        // -----------------------------

        /**
         * Handles the color input event.
         */
        onElementInput: function () {
            this.updateUI();
            this.setFocus();
        },

        /**
         * Handles the dropdown before show event.
         */
        onDropdownBeforeVisible: function () {
            if (!this.$palette) {
                this.createPalette();
            }
        },

        /**
         * Handles the dropdown after show event.
         */
        onDropdownAfterVisible: function () {
            // get value
            const value = (this.$element.val() || '').toUpperCase();

            // find button
            const $button = this.findButton('.color-button[data-value="' + value + '"]:first');
            if ($button) {
                $button.focus();
            } else {
                this.setSelection({
                    col: 0,
                    row: 0
                });
            }
        },

        /**
         * Handles the custom button click event.
         * 
         * @param {Event}
         *            e - the event.
         */
        onCustomButtonClick: function (e) {
            e.preventDefault();
            this.$element.trigger('click');
            this.setFocus();
        },

        /**
         * Handles the color button click event.
         * 
         * @param {Event}
         *            e - the event.
         */
        onColorButtonClick: function (e) {
            e.preventDefault();
            const $button = $(e.target);
            const oldValue = this.$element.val();
            const newValue = $button.data('value');
            if (!newValue.equalsIgnoreCase(oldValue)) {
                this.$element.val(newValue);
                this.$element.trigger('input');
            }
        },

        /**
         * Handles the color button key down event.
         * 
         * @param {Event}
         *            e - the event.
         */
        onColorButtonKeyDown: function (e) {
            const lastCol = this.cols - 1;
            const lastRow = this.rows - 1;
            const $button = $(e.target);
            const selection = this.getSelection($button);
            
            switch (e.which || e.keyCode) {
            case 35: // end
                selection.col = lastCol;
                if (e.ctrlKey) {
                    selection.row = lastRow;
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
                break;

            case 38: // up arrow
                selection.row = selection.row > 0 ? selection.row - 1 : lastRow;
                break;

            case 39: // right arrow
                selection.col = selection.col < lastCol ? selection.col + 1 : 0;
                break;

            case 40: // down arrow
                selection.row = selection.row < lastRow ? selection.row + 1 : 0;
                break;

            case 107: // add
                if (selection.col < lastCol) {
                    selection.col++;
                } else if (selection.row < lastRow) {
                    selection.col = 0;
                    selection.row++;
                } else {
                    selection.col = selection.row = 0;
                }
                break;

            case 109: // subtract
                if (selection.col > 0) {
                    selection.col--;
                } else if (selection.row > 0) {
                    selection.col = lastCol;
                    selection.row--;
                } else {
                    selection.col = lastCol;
                    selection.row = lastRow;
                }
                break;

            default:
                return;
            }

            // update
            this.setSelection(selection);
            e.preventDefault();
        },

        // -----------------------------
        // Utility functions
        // -----------------------------

        /**
         * Sets focus to the dropdown toggle.
         */
        setFocus: function () {
            this.$dropdownToggle.focus();
        },

        /**
         * Update the UI.
         */
        updateUI: function () {
            const value = this.$element.val();
            const text = this.getColorName(value);
            this.$spanColor.css('background-color', value);
            this.$spanText.text(text);
        },

        /**
         * Gets the selected color button.
         * 
         * @param {JQuery}
         *            $button - the clicked button element.
         * @returns {Object} the row and column index of the selected button.
         */
        getSelection: function ($button) {
            // find focus
            const $focused = this.findButton('.color-button:focus');
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
        },

        /**
         * Finds color buttons for the given selector.
         * 
         * @param {String}
         *            selector - the buttons selector.
         * @returns {JQuery} the buttons, if found; null otherwise.
         */
        findButton: function (selector) {
            const $button = this.$palette.find(selector);
            return $button.length ? $button : null;
        },

        /**
         * Sets the selected (focus) color button.
         * 
         * @param {Object}
         *            selection - the selection to set (must contains a 'row'
         *            and a 'col' field).
         * @returns {JQuery} the button, if found; null otherwise.
         */
        setSelection: function (selection) {
            const selector = '.color-row:eq(' + selection.row + ') .color-button:eq(' + selection.col + ')';
            const $button = this.findButton(selector);
            if ($button.length) {
                return $button.focus();
            }
            return null;
        },

        /**
         * Find the name for the given hexadecimal color.
         * 
         * @param {String}
         *            color - the hexadecimal color to search for.
         * @returns {String} the color name, if found; the custom text
         *          otherwise.
         */
        getColorName: function (color) {
            color = color || '';
            const cols = this.cols;
            const colors = this.options.colors;

            for (let row = 0, rows = this.rows; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    if (colors[row][col].equalsIgnoreCase(color)) {
                        return this.options.names[row][col];
                    }
                }
            }

            // custom text
            return this.options.customText;
        },

        /**
         * Find the hexadecimal color for the given name.
         * 
         * @param {String}
         *            name - the color name to search for.
         * @returns {String} the hexadecimal color, if found; the first color
         *          otherwise.
         */
        getColorHex: function (name) {
            name = name || '';
            const cols = this.cols;
            const names = this.options.names;

            for (let row = 0, rows = this.rows; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    if (names[row][col].equalsIgnoreCase(name)) {
                        return this.options.colors[row][col];
                    }
                }
            }

            // first color
            return this.options.colors[0][0];
        },
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    ColorPicker.DEFAULTS = {
        focus: false, // set focus to the control

        // classes
        // dropdownClass: 'color-picker dropdown form-control',
        // dropdownToggleClass: 'dropdown-toggle btn',
        // dropdownMenuClass: 'dropdown-menu d-print-none',
        //
        // dropdownColorClass: 'drowpdown-color',
        // dropdownTextClass: 'drowpdown-text',
        //
        // paletteClass: 'color-palette',
        // paletteRrowClass: 'color-row',
        // paletteButtonClass: 'color-button',
        // paletteCustomClass: 'btn btn-sm btn-outline-secondary mt-1 w-100',

        // columns: 8, // the number of columns

        // texts
        customText: 'Custom', // the custom color text
        advancedText: 'Advanced...', // the advanced button's text

        // tooltip
        tooltipDisplay: true, // display
        tooltipPlacement: 'top', // placement
        tooltipContent: '{name} ({color})', // text format
        tooltipTrigger: 'hover', // trigger event

        colors: [// color values
        ['#000000', '#424242', '#636363', '#9C9C94', '#CEC6CE', '#EFEFEF', '#F7F7F7', '#FFFFFF'], // 
        ['#FF0000', '#FF9C00', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#9C00FF', '#FF00FF'], //
        ['#F7C6CE', '#FFE7CE', '#FFEFC6', '#D6EFD6', '#CEDEE7', '#CEE7F7', '#D6D6E7', '#E7D6DE'], //
        ['#E79C9C', '#FFC69C', '#FFE79C', '#B5D6A5', '#A5C6CE', '#9CC6EF', '#B5A5D6', '#D6A5BD'], //
        ['#E76363', '#F7AD6B', '#FFD663', '#94BD7B', '#73A5AD', '#6BADDE', '#8C7BC6', '#C67BA5'], //
        ['#CE0000', '#E79439', '#EFC631', '#6BA54A', '#4A7B8C', '#3984C6', '#634AA5', '#A54A7B'], //
        ['#9C0000', '#B56308', '#BD9400', '#397B21', '#104A5A', '#085294', '#311873', '#731842'], //
        ['#630000', '#7B3900', '#846300', '#295218', '#083139', '#003163', '#21104A', '#4A1031'], //
        ],
        names: [ // color names (french)
        ['Noir', 'Tundora', 'Colombe grise', 'Poussière d\'étoile', 'Ardoise pâle', 'Galerie', 'Albâtre', 'Blanc'], //
        ['Rouge', 'Pelure d\'orange', 'Jaune', 'Vert', 'Cyan', 'Bleu', 'Violet électrique', 'Magenta'], //
        ['Azalée', 'Karry', 'Blanc d\'oeuf', 'Zanah', 'Botticelli', 'Bleu tropical', 'Mischka', 'Crépuscule'], //
        ['Rose Tonys', 'Orange pêche', 'Crème brulée', 'Germes', 'Casper', 'Perano', 'Violet froid', 'Rose Careys'], //
        ['Mandy', 'Rajah', 'Pissenlit', 'Olivine', 'Ruisseau du Golfe', 'Viking', 'Blue Marguerite', 'Puce'], //
        ['Gardien Rouge', 'Fire Bush', 'Rêve d\'or', 'Concombre', 'Bleu slim', 'Bleu Boston', 'Papillon', 'Cadillac'], //
        ['Sangria', 'Mai Tai', 'Bouddha d\'or', 'Vert forêt', 'Eden', 'Bleu Venise', 'Météorite', 'Bordeaux'], //
        ['Bois de rose', 'Cannelle', 'Olive', 'Persil', 'Tibre', 'Bleu Minuit', 'Valentino', 'Loulou'] //
        ],

    // color names (english)
    // ['Black', 'Tundora', 'Dove Gray', 'Star Dust', 'Pale Slate',
    // 'Gallery', 'Alabaster', 'White'], //
    // ['Red', 'Orange Peel', 'Yellow', 'Green', 'Cyan', 'Blue', 'Electric
    // Violet', 'Magenta'], //
    // ['Azalea', 'Karry', 'Egg White', 'Zanah', 'Botticelli', 'Tropical
    // Blue', 'Mischka', 'Twilight'], //
    // ['Tonys Pink', 'Peach Orange', 'Cream Brulee', 'Sprout', 'Casper',
    // 'Perano', 'Cold Purple', 'Careys Pink'], //
    // ['Mandy', 'Rajah', 'Dandelion', 'Olivine', 'Gulf Stream', 'Viking',
    // 'Blue Marguerite', 'Puce'], //
    // ['Guardsman Red', 'Fire Bush', 'Golden Dream', 'Chelsea Cucumber',
    // 'Smalt Blue', 'Boston Blue', 'Butterfly Bush', 'Cadillac'], //
    // ['Sangria', 'Mai Tai', 'Buddha Gold', 'Forest Green', 'Eden', 'Venice
    // Blue', 'Meteorite', 'Claret'], //
    // ['Rosewood', 'Cinnamon', 'Olive', 'Parsley', 'Tiber', 'Midnight
    // Blue', 'Valentino', 'Loulou'], //

    // input: {
    // "Noir": "#000000",
    // "Tundora": "#424242",
    // "Colombe grise": "#636363",
    // "Poussière d'étoile": "#9C9C94",
    // "Ardoise pâle": "#CEC6CE",
    // "Galerie": "#EFEFEF",
    // "Albâtre": "#F7F7F7",
    // "Blanc": "#FFFFFF",
    // "Rouge": "#FF0000",
    // "Pelure d'orange": "#FF9C00",
    // "Jaune": "#FFFF00",
    // "Vert": "#00FF00",
    // "Cyan": "#00FFFF",
    // "Bleu": "#0000FF",
    // "Violet électrique": "#9C00FF",
    // "Magenta": "#FF00FF",
    // "Azalée": "#F7C6CE",
    // "Karry": "#FFE7CE",
    // "Blanc d'oeuf": "#FFEFC6",
    // "Zanah": "#D6EFD6",
    // "Botticelli": "#CEDEE7",
    // "Bleu tropical": "#CEE7F7",
    // "Mischka": "#D6D6E7",
    // "Crépuscule": "#E7D6DE",
    // "Rose Tonys": "#E79C9C",
    // "Orange pêche": "#FFC69C",
    // "Crème brulée": "#FFE79C",
    // "Germes": "#B5D6A5",
    // "Casper": "#A5C6CE",
    // "Perano": "#9CC6EF",
    // "Violet froid": "#B5A5D6",
    // "Rose Careys": "#D6A5BD",
    // "Mandy": "#E76363",
    // "Rajah": "#F7AD6B",
    // "Pissenlit": "#FFD663",
    // "Olivine": "#94BD7B",
    // "Ruisseau du Golfe": "#73A5AD",
    // "Viking": "#6BADDE",
    // "Blue Marguerite": "#8C7BC6",
    // "Puce": "#C67BA5",
    // "Gardien Rouge": "#CE0000",
    // "Fire Bush": "#E79439",
    // "Rêve d'or": "#EFC631",
    // "Concombre de Chelsea": "#6BA54A",
    // "Bleu slim": "#4A7B8C",
    // "Bleu Boston": "#3984C6",
    // "Papillon du Bush": "#634AA5",
    // "Cadillac": "#A54A7B",
    // "Sangria": "#9C0000",
    // "Mai Tai": "#B56308",
    // "Bouddha d'or": "#BD9400",
    // "Vert forêt": "#397B21",
    // "Eden": "#104A5A",
    // "Bleu Venise": "#085294",
    // "Météorite": "#311873",
    // "Bordeaux": "#731842",
    // "Bois de rose": "#630000",
    // "Cannelle": "#7B3900",
    // "Olive": "#846300",
    // "Persil": "#295218",
    // "Tibre": "#083139",
    // "Bleu Minuit": "#003163",
    // "Valentino": "#21104A",
    // "Loulou": "#4A1031"
    // }
    };

    // -----------------------------------
    // ColorPicker plugin definition
    // -----------------------------------
    const oldColorPicker = $.fn.colorpicker;

    $.fn.colorpicker = function (option) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('colorpicker');
            if (!data) {
                const options = typeof option === 'object' && option;
                $this.data('colorpicker', data = new ColorPicker(this, options));
            }
        });
    };

    $.fn.colorpicker.Constructor = ColorPicker;

    // -----------------------------------
    // Colorpicker no conflict
    // -----------------------------------
    $.fn.colorpicker.noConflict = function () {
        $.fn.colorpicker = oldColorPicker;
        return this;
    };

}(jQuery));