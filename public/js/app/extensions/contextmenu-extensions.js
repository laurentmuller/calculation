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
    this.entries = {};
    this.sepIndex = 0;
    this.entryIndex = 0;
};

// ------------------------
// Public API
// ------------------------
MenuBuilder.prototype = {

    addEntry: function ($link, icon) {
        'use strict';

        const key = 'entry_' + this.entryIndex++;
        this.entries[key] = {
            link: $link,
            name: $link.text().trim(),
            icon: icon || $link.findIcon()
        };
        return this;
    },

    addSeparator: function () {
        'use strict';

        // entries?
        if (this.isEmpty()) {
            return this;
        }

        // last is already a separator?
        const keys = Object.keys(this.entries);
        if (this.isSeparator(keys[keys.length - 1])) {
            return this;
        }

        // add
        const key = 'separator_' + this.sepIndex++;
        this.entries[key] = {
            'type': 'cm_separator'
        };
        return this;
    },

    getEntries: function () {
        'use strict';

        // entries?
        if (this.isEmpty()) {
            return {};
        }

        // remove last separator (if any)
        const keys = Object.keys(this.entries);
        const lastKey = keys[keys.length - 1];
        if (this.isSeparator(lastKey)) {
            delete this.entries[lastKey];
        }

        return this.entries;
    },

    isEmpty: function () {
        'use strict';
        return $.isEmptyObject(this.entries);
    },

    isSeparator: function (key) {
        'use strict';
        return key.startsWith('separator_');
    }
};