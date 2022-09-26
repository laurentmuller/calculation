/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // Cookies public class definition
    // ------------------------------------
    const Cookies = {
        /**
         * Gets the cookie value.
         *
         * @param {string} key - the key to get cookie value for.
         * @return [{string}] the value, if found; null otherwise.
         */
        get: function (key) {
            const encodedKey = encodeURIComponent(key).replace(/[-.+*]/g, '\\$&');
            const pattern = '(?:(?:^|.*;)\\s*' + encodedKey + '\\s*\\=\\s*([^;]*).*$)|^.*$';
            const regex = new RegExp(pattern);
            return decodeURIComponent(document.cookie.replace(regex, '$1')) || null;
        },

        /**
         * Sets the cookie value.
         *
         * @param {string} key - the key to set cookie value for.
         * @param {string|number| boolean} value - the cookie value.
         * @param {Number|String|Date} [end] - the expired date.
         * @param {string} [path] - the cookie path.
         * @param {string} [domain] - the cookie domain.
         * @param {boolean} [secure] - true to secure cookie.
         * @param {boolean} [samesite] - true if same site.
         * @return {boolean} true if set; false otherwise.
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
         *
         * @param {string} key - the key to check cookie existence for.
         * @return {boolean} true if cookie exists; false otherwise.
         */
        has: function (key) {
            const encodedKey = encodeURIComponent(key).replace(/[-.+*]/g, '\\$&');
            const pattern = '(?:^|;\\s*)' + encodedKey + '\\s*\\=';
            const regex = new RegExp(pattern);
            return regex.test(document.cookie);
        },

        /**
         * Remove a cookie
         *
         * @param {string} key - the key to set cookie value for.
         * @param {string} [path] - the cookie path.
         * @param {string} [domain] - the cookie domain.
         * @return {boolean} true if removed; false otherwise.
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

    // ------------------------------------
    // CookieBanner public class definition
    // ------------------------------------
    const CookieBanner = class {
        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {Object|string} options - the plugin options.
         */
        constructor(options) {
            this.options = $.extend({}, CookieBanner.DEFAULTS, options);
            this._init();
        }

        /**
         * Update the cookie to accept policy.
         *
         * @return {CookieBanner} this instance for chaining.
         */
        accept() {
            const options = this.options;
            if (!Cookies.has(options.cookieName)) {
                Cookies.set(options.cookieName, 1, options.cookieExpire, options.cookiePath, options.cookieDomain, options.cookieSecure, options.cookieSameSite);
            }
            return this;
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize the plugin.
         * @private
         */
        _init() {
            const that = this;
            const options = that.options;
            if (!Cookies.has(options.cookieName) && options.displayBanner) {
                that._createBanner(options);
                $('#cookie-banner-close').on('click', function (e) {
                    e.preventDefault();
                    that.accept()._removeBanner();
                });
            }
        }

        /**
         * Create the banner element.
         *
         * @param {Object} options - the plugin options.
         * @private
         */
        _createBanner(options) {
            const $banner = $('<div/>', {
                'id': 'cookie-banner-div',
                'class': options.bannerClass,
                'css': {
                    'z-index': options.zIndex,
                    'font-size': options.fontSize,
                    'font-family': options.fontFamily,
                    // 'visibility': visibility
                }
            });
            const $message = $('<div/>', {
                'id': 'cookie-banner-message',
                'class': options.messageClass,
                'text': options.message,
                'css': {
                    'text-align': options.textAlign
                }
            });
            if (options.linkUrl) {
                const $link = $('<a/>', {
                    'id': 'cookie-banner-link',
                    'rel': options.linkRel,
                    'href': options.linkUrl,
                    'class': options.linkClass,
                    'text': options.linkMessage,
                    'title': options.linkTitle,
                    'target': options.linkTarget
                });
                if (options.linkToggleClass) {
                    $link.on('hover', function () {
                        $(this).toggleClass(options.linkToggleClass);
                    });
                }
                $message.append($link);
            }
            const $close = $('<a/>', {
                'id': 'cookie-banner-close',
                'class': options.closeClass,
                'text': options.closeMessage,
                'title': options.closeTitle,
                'href': '#'
            });
            $banner.append($message, $close);

            $(options.appendTo).append($banner);
        }

        /**
         * Remove the banner element.
         * @private
         */
        _removeBanner() {
            $('#cookie-banner-div').fadeOut(400, function () {
                $(this).remove();
            });
        }
    };

    /**
     * Default options
     */
    CookieBanner.DEFAULTS = {
        cookieName: 'POLICY-ACCEPTED',
        cookiePath: '/',
        cookieDomain: null,
        cookieSecure: 'true',
        cookieExpire: Infinity,
        cookieSameSite: 'lax',

        displayBanner: true,

        message: 'This website uses cookies to provide you a better experience. By closing this banner you agree to the use of cookies.',
        linkMessage: 'Learn more',
        linkTitle: null,
        closeMessage: 'Close',
        closeTitle: null,

        bannerClass: 'd-flex d-print-none fixed-bottom bg-secondary text-white p-1',
        messageClass: 'flex-fill',
        linkClass: 'mx-1 text-white-50',
        linkToggleClass: 'text-white-50 text-white',
        closeClass: 'text-white mx-2',

        linkTarget: '_blank',
        linkUrl: 'https://www.aboutcookies.org/',
        linkRel: 'noopener noreferrer',

        fontSize: '0.75rem',
        textAlign: 'left',
        appendTo: 'body',
        zIndex: 1031
    };


    // initialized?
    if (!$.cookiebanner) {
        // find script
        const scripts = document.getElementsByTagName('script');
        for (let i = 0, len = scripts.length; i < len; i++) {
            if ('cookie-banner' === scripts[i].id) {
                // initialize
                const $script = $(scripts[i]);
                const options = $script.data();
                $script.removeDataAttributes();
                $.cookiebanner = new CookieBanner(options);
                break;
            }
        }
    }
}(jQuery));
