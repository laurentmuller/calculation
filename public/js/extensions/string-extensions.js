/**! compression tag for ftp-deployment */

/**
 * -------------- String Extensions --------------
 */

/**
 * Clean this string.
 *
 * @return {string} this clean string.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.clean = function () {
    'use strict';
    if (String.prototype.normalize) {
        return this.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    return this.trim().toLowerCase().replace(/[\u0300-\u036f]/g, '');
};

/**
 * Returns the index of the first occurrence in this string, ignoring case, of
 * the specified value.
 *
 * @param {string} value - the value to search for.
 * @param {int} fromIndex - the index at which to start the search (optional); the
 *            default value is 0.
 * @return {int} the index of the first occurrence of searchValue, or -1 if
 *         not found.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.indexOfIgnoreCase = function (value, fromIndex) {
    'use strict';
    return this.toLowerCase().indexOf(value.toLowerCase(), fromIndex);
};

/**
 * Check if the given value is equal to this string, ignoring case.
 *
 * @param {string} value - the value to compare with.
 * @return {boolean} true if equal, ignoring case.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.equalsIgnoreCase = function (value) {
    'use strict';
    const regexp = RegExp('^' + this.replace(/[.\\+*?\[\^\]$(){}=!<>|:-]/g, '\\$&') + '$', 'i');
    return regexp.test(value);

    // const string1 = this.toLocaleLowerCase();
    // const string2 = value.toLocaleLowerCase();
    // return string1 === string2;
};

/**
 * Replace camel cased to dashed ('isAnEntry' => 'is-an-entry').
 *
 * @return {string} this dashed string.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.dasherize = function () {
    'use strict';
    // first character is always in lower case
    const trim = this.trim();
    const first = trim.substring(0, 1).toLowerCase();
    const other = trim.substring(1);
    return first + other.replace(/([A-Z])/g, '-$1');
};

/**
 * Replace dashed to camel cased ('is-an-entry' => 'isAnEntry').
 *
 * @return {string} this camelized string.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.camelize = function () {
    'use strict';
    return this.trim().replace(/[-_\s]+(.)?/g, function (_match, c) {
        return c ? c.toUpperCase() : '';
    });
};

/**
 * Converts this string to a boolean value
 *
 * @return {boolean} the boolean value.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.toBool = function () {
    'use strict';
    try {
        return !!JSON.parse(this.toLowerCase());
    } catch (e) {
        return false;
    }
};

/**
 * Format a string. Example:
 * <p>
 * 'First {0}. Second {1}'.format('ASP', 'PHP');
 * </p>
 *
 * @return {string} this formatted string.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.format = function () {
    'use strict';
    let formatted = this;
    for (let i = 0; i < arguments.length; i++) {
        const pattern = '\\{' + i + '\\}';
        const flags = 'gi';
        const regexp = new RegExp(pattern, flags);
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};

/**
 * Returns if this string start with the given value, ignoring case
 * consideration.
 *
 * @param {String}
 *            s - the string to compare to.
 * @return {boolean} true if matched.
 */
/* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */
String.prototype.startsWithIgnoreCase = function (s) {
    'use strict';
    return this.match(new RegExp('^' + s, 'i'));
};
