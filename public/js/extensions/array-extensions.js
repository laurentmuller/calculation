/**! compression tag for ftp-deployment */

/**
 * -------------- Array Extensions --------------
 */

/*eslint no-extend-native: ["error", { "exceptions": ["Array"] }]*/
Object.defineProperty(Array.prototype, 'randomElement', {
    /**
     * Returns a random element that is different from the given index (if any).
     * @template T
     * @param {number} [lastIndex] the last selected index, if any; null otherwise.
     * @return {T|null}
     */
    value: function (lastIndex) {
        'use strict';
        const len = this.length;
        switch (len) {
            case 0:
                return null;
            case 1:
                return this[0];
            default: {
                let index = 0;
                do {
                    index = Math.floor(Math.random() * len);
                } while (this[index] === lastIndex);

                return this[index];
            }
        }
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["Array"] }]*/
Object.defineProperty(Array.prototype, 'last', {
    /**
     * Returns the last element or null if empty.
     * @template T
     * @return {T|null}
     */
    value: function () {
        'use strict';
        return this.length ? this[this.length - 1] : null;
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["Array"] }]*/
Object.defineProperty(Array.prototype, 'unique', {
    /**
     * Returns unique elements.
     * @template T
     * @return {Array.<T>}
     */
    value: function () {
        'use strict';
        return this.filter(function (value, index, self) {
            return self.indexOf(value) === index;
        });
    }
});
