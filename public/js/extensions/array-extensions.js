/**! compression tag for ftp-deployment */

/**
 * -------------- Array Extensions --------------
 */

/**
 * Returns a random element that is different from the given last index (if
 * any).
 * 
 * @param {integer}
 *            lastIndex - the last selected index, if any; null otherwise.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["Array"] }] */
Array.prototype.randomElement = function (lastIndex) {
    'use strict';

    const len = this.length;
    switch (len) {
    case 0:
        return null;
    case 1:
        return this[0];
    default:
        let index = 0;
        do {
            index = Math.floor(Math.random() * len);
        } while (this[index] === lastIndex);

        return this[index];
    }
};

/**
 * Returns the last element or null if empty.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["Array"] }] */
Array.prototype.last = function () {
    'use strict';

    return this.length ? this[this.length - 1] : null;
};

/**
 * Returns unique elements.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["Array"] }] */
Array.prototype.unique = function () {
    'use strict';

    return this.filter(function (value, index, self) {
        return self.indexOf(value) === index;
    });
};
