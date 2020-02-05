/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * Cookies functions
     */
    var Cookies = {

        /**
         * Gets the cookie value.
         */
        get: function (key) {
            return decodeURIComponent(document.cookie.replace(new RegExp('(?:(?:^|.*;)\\s*' + encodeURIComponent(key).replace(/[-.+*]/g, '\\$&') + '\\s*\\=\\s*([^;]*).*$)|^.*$'), '$1')) || null;
        },

        /**
         * Sets the cookie value.
         */
        set: function (key, val, end, path, domain, secure, samesite, httponly) {
            // check key
            if (!key || /^(?:expires|max-age|path|domain|secure|samesite|httponly)$/i.test(key)) {
                return false;
            }

            // build
            let cookie = encodeURIComponent(key) + '=' + encodeURIComponent(val);
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
            if (httponly) {
                // cookie += '; httponly';
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
            return (new RegExp('(?:^|;\\s*)' + encodeURIComponent(key).replace(/[-.+*]/g, '\\$&') + '\\s*\\=')).test(document.cookie);
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

})(jQuery);