/**
 * -------------- URLSearchParams Extensions --------------
 */

/*eslint no-extend-native: ["error", { "exceptions": ["URLSearchParams"] }]*/
Object.defineProperty(URLSearchParams.prototype, 'length', {
    /**
     * Returns the number of parameters.
     * @returns {number} the number of parameters.
     */
    value: function () {
        'use strict';
        return this.size;
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["URLSearchParams"] }]*/
Object.defineProperty(URLSearchParams.prototype, 'toQuery', {
    /**
     * Returns the parameters query.
     * @returns {string} the query parameters.
     */
    value: function () {
        'use strict';
        return this.size ? '?' + this.toString() : '';
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["URLSearchParams"] }]*/
Object.defineProperty(URLSearchParams.prototype, 'getOrDefault', {
    /**
     * Returns the parameter value or the default value if none.
     * @param {string} name the parameter name.
     * @param {?string} [defaultValue] the default value if the parameter is not found.
     * @returns {?string} the parameter value, if found; the default value otherwise.
     */
    value: function (name, defaultValue) {
        'use strict';
        return this.has(name) ? this.get(name) : defaultValue;
    }
});

/*eslint no-extend-native: ["error", { "exceptions": ["URLSearchParams"] }]*/
Object.defineProperty(URLSearchParams.prototype, 'getIntOrDefault', {
    /**
     * Returns the parameter value, as integer, or the default value if none.
     * @param {string} name the parameter name.
     * @param {number} defaultValue the default value if the parameter is not found.
     * @returns {number} the parameter value, if found; the default value otherwise.
     */
    value: function (name, defaultValue) {
        'use strict';
        if (this.has(name)) {
            const value = Number.parseInt(this.get(name), 10);
            return Number.isInteger(value) ? value : defaultValue;
        }
        return defaultValue;
    }
});
