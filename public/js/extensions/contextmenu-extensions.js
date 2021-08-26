/**! compression tag for ftp-deployment */

/**
 * Class to build context-menu items.
 */
const MenuBuilder = class { /* exported MenuBuilder */

    /**
     * Constructor.
     */
    constructor() {
        this.items = {};
        this.index = 0;
    }

    /**
     * Adds an item entry.
     *
     * @param {jQuery}
     *            $link - the element to add.
     * @param {string}
     *            icon - the item's icon.
     * @return {MenuBuilder} This instance for chaining.
     */
    addItem($link, icon) {
        const key = 'entry_' + this.index++;
        this.items[key] = {
            link: $link,
            name: $link.findText(),
            icon: icon || $link.findIcon()
        };
        return this;
    }

    /**
     * Adds a separator. Do nothing if the last added item is already a
     * separator.
     *
     * @return {MenuBuilder} This instance for chaining.
     */
    addSeparator() {
        // last is already a separator?
        if (this.isSeparator(this.getLastKey())) {
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
     * Adds a title. Do nothing if the last added item is already a title.
     *
     * @param {string}
     *            title - the item's title.
     * @param {string}
     *            tag - the item's tag.
     * @return {MenuBuilder} This instance for chaining.
     */
    addTitle(title, tag) {
        // last is already a title?
        if (this.isTitle(this.getLastKey())) {
            return this;
        }

        // properties
        tag = tag || 'h6';
        const key = 'title_' + this.index++;
        const html = '<tag class="context-menu-header">title</tag>'.replace(/tag/g, tag).replace(/title/g, title);

        // add
        this.items[key] = {
            type: 'html',
            title: title,
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
        // remove last separator (if any)
        const key = this.getLastKey();
        if (this.isSeparator(key)) {
            delete this.items[key];
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
     * @param {string}
     *            key - the key to be tested.
     * @return {boolean} true if separator.
     */
    isSeparator(key) {
        return key && key.startsWith('separator_');
    }

    /**
     * Returns if the given key is a title item.
     *
     * @param {string}
     *            key - the key to be tested.
     * @return {boolean} true if title.
     */
    isTitle(key) {
        return key && key.startsWith('title_');
    }


    /**
     * Gets the last key.
     *
     * @return {string} the last key, if any; null otherwise.
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
     * @param {Jquery}
     *            $elements the elements to add.
     * @return {MenuBuilder} This instance for chaining.
     */
    fill($elements) {
        const that = this;
        $elements.each(function () {
            const $this = $(this);
            if ($this.hasClass('dropdown-divider')) {
                that.addSeparator();
            } else if ($this.isSelectable()) { // .dropdown-item
                that.addItem($this);
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
     * Finds an icon within in this element.
     *
     * @returns {string} the icon class, if found, null otherwise.
     */
    findIcon: function () {
        'use strict';
        const $this = $(this);
        const icon = $this.data('icon');
        if (icon) {
            return icon;
        }
        const $child = $this.find('i');
        if ($child.length) {
            return $child.attr('class');
        }
        return null;
    },

    /**
     * Finds an text within in this element.
     *
     * @returns {string} the text, if found, null otherwise.
     */
    findText: function () {
        'use strict';
        const $this = $(this);
        return $this.text().trim() || $this.attr('title') || $this.data('text') || null;
    },

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
     * @param {string}
     *            selector - the selector matching the elements to trigger on.
     * @param {function}
     *            fnShow - the function called when the context menu is shown.
     * @param {function}
     *            fnHide - the function called when the context menu is hidden.
     * @return {jQuery} The jQuery element for chaining.
     */
    initContextMenu: function (selector, fnShow, fnHide) {
        'use strict';
        // build callback
        const build = function ($element) {
            // get items
            const items = $element.getContextMenuItems();
            if ($.isEmptyObject(items)) {
                return false;
            }

            return {
                zIndex: 1000,
                autoHide: true,
                callback: function (key, options, e) {
                    const item = options.items[key];
                    if (item.link) {
                        e.stopPropagation();
                        item.link.get(0).click();
                        return true;
                    }
                },
                events: {
                    show: fnShow || $.noop,
                    hide: fnHide || $.noop
                },
                items: items
            };
        };

        // create
        $.contextMenu({
            build: build,
            selector: selector
        });

        return $(this);
    }
});
