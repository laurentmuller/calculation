/**
 * Class to build context menus.
 */
const MenuBuilder = class { /* exported MenuBuilder */

    /**
     * Constructor.
     * @param {Object} [options] the context menu options.
     */
    constructor(options) {
        this.index = 0;
        /** @type {Object.<string, Object>} */
        this.items = {};
        this.options = $.extend(true, {}, options);
        if (this.options.elements) {
            this.fill(this.options.elements);
        }
    }

    /**
     * Adds an item entry.
     *
     * @param {jQuery} $link - the element to add.
     * @return {MenuBuilder} This instance for chaining.
     */
    addItem($link) {
        const key = 'entry_' + this.index++;
        const selector = this.options.classSelector || false;
        const className = (selector && $link.hasClass(selector)) ? selector : '';
        this.items[key] = {
            link: $link,
            isHtmlName: true,
            name: $link.html(),
            className: className
        };
        return this;
    }

    /**
     * Adds a separator. Do nothing if empty or if the last item is already a separator.
     *
     * @return {MenuBuilder} This instance for chaining.
     */
    addSeparator() {
        // empty or last is already a separator?
        if (this.isEmpty() || this.isSeparator(this.getLastKey())) {
            return this;
        }

        // add
        const key = 'separator_' + this.index++;
        this.items[key] = {
            'type': 'cm_separator'
        };
        return this;
    }

    /**
     * Adds a title. Do nothing if the last item is already a title.
     *
     * @param {jQuery} $item - the item's title.
     * @return {MenuBuilder} This instance for chaining.
     */
    addTitle($item) {
        // already a title?
        if (this.isTitle(this.getLastKey())) {
            return this;
        }

        // get properties
        const title = $item.text();
        const tag = $item.prop('tagName');
        const className = $item.attr('class') + ' py-1';
        const key = 'title_' + this.index++;
        const html = `<${tag} class="${className}">${title}</${tag}>`;

        // add
        this.items[key] = {
            type: 'html',
            icon: function (_options, $element) {
                $element.html(html);
            }
        };
        return this;
    }

    /**
     * Gets items.
     *
     * @return {Object.<string, Object>}
     */
    getItems() {
        // remove the first and last separators (if any)
        while (this.isSeparator(this.getFirstKey())) {
            delete this.items[this.getFirstKey()];
        }
        while (this.isSeparator(this.getLastKey())) {
            delete this.items[this.getLastKey()];
        }
        return this.items;
    }

    /**
     * Returns a value indicating if this builder is empty.
     *
     * @return {boolean} true if empty.
     */
    isEmpty() {
        return $.isEmptyObject(this.items);
    }

    /**
     * Returns if the given key is a separator item.
     *
     * @param {string} [key] - the key to be tested.
     * @return {boolean} true if separator.
     */
    isSeparator(key) {
        return key && key.startsWith('separator_');
    }

    /**
     * Returns if the given key is a title item.
     *
     * @param {string} [key] - the key to be tested.
     * @return {boolean} true if title.
     */
    isTitle(key) {
        return key && key.startsWith('title_');
    }

    /**
     * Gets the first key.
     *
     * @return {?string} the last key, if any; null otherwise.
     */
    getFirstKey() {
        const keys = Object.keys(this.items);
        return keys.length ? keys[0] : null;
    }

    /**
     * Gets the last key.
     *
     * @return {?string} the last key, if any; null otherwise.
     */
    getLastKey() {
        const keys = Object.keys(this.items);
        return keys.length ? keys[keys.length - 1] : null;
    }

    /**
     * Fills the given elements.
     *
     * @param {jQuery} $elements the elements to add.
     * @return {MenuBuilder} This instance for chaining.
     */
    fill($elements) {
        const that = this;
        $elements.each(function () {
            let $item = $(this);
            if ($item.is('li')) {
                $item = $item.children(':first-child');
            }
            if ($item.hasClass('dropdown-divider')) {
                that.addSeparator();
            } else if ($item.hasClass('dropdown-header')) {
                that.addTitle($item);
            } else if ($item.isSelectable()) {
                that.addItem($item);
            }
        });
        return that;
    }
};

/**
 * -------------- jQuery extensions --------------
 */

/**
 * jQuery's extension for context-menu.
 */
(function ($) {
    'use strict';

    // noinspection JSUnusedGlobalSymbols
    $.extend({
        /**
         * Returns if the given data is a function.
         *
         * Note. Must be removed when the context-menu plugin is updated to jQuery v4.0.
         *
         * @param {any} data - The data to evaluate.
         * @return {boolean} true if a function.
         */
        isFunction: function (data) {
            return typeof data === 'function';
        }
    });

    $.fn.extend({
        /**
         * Returns if this element is selectable.
         *
         * @returns {boolean} true if selectable.
         */
        isSelectable: function () {
            const $this = $(this);
            return !($this.hasClass('disabled') || $this.hasClass('d-none'));
        },

        /**
         * Drop first, last, and all 2 consecutive separators in a drop-down menu.
         *
         * @return {jQuery} - the element for chaining.
         */
        removeSeparators: function () {
            const selector = 'li:has(.dropdown-divider),.dropdown-divider';
            return this.each(function () {
                const $this = $(this);
                if ($this.is('.dropdown-menu')) {
                    // remove firsts
                    while ($this.children().first().is(selector)) {
                        $this.children().first().remove();
                    }
                    // remove lasts
                    while ($this.children().last().is(selector)) {
                        $this.children().last().remove();
                    }
                    // remove 2 consecutive separators
                    let previewSeparator = false;
                    $this.children().each(function (index, element) {
                        const $item = $(element);
                        const isSeparator = $item.is(selector);
                        if (previewSeparator && isSeparator) {
                            $item.remove();
                        } else {
                            previewSeparator = isSeparator;
                        }
                    });
                }
            });
        },

        /**
         * Initialize the context menu.
         *
         * @param {string}  selector - the selector matching the elements to trigger on.
         *
         * @param {function} [fnShow] - the function called when the context menu is shown.
         * @param {function} [fnHide] - the function called when the context menu is hidden.
         * @param {Object} [options] - the context menu options to override.
         * @return {jQuery} The jQuery element for chaining.
         */
        initContextMenu: function (selector, fnShow, fnHide, options) {
            return this.each(function () {
                // build callback
                const build = function ($element) {
                    // get items
                    const items = $element.getContextMenuItems();
                    if ($.isEmptyObject(items)) {
                        return false;
                    }

                    // default options
                    const settings = {
                        items: items,
                        zIndex: 1000,
                        autoHide: true,
                        animation: {
                            duration: 0
                        },
                        callback: function (key, options, e) {
                            const item = options.items[key];
                            if (item && item.link && item.link.length) {
                                e.preventDefault();
                                item.link.get(0).click();
                                return true;
                            }
                            return false;
                        },
                        events: {
                            show: fnShow || $.noop,
                            hide: fnHide || $.noop
                        }
                    };

                    return $.extend(true, settings, options);
                };

                // create
                $.contextMenu({
                    build: build,
                    selector: selector
                });
            });
        }
    });
}(jQuery));
