/**! compression tag for ftp-deployment */

/* globals URLSearchParams */

/**
 * -------------- URLSearchParams Extensions --------------
 */

/**
 * Returns the number of parameters.
 *
 * @returns {int} the parameters length.
 */
URLSearchParams.prototype.length = function () {
    'use strict';
    return Array.from(this).length;
};

/**
 * Returns the parameters query.
 *
 * @returns {string} the parameters query.
 */
URLSearchParams.prototype.toQuery = function () {
    'use strict';
    return this.length() ? '?' + this.toString() : '';
};

/**
 * Returns the parameter value or the default value if none.
 *
 * @param {string} name -the parameter name.
 * @param {any} defaultValue - the default value if the parameter is not found.
 *
 * @returns {any} the parameter value, if found; the default value otherwise.
 */
URLSearchParams.prototype.getOrDefault = function (name, defaultValue) {
    'use strict';
    if (!this.has(name)) {
        return defaultValue;
    }
    return this.get(name);
};

/**
 * Returns the parameter value, as integer, or the default value if none.
 *
 * @param {string} name -the parameter name.
 * @param {int} defaultValue - the default value if the parameter is not found.
 *
 * @returns {int} the parameter value, if found; the default value otherwise.
 */
URLSearchParams.prototype.getIntOrDefault = function (name, defaultValue) {
    'use strict';
    if (!this.has(name)) {
        return defaultValue;
    }
    const value = Number.parseInt(this.get(name), 10);
    return Number.isInteger(value) ? value : defaultValue;
};
