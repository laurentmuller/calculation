/**
 * Ready function
 */
$(function () {
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
                this.$menu.off('click', '.dropdown-item', this.menuClickProxy);
            }
            if (this.$dropdown.length) {
                this.$dropdown.off('shown.bs.dropdown', this.menuShowProxy);
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
            const className = `.${this.options.selectionClass}`;
            const $item = this.$menu.find(className);
            if ($item.length && $item.data('parameter')) {
                return $item.data('parameter');
            }
            return this.$element.attr('id');
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize the widget.
         * @private
         */
        _init() {
            this.$menu = this.$element.next('.dropdown-menu');
            if (this.$menu.length) {
                this.menuClickProxy = (e) => this._menuClick(e);
                this.$menu.on('click', '.dropdown-item', this.menuClickProxy);
            }
            this.$dropdown = this.$element.closest('[data-bs-toggle="dropdown"]');
            if (this.$dropdown.length) {
                this.menuShowProxy = () => this._menuShow();
                this.$dropdown.on('shown.bs.dropdown', this.menuShowProxy);
            }
        }

        /**
         * Handle the drop-down menu show event.
         * @private
         */
        _menuShow() {
            const className = `.${this.options.selectionClass}`;
            this.$menu.find(className).trigger('focus');
        }

        /**
         * Handle the drop-down menu item click.
         * @param {Event} e - the event.
         * @private
         */
        _menuClick(e) {
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
         * @param {jQuery} [$selection] - the selected drop-down item.
         * @private
         */
        _updateValue(value, $selection) {
            const options = this.options;
            const $element = this.$element;
            const className = options.selectionClass;
            const $items = this.$menu.find('.dropdown-item').removeClass(className);
            /** @type {JQuery<HTMLSpanElement>|any} */
            const $iconElement = $element.find(options.iconClass);
            /** @type {JQuery<HTMLSpanElement>|any} */
            const $textElement = $element.find(options.textClass);

            // default values
            /** @type {JQuery<HTMLSpanElement>|any} */
            let $icon = $iconElement.find('i');
            if ($icon.length === 0 && $element.data('icon')) {
                $icon = $($element.data('icon'));
            }
            let text = $textElement.text().trim();

            // select first item if no value or no selection
            if (!value || !$selection) {
                $selection = $items.first();
            }
            $selection.addClass(className);

            // icon
            if (options.copyIcon) {
                const $newIcon = $selection.find(options.iconClass).find('i');
                if ($newIcon.length) {
                    $icon = $newIcon;
                }
                if (options.resetIcon && $selection.is($items.first()) && $element.data('icon')) {
                    $icon = $($element.data('icon'));
                }
            }

            // text
            if (options.copyText) {
                if (value) {
                    text = $selection.find(options.textClass).text().trim() || text;
                } else {
                    text = $element.data('default') || text;
                }
            }

            // update
            $iconElement.empty().append($icon.clone());
            $textElement.text(text);
            $element.data('value', value).trigger('input', value);
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    DropDown.DEFAULTS = {
        copyText: true,
        copyIcon: true,
        resetIcon: true,
        iconClass: '.dropdown-icon',
        textClass: '.dropdown-label',
        selectionClass: 'active'
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
});
