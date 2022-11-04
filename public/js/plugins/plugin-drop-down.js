/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // -----------------------------------
    // DropDown public class definition
    // -----------------------------------

    /**
     * Plugin name.
     * @type {{NAME: string}}
     */
    $.DropDown = {
        'NAME': 'bs.drop-down'
    };

    const DropDown = class {
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
            this.options = $.extend(true, {}, DropDown.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            if (this.$menu.length) {
                this.$menu.off('shown.bs.dropdown', this.menuShowProxy);
                this.$menu.off('click', '.dropdown-item', this.menuClickProxy);
            }
            this.$element.removeData($.DropDown.NAME);
        }

        /**
         * Sets the selected value.
         * @param {any} value - the selected value to set.
         */
        setValue(value) {
            if (this.getValue() !== value) {
                this._updateValue(value || '', null);
            }
        }

        /**
         * Gets the selected value.
         * @return {any} the selected value.
         */
        getValue() {
            return this.$element.data('value');
        }

        /**
         * Gets the identifier attribute.
         * @return {string} the identifier attribute.
         */
        getId() {
            return this.$element.attr('id');
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize widget.
         * @private
         */
        _init() {
            this.$menu = this.$element.next('.dropdown-menu');
            if (this.$menu.length) {
                this.menuShowProxy = () => this._menuShow();
                this.menuClickProxy = (e) => this._menuItemClick(e);
                this.$menu.on('shown.bs.dropdown', this.menuShowProxy);
                this.$menu.on('click', '.dropdown-item', this.menuClickProxy);
            }
        }

        /**
         * Handle the drop-down menu item click.
         * @param {Event} e - the event.
         * @private
         */
        _menuItemClick(e) {
            e.preventDefault();
            const $item = $(e.currentTarget);
            const oldValue = this.getValue() || '';
            const newValue = $item.data('value') || '';
            if (newValue !== oldValue) {
                this._updateValue(newValue, $item);
            }
            this.$element.trigger('focus');
        }

        /**
         * Sets the selected value.
         * @param {any} [value] - the value to set.
         * @param {JQuery} [$selection] - the selected drop-down item.
         * @private
         */
        _updateValue(value, $selection) {
            const $element = this.$element;
            const copyIcon = this.options.copyIcon;
            const copyText = this.options.copyText;
            const $items = this.$menu.find('.dropdown-item').removeClass('active');

            // default values
            let $icon = $element.find('i');
            if ($icon.length === 0 && $element.data('icon')) {
                $icon = $($element.data('icon'));
            }
            let text = $element.text().trim();

            // select first item if no value
            if (!value || !$selection) {
                $selection = $items.first();
            }
            $selection.addClass('active');

            // icon
            if (copyIcon) {
                const $newIcon = $selection.find('i');
                if ($newIcon.length) {
                    $icon = $newIcon;
                }
            }

            // text
            if (copyText) {
                if (value) {
                    text = $selection.text().trim() || text;
                } else {
                    text = $element.data('default') || text;
                }
            }

            // update
            if ($icon.length) {
                $element.text(' ' + text).prepend($icon.clone());
            } else {
                $element.text(text);
            }
            $element.data('value', value).trigger('input', value);
        }

        /**
         * Handle the drop-down menu show event.
         * @private
         */
        _menuShow() {
            this.$menu.find('.active').trigger('focus');
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    DropDown.DEFAULTS = {
        copyText: true,
        copyIcon: false
    };

    // -----------------------------------
    // DropDown plugin definition
    // -----------------------------------
    const oldDropDown = $.fn.dropdown;
    $.fn.dropdown = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data($.DropDown.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data($.DropDown.NAME, new DropDown(this, settings));
            }
        });
    };
    $.fn.dropdown.Constructor = DropDown;

    // -----------------------------------
    // DropDown no conflict
    // -----------------------------------
    $.fn.dropdown.noConflict = function () {
        $.fn.dropdown = oldDropDown;
        return this;
    };
}(jQuery));
