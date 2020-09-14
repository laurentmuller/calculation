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
            const display = options.tooltip_display;
            const content = options.tooltip_content;

            // default buttons options
            let buttonOptions = {
                'type': 'button',
                'class': 'color-button',
            };
            if (display) {
                buttonOptions['data-toggle'] = 'tooltip';
                buttonOptions['data-trigger'] = options.tooltip_trigger;
                buttonOptions['data-placement'] = options.tooltip_placement;
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
                'text': options.advanced_text,
                'class': 'btn btn-sm btn-outline-secondary mt-1 w-100'
            }).appendTo(that.$palette);

            // append
            that.$palette.appendTo(that.$dropdownMenu);

            // tooltip
            if (display) {
                that.$palette.find('.color-button').tooltip();
            }

            // add handlers
            that.$customButton.on('click', function (e) {
                that.onCustomButtonClick($(this), e);
            });
            that.$palette.on('click', '.color-button', function (e) {
                that.onColorButtonClick($(this), e);
            });
            that.$palette.on('keydown', '.color-button', function (e) {
                that.onColorButtonKeyDown($(this), e);
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
            let found = false;
            this.$palette.find('.color-button').each(function () {
                const $button = $(this);
                if ($button.data('value') === value) {
                    $button.focus();
                    found = true;
                    return false;
                }
            });

            // select first
            if (!found) {
                this.setSelection({
                    col: 0,
                    row: 0
                });
            }
        },

        /**
         * Handles the custom button click event.
         * 
         * @param {JQuery}
         *            $button - the clicked button element.
         * @param {Event}
         *            e - the event.
         */
        onCustomButtonClick: function ($button, e) {
            e.preventDefault();
            this.$element.trigger('click');
            this.setFocus();
        },

        /**
         * Handles the color button click event.
         * 
         * @param {JQuery}
         *            $button - the clicked button element.
         * @param {Event}
         *            e - the event.
         * 
         */
        onColorButtonClick: function ($button, e) {
            e.preventDefault();
            const oldValue = this.$element.val().toLowerCase();
            const newValue = $button.data('value').toLowerCase();
            if (oldValue !== newValue) {
                this.$element.val(newValue);
                this.$element.trigger('input');
            }
        },

        /**
         * Handles the color button key down event.
         * 
         * @param {JQuery}
         *            $button - the clicked button element.
         * @param {Event}
         *            e - the event.
         */
        onColorButtonKeyDown: function ($button, e) {
            const lastCol = this.cols - 1;
            const lastRow = this.rows - 1;
            const selection = this.getSelection($button);
            switch (e.which || e.keyCode) {
            case 35: // end
                selection.col = lastCol;
                selection.row = lastRow;
                break;

            case 36: // home
                selection.col = selection.row = 0;
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
            const $focused = this.$palette.find('.color-button:focus');
            if ($focused.length) {
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
         * Sets the selected (focus) color button.
         * 
         * @param {Object}
         *            selection - the selection to set (must contains a 'row'
         *            and a 'col' field).
         * @returns {JQuery} the button, if found; null otherwise.
         */
        setSelection: function (selection) {
            const selector = '.color-row:eq(' + selection.row + ') .color-button:eq(' + selection.col + ')';
            const $button = this.$palette.find(selector);
            if ($button.length) {
                return $button.focus();
            }
            return null;
        },

        /**
         * Find the color name.
         * 
         * @param {String}
         *            color - the hexadecimal color to search for.
         * @returns {String} the color name, if found; the custom text
         *          otherwise.
         */
        getColorName: function (color) {
            color = color ? color.toUpperCase() : '';
            const colors = this.options.colors;
            for (let row = 0; row < this.rows; row++) {
                for (let col = 0; col < this.cols; col++) {
                    if (colors[row][col].toUpperCase() === color) {
                        return this.options.names[row][col];
                    }
                }
            }

            return this.options.custom_text;
        },

        /**
         * Find the hexadecimal color.
         * 
         * @param {String}
         *            name - the color name to search for.
         * @returns {String} the hexadecimal color, if found; the first color
         *          otherwise.
         */
        getColorHex: function (name) {
            name = name.toUpperCase();
            const names = this.options.names;
            for (let row = 0; row < this.rows; row++) {
                for (let col = 0; col < this.cols; col++) {
                    if (names[row][col].toUpperCase() === name) {
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
        // dropdown_class: 'color-picker dropdown form-control',
        // dropdown_toggle_class: 'dropdown-toggle btn',
        // dropdown_menu_class: 'dropdown-menu d-print-none',
        //
        // dropdown_color_class: 'drowpdown-color',
        // dropdown_text_class: 'drowpdown-text',
        //
        // palette_class: 'color-palette',
        // palette_row_class: 'color-row',
        // palette_button_class: 'color-button',
        // palette_custom_class: 'btn btn-sm btn-outline-secondary mt-1 w-100',

        // texts
        custom_text: 'Custom', // the custom color text
        advanced_text: 'Advanced...', // the advanced button's text

        // tooltip
        tooltip_display: true, // display tooltip
        tooltip_placement: 'top', // tooltip placement
        tooltip_content: '{name} ({color})', // tooltip text
        tooltip_trigger: 'hover', // tooltip trigger event

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
        names: [ // color names
        ['Noir', 'Tundora', 'Colombe grise', 'Poussière d\'étoile', 'Ardoise pâle', 'Galerie', 'Albâtre', 'Blanc'], // /
        ['Rouge', 'Pelure d\'orange', 'Jaune', 'Vert', 'Cyan', 'Bleu', 'Violet électrique', 'Magenta'], // /
        ['Azalée', 'Karry', 'Blanc d\'oeuf', 'Zanah', 'Botticelli', 'Bleu tropical', 'Mischka', 'Crépuscule'], // /
        ['Rose Tonys', 'Orange pêche', 'Crème brulée', 'Germes', 'Casper', 'Perano', 'Violet froid', 'Rose Careys'], // /
        ['Mandy', 'Rajah', 'Pissenlit', 'Olivine', 'Ruisseau du Golfe', 'Viking', 'Blue Marguerite', 'Puce'], // /
        ['Gardien Rouge', 'Fire Bush', 'Rêve d\'or', 'Concombre de Chelsea', 'Bleu slim', 'Bleu Boston', 'Papillon du Bush', 'Cadillac'], // /
        ['Sangria', 'Mai Tai', 'Bouddha d\'or', 'Vert forêt', 'Eden', 'Bleu Venise', 'Météorite', 'Bordeaux'], // /
        ['Bois de rose', 'Cannelle', 'Olive', 'Persil', 'Tibre', 'Bleu Minuit', 'Valentino', 'Loulou'], // /

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
        ],
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