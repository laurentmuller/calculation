/**
 * Cookie utility.
 */
(() => {
    'use strict';
    window.Cookie = {
        /**
         * Sets a cookie value.
         *
         * @param {string} key - the cookie key.
         * @param {string|number|boolean} value - the cookie value.
         * @param {string} path - the cookie path.
         * @param {string|Date} [expires] - the expired date or null to use default (+1 year).
         * @param {string} samesite - the same site behavior ('strict', 'lax' or 'none').
         */
        setValue: function (key, value, path = '/', expires = null, samesite = 'lax') {
            if (!expires) {
                expires = new Date();
                expires.setFullYear(expires.getFullYear() + 1);
            }
            if (expires instanceof Date) {
                expires = expires.toUTCString();
            }
            if (typeof value === 'boolean' || typeof value === 'number') {
                value = JSON.stringify(value);
            }
            let cookie = `${key.toUpperCase()}=${value};expires=${expires};path=${path};samesite=${samesite};`;
            if (window.location.protocol === 'https:') {
                cookie += 'secure';
            }
            document.cookie = cookie;
        },

        /**
         * Clears a cookie value.
         *
         * @param {string} key - the cookie key.
         * @param {string} path - the cookie path.
         * @param {string} samesite - the same site behavior ('strict', 'lax' or 'none').
         */
        clearValue: function (key, path = '/', samesite = 'lax') {
            window.Cookie.setValue(key, '', path, new Date(0), samesite);
        },

        /**
         * Gets a cookie value.
         *
         * @param {string} key the cookie key.
         * @param {string} [defaultValue] the default value.
         *
         * @return {string} the cookie value, if found; the default value otherwise.
         */
        getValue: function (key, defaultValue) {
            key = `${key.toUpperCase()}=`;
            /* stylelint-disable-next-line */
            const decodedCookie = decodeURIComponent(document.cookie);
            const entries = decodedCookie.split(';');
            for (let i = 0; i < entries.length; i++) {
                const entry = entries[i].trim();
                if (entry.startsWith(key)) {
                    return entry.substring(key.length);
                }
            }
            return defaultValue;
        },

        /**
         * Gets a cookie value as integer.
         *
         * @param {string} key - the cookie key.
         * @param {number} defaultValue - the default value.
         *
         * @return {number} the cookie value, if found; the default value otherwise.
         */
        getInteger: function (key, defaultValue = 0) {
            const value = this.getValue(key, defaultValue.toString(10));
            const number = Number.parseInt(value, 10);
            return Number.isNaN(number) ? defaultValue : number;
        },

        /**
         * Gets a cookie value as boolean.
         *
         * @param {string} key - the cookie key.
         * @param {boolean} defaultValue - the default value.
         *
         * @return {boolean} the cookie value, if found; the default value otherwise.
         */
        getBoolean: function (key, defaultValue = false) {
            const value = this.getValue(key, JSON.stringify(defaultValue)).toLowerCase();
            if (value === 'true') {
                return true;
            }
            if (value === 'false') {
                return false;
            }
            return defaultValue;
        }
    };
})();
