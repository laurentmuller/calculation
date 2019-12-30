/**! compression tag for ftp-deployment */

/**
 * -------------- JQuery extensions --------------
 */
$.fn.findIcon = function () {
    'use strict';
    const $icon = $(this).find('i');
    if ($icon.length) {
        return $icon.attr('class');
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
    this.sepIndex = 0;
    this.entryIndex = 0;
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

        const key = 'entry_' + this.entryIndex++;
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

        // items?
        if (this.isEmpty()) {
            return this;
        }

        // last is already a separator?
        const keys = Object.keys(this.items);
        if (this.isSeparator(keys[keys.length - 1])) {
            return this;
        }

        // add
        const key = 'separator_' + this.sepIndex++;
        this.items[key] = {
            'type': 'cm_separator'
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

        // items?
        if (this.isEmpty()) {
            return {};
        }

        let items = this.items;

        // remove last separator (if any)
        const keys = Object.keys(items);
        const lastKey = keys[keys.length - 1];
        if (this.isSeparator(lastKey)) {
            delete items[lastKey];
        }

        return items;
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
        return key.startsWith('separator_');
    }
};