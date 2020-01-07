/**! compression tag for ftp-deployment */

/**
 * -------------- JQuery extensions --------------
 */
$.fn.findIcon = function () {
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
};

$.fn.isSelectable = function () {
    'use strict';
    const $this = $(this);
    return !($this.hasClass('disabled') || $this.hasClass('d-none'));
};

/**
 * --------- Context menu extensions -------------
 */

/**
 * Class to build context-menu items.
 */
var MenuBuilder = function () {
    'use strict';
    this.items = {};
    this.index = 0;
};

// ------------------------
// Public API
// ------------------------
MenuBuilder.prototype = {

    /**
     * Adds an item entry.
     * 
     * @param {JQuery}
     *            $link - the element to add.
     * @param {String}
     *            icon - the item's icon.
     * @return {MenuBuilder} This instance for chaining.
     */
    addItem: function ($link, icon) {
        'use strict';

        const key = 'entry_' + this.index++;
        this.items[key] = {
            link: $link,
            name: $link.text().trim(),
            icon: icon || $link.findIcon()
        };
        return this;
    },

    /**
     * Adds a separator. Do nothing if the last added item is already a
     * separator.
     * 
     * @return {MenuBuilder} This instance for chaining.
     */
    addSeparator: function () {
        'use strict';

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
    },

    /**
     * Adds a title. Do nothing if the last added item is already a title.
     * 
     * @param {String}
     *            title - the item's title.
     * @param {String}
     *            tag - the item's tag.
     * @return {MenuBuilder} This instance for chaining.
     */
    addTitle: function (title, tag) {
        'use strict';

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
            icon: function (options, $element) {
                $element.html(html);
            }
        };
        return this;
    },

    /**
     * Gets items.
     * 
     * @return {Object} The items.
     */
    getItems: function () {
        'use strict';

        // remove last separator (if any)
        const key = this.getLastKey();
        if (this.isSeparator(key)) {
            delete this.items[key];
        }

        return this.items;
    },

    /**
     * Returns a value indicating if this builder is empty.
     * 
     * @return {Boolean} true if empty.
     */
    isEmpty: function () {
        'use strict';
        return $.isEmptyObject(this.items);
    },

    /**
     * Returns if the given key is a separator item.
     * 
     * @param {String}
     *            key - the key to be tested.
     * @return {Boolean} true if separator.
     */
    isSeparator: function (key) {
        'use strict';
        return key && key.startsWith('separator_');
    },

    /**
     * Returns if the given key is a title item.
     * 
     * @param {String}
     *            key - the key to be tested.
     * @return {Boolean} true if title.
     */
    isTitle: function (key) {
        'use strict';
        return key && key.startsWith('title_');
    },

    /**
     * Gets the last key.
     * 
     * @return {String} the last key, if any; null otherwise.
     */
    getLastKey: function () {
        'use strict';
        const keys = Object.keys(this.items);
        if (keys.length) {
            return keys[keys.length - 1];
        }
        return null;
    }
};