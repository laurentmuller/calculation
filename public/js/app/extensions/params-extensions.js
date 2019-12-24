/**! compression tag for ftp-deployment */

/* globals URLSearchParams */

/**
 * -------------- URLSearchParams Extensions --------------
 */

/**
 * Returns the parameters length.
 */
URLSearchParams.prototype.length = function () {
    'use strict';
    return Array.from(this).length;
};

/**
 * Returns the parameters query.
 */
URLSearchParams.prototype.toQuery = function () {
    'use strict';
    return this.length() ? '?' + this.toString() : '';
};

/**
 * Returns the parameter value or the default value if none.
 * 
 * @param {string}
 *            name -the parameter name.
 * @param {any}
 *            defaultValue - the default value if the parameter is not found.
 */
URLSearchParams.prototype.getOrDefault = function (name, defaultValue) {
    'use strict';
    return this.has(name) ? this.get(name) : defaultValue;
};