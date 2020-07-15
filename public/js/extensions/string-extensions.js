/**! compression tag for ftp-deployment */

/**
 * -------------- String Extensions --------------
 */

/**
 * Clean this string.
 * 
 * @return {string} this clean string.
 */
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
 * @param {string}
 *            searchvalue - the value to search for.
 * @param {integer}
 *            fromIndex - the index at which to start the search (optional); the
 *            default value is 0.
 * @return {integer} the index of the first occurrence of searchValue, or -1 if
 *         not found.
 */
String.prototype.indexOfIgnoreCase = function (searchvalue, fromIndex) {
    'use strict';
    return this.toLowerCase().indexOf(searchvalue.toLowerCase(), fromIndex);
};

/**
 * Check if the given value is equal to this string, ignoring case.
 * 
 * @param {string}
 *            value - the value to compare with.
 * @return {boolean} true if equal, ignoring case.
 */
String.prototype.equalsIgnoreCase = function (value) {
    'use strict';
    return this === value || this.toLowerCase() === value.toLowerCase();
};

/**
 * Replace camel cased to dashed ('isAnEntry' => 'is-an-entry').
 * 
 * @return {string} this dashed string.
 */
String.prototype.dasherize = function () {
    'use strict';
    // first character is always in lower case
    const trim = this.trim();
    const first = trim.substring(0, 1).toLowerCase();
    const other = trim.substring(1);
    return first + other.replace(/([A-Z])/g, '-$1');
    // .replace(/[-_\s]+/g, '-').toLowerCase();
};

/**
 * Replace dashed to camel cased ('is-an-entry' => 'isAnEntry').
 * 
 * @return {string} this camelized string.
 */
String.prototype.camelize = function () {
    'use strict';
    return this.trim().replace(/[-_\s]+(.)?/g, function (match, c) {
        return c ? c.toUpperCase() : '';
    });
};

/**
 * Converts this string to a boolean value
 * 
 * @return {boolean} the boolean value.
 */
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
String.prototype.format = function () {
    'use strict';
    var formatted = this;
    for (var i = 0; i < arguments.length; i++) {
        const pattern = '\\{' + i + '\\}';
        const flags = 'gi';
        const regexp = new RegExp(pattern, flags);
        formatted = formatted.replace(regexp, arguments[i]);
    }
    return formatted;
};