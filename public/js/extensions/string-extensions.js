/**
 * -------------- String Extensions --------------
 */

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'clean', {
    /**
     * Clean this string.
     * @return {string} this clean string.
     */
    value: function () {
        'use strict';
        return this.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'indexOfIgnoreCase', {
    /**
     * Returns the index of the first occurrence in this string, ignoring case considerations of the specified value.
     * @param {string} value the value to search for.
     * @param {int} fromIndex the index at which to start the search (optional); the default value is 0.
     * @return {int} the index of the first occurrence, or -1 if not found.
     */
    value: function (value, fromIndex) {
        'use strict';
        const string1 = this.normalize('NFD').toLowerCase();
        const string2 = value.normalize('NFD').toLowerCase();
        return string1.indexOf(string2, fromIndex);
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'equalsIgnoreCase', {
    /**
     * Check if the given value is equal to this string, ignoring case considerations.
     * @param {string} value the value to compare with.
     * @return {boolean} true if equal.
     */
    value: function (value) {
        'use strict';
        const string1 = this.normalize('NFD').toLowerCase();
        const string2 = value.normalize('NFD').toLowerCase();
        return string1 === string2;
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'format', {
    /**
     * Format a string.
     *
     * Example: 'First {0}, Second {1}'.format('ASP', 'PHP');
     * Returns: 'First ASP, Second PHP'
     *
     * @return {string} this formatted string.
     */
    value: function () {
        'use strict';
        let formatted = this;
        for (let i = 0, len = arguments.length; i < len; i++) {
            const regexp = new RegExp(`\\{${i}\\}`, 'gi');
            formatted = formatted.replace(regexp, arguments[i]);
        }
        return formatted;
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'startsWithIgnoreCase', {
    /**
     * Returns if this string starts with the given value, ignoring case
     * consideration.
     * @param {String} s the string to compare to.
     * @return {boolean}
     */
    value: function (s) {
        'use strict';
        const string1 = this.normalize('NFD').toLowerCase();
        const string2 = s.normalize('NFD').toLowerCase();
        return string1.startsWith(string2);
    }
});
