/**! compression tag for ftp-deployment */

/**
 * Class to build context-menu items.
 */
const MenuBuilder = class { /* exported MenuBuilder */

    /**
     * Constructor.
     * @param {Object} [options] the context menu options.
     */
    constructor(options) {
        this.index = 0;
        this.items = {};
        this.options = $.extend(true, {}, options);
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
     * Adds a separator. Do nothing if the last item is already a
     * separator.
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
     * @param {string} title - the item's title.
     * @param {string} [tag] - the item's tag or h6 if none.
     * @return {MenuBuilder} This instance for chaining.
     */
    addTitle(title, tag) {
        // already a title?
        if (this.isTitle(this.getLastKey())) {
            return this;
        }

        // properties
        tag = tag || 'h6';
        const key = 'title_' + this.index++;
        const html = `<${tag} class="dropdown-header p-0">${title}</${tag}>`;

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
     * @return {Object} The items.
     */
    getItems() {
        // remove first and last separators (if any)
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
        'use strict';
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
        if (keys.length) {
            return keys[0];
        }
        return null;
    }

    /**
     * Gets the last key.
     *
     * @return {?string} the last key, if any; null otherwise.
     */
    getLastKey() {
        const keys = Object.keys(this.items);
        if (keys.length) {
            return keys[keys.length - 1];
        }
        return null;
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
            const $item = $(this);
            if ($item.hasClass('dropdown-divider')) {
                that.addSeparator();
            } else if ($item.hasClass('dropdown-header')) {
                that.addTitle($item.text(), $item.prop("tagName"));
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
$.fn.extend({
    /**
     * Returns if this element is selectable.
     *
     * @returns {boolean} true if selectable.
     */
    isSelectable: function () {
        'use strict';
        const $this = $(this);
        return !($this.hasClass('disabled') || $this.hasClass('d-none'));
    },

    /**
     * Initialize the context menu.
     *
     * @param {string}  selector - the selector matching the elements to trigger on.
     * @param {function} [fnShow] - the function called when the context menu is shown.
     * @param {function} [fnHide] - the function called when the context menu is hidden.
     * @param {Object} [options] - the context menu options to override.
     * @return {jQuery} The jQuery element for chaining.
     */
    initContextMenu: function (selector, fnShow, fnHide, options) {
        'use strict';
        return this.each(function () {
            //const $this = $(this);
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
