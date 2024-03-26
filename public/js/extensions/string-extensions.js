/**! compression tag for ftp-deployment */

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
        if (String.prototype.normalize) {
            return this.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return this.trim().toLowerCase().replace(/[\u0300-\u036f]/g, '');
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'indexOfIgnoreCase', {
    /**
     * Returns the index of the first occurrence in this string, ignoring case, of the specified value.
     * @param {string} value the value to search for.
     * @param {int} fromIndex the index at which to start the search (optional); the default value is 0.
     * @return {int} the index of the first occurrence of searchValue, or -1 if not found.
     */
    value: function (value, fromIndex) {
        'use strict';
        return this.toLowerCase().indexOf(value.toLowerCase(), fromIndex);
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'equalsIgnoreCase', {
    /**
     * Check if the given value is equal to this string, ignoring case.
     * @param {string} value the value to compare with.
     * @return {boolean} true if equal, ignoring case.
     */
    value: function (value) {
        'use strict';
        // const string1 = this.toLocaleLowerCase();
        // const string2 = value.toLocaleLowerCase();
        // return string1 === string2;
        const regexp = RegExp('^' + this.replace(/[.\\+*?\[\^\]$(){}=!<>|:-]/g, '\\$&') + '$', 'i');
        return regexp.test(value);
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'dasherize', {
    /**
     * Replace a camel cased to a dashed ('isAnEntry' > 'is-an-entry').
     * @return {string}
     */
    value: function () {
        'use strict';
        // first character is always in lower case
        const trim = this.trim();
        const first = trim.substring(0, 1).toLowerCase();
        const other = trim.substring(1);
        return first + other.replace(/([A-Z])/g, '-$1');
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'camelize', {
    /**
     * Replace dashed to camel cased ('is-an-entry' > 'isAnEntry').
     * @return {string}
     */
    value: function () {
        'use strict';
        return this.trim().replace(/[-_\s]+(.)?/g, function (_match, c) {
            return c ? c.toUpperCase() : '';
        });
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'toBool', {
    /**
     * Converts this string to a boolean value
     * @return {boolean} the boolean value.
     */
    value: function () {
        'use strict';
        try {
            return !!JSON.parse(this.toLowerCase());
        } catch (e) {
            return false;
        }
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'format', {
    /**
     * Format a string. Example: 'First {0}. Second {1}'.format('ASP', 'PHP');
     * @return {string} this formatted string.
     */
    value: function () {
        'use strict';
        let formatted = this;
        for (let i = 0; i < arguments.length; i++) {
            const flags = 'gi';
            const pattern = `\\{${i}\\}`;
            const regexp = new RegExp(pattern, flags);
            formatted = formatted.replace(regexp, arguments[i]);
        }
        return formatted;
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'startsWithIgnoreCase', {
    /**
     * Returns if this string start with the given value, ignoring case
     * consideration.
     * @param {String} s the string to compare to.
     * @return {boolean}
     */
    value: function (s) {
        'use strict';
        const regex = new RegExp('^' + s, 'i');
        return regex.test(this);
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["String"] }]*/
Object.defineProperty(String.prototype, 'ucFirst', {
    /**
     * Return this string with the first character uppercase.
     * @return {string}
     */
    value: function () {
        'use strict';
        return this.charAt(0).toUpperCase() + this.slice(1);
    }
});
