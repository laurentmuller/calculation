/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
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
            const pattern = '(?:^|;\\s*)' + encodedKey + '\\s*\\=';
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

    /**
     * Constructor
     */
    function Cookiebanner() {
    }

    /**
     * Functions
     */
    Cookiebanner.prototype = {

        /**
         * Create the banner div.
         */
        createBanner: function (settings) {
            const $banner = $('<div/>', {
                'id': 'cookie-banner-div',
                'class': settings.bannerClass,
                'css': {
                    'z-index': settings.zindex,
                    'font-size': settings.fontSize,
                    'font-family': settings.fontFamily
                }
            });
            const $message = $('<div/>', {
                'id': 'cookie-banner-message',
                'class': settings.messageClass,
                'text': settings.message,
                'css': {
                    'text-align': settings.textAlign
                }
            });
            if (settings.linkUrl) {
                const $link = $("<a/>", {
                    'id': 'cookie-banner-link',
                    'rel': settings.linkRel,
                    'href': settings.linkUrl,
                    'class': settings.linkClass,
                    'text': settings.linkMessage,
                    'target': settings.linkTarget
                });
                $message.append($link);
            }
            const $close = $("<a/>", {
                'id': 'cookie-banner-close',
                'class': settings.closeClass,
                'text': settings.closeMessage,
                'href': '#'
            });
            $banner.append($message, $close);

            $(settings.appendTo).append($banner);
        },

        /**
         * Remove the banner div.
         */
        removeBanner: function () {
            $('#cookie-banner-div').fadeOut(400, function () {
                $(this).remove();
            });
        },

        /**
         * Gets the default options.
         */
        defaults: function () {
            return {
                cookieName: 'POLICY-ACCEPTED',
                cookiePath: '/',
                cookieDomain: null,
                cookieSecure: false,
                cookieExpire: Infinity,
                cookieSamesite: 'lax',

                message: 'This website uses cookies to provide you a better navigation experience. By closing this banner you agree to the use of cookies.',
                linkMessage: 'Learn more',
                closeMessage: 'Close',

                bannerClass: 'd-flex d-print-none fixed-bottom bg-secondary text-white p-1',
                messageClass: 'flex-fill',
                linkClass: 'ml-1 text-white-50',
                closeClass: 'text-white align-self-center ml-2',

                linkTarget: '_blank',
                linkUrl: 'http://aboutcookies.org',
                linkRel: 'noopener noreferrer',

                fontSize: '0.8rem',
                //fontFamily: 'var(--font-family-sans-serif)',
                textAlign: 'center',
                appendTo: 'body',
                zindex: 1000
            };
        },

        /**
         * Initialize the cookie banner.
         */
        init: function (options) {
            // merge settings
            const settings = $.extend({}, this.defaults(), options);

            // check cookie
            if (!Cookies.has(settings.cookieName)) {
                // create banner
                this.createBanner(settings);

                // bind event
                const that = this;
                $('#cookie-banner-close').on('click', function (e) {
                    e.preventDefault();
                    if (!Cookies.has(settings.cookieName)) {
                        Cookies.set(settings.cookieName, 1, settings.cookieExpire, settings.cookiePath, settings.cookieDomain, settings.cookieSecure, settings.cookieSamesite);
                    }
                    that.removeBanner();
                });
            }
        }
    };

    // initialized?
    if (!$.cookiebanner) {
        // find script
        const scripts = document.getElementsByTagName('script');
        for (var i = 0, len = scripts.length; i < len; i++) {
            if ('cookiebanner' === scripts[i].id) {
                // initialize
                const script = scripts[i];
                const options = $(script).data();
                $.cookiebanner = new Cookiebanner();
                $.cookiebanner.init(options);
                break;
            }
        }
    }

})(jQuery);
