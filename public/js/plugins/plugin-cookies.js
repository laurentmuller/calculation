/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function () {
    'use strict';

    /**
     * Cookies functions
     */
    const Cookies = { 

        /**
         * Gets the cookie value.
         */
        get: function (key) {
            const encodedKey = encodeURIComponent(key).replace(/[-.+*]/g, '\\$&');
            const pattern = '(?:(?:^|.*;)\\s*' + encodedKey + '\\s*\\=\\s*([^;]*).*$)|^.*$';
            const regex = new RegExp(pattern);
            return decodeURIComponent(document.cookie.replace(regex, '$1')) || null;
        },

        /**
         * Sets the cookie value.
         */
        set: function (key, value, end, path, domain, secure, samesite) {
            // check key
            if (!key || /^(?:expires|max-age|path|domain|secure|samesite)$/i.test(key)) {
                return false;
            }

            // build
            let cookie = encodeURIComponent(key) + '=' + encodeURIComponent(value);
            if (domain) {
                cookie += '; domain=' + domain;
            }
            if (path) {
                cookie += '; path=' + path;
            }
            if (secure) {
                cookie += '; secure=' + secure;
            }
            if (samesite) {
                cookie += '; samesite=' + samesite;
            }
            if (end) {
                switch (end.constructor) {
                case Number:
                    if (end === Infinity) {
                        cookie += '; expires=Fri, 31 Dec 9999 23:59:59 GMT;';
                    } else {
                        cookie += '; max-age=' + end;
                    }
                    break;
                case String:
                    cookie += '; expires=' + end;
                    break;
                case Date:
                    cookie += '; expires=' + end.toUTCString();
                    break;
                }
            }
            document.cookie = cookie;
            return true;
        },

        /**
         * Checks if a cookie exists.
         */
        has: function (key) {
            const encodedKey = encodeURIComponent(key).replace(/[-.+*]/g, '\\$&');
            const pattern = '(?:^|;\\s*)' + encodedKey  + '\\s*\\=';
            const regex = new RegExp(pattern);
            return regex.test(document.cookie);
        },

        /**
         * Remove a cookie
         */
        remove: function (key, path, domain) {
            // check key
            if (!key || !this.has(key)) {
                return false;
            }

            // build
            let cookie = encodeURIComponent(key) + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT';
            if (domain) {
                cookie += '; domain=' + domain;
            }
            if (path) {
                cookie += '; path=' + path;
            }
            document.cookie = cookie;
            return true;
        }
    };

}());
