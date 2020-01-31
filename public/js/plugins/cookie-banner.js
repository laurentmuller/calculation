/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * Utility functions
     */
    var Utils = {

        /**
         * Find a script.
         */
        getScriptById: function (id) {
            const scripts = document.getElementsByTagName('script');
            for (var i = 0, len = scripts.length; i < len; i++) {
                if (id === scripts[i].id) {
                    return scripts[i];
                }
            }
            return null;
        },

        /**
         * Gets the data attribute.
         */
        getDataAttributes: function (script) {
            // dataset?
            if (script.hasOwnProperty('dataset')) {
                return script.dataset;
            }

            // data
            return $(script).data();
        }
    };

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
                //cookie += '; httponly';
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
            const $div = $('<div/>', {
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
            $div.append($message).append($close);
            $(settings.appendTo).append($div);
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
                cookieHttpOnly: true,

                messageClass: 'flex-fill',
                message: 'This website uses cookies to provide you a better navigation experience. By closing this banner you agree to the use of cookies.',
                bannerClass: 'd-flex fixed-bottom p-1 bg-dark text-white',

                linkClass: 'ml-1 text-white-50',
                linkMessage: 'Learn more',
                linkTarget: '_blank',
                linkUrl: 'http://aboutcookies.org',
                linkRel: 'noopener noreferrer',

                closeClass: 'text-white align-self-center ml-2',
                closeMessage: 'Close',

                fontSize: '0.8rem',
                fontFamily: 'var(--font-family-sans-serif)',
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
            var settings = $.extend({}, this.defaults(), options);

            // check cookie
            if (!Cookies.has(settings.cookieName)) {
                // create banner
                this.createBanner(settings);

                // bind event
                const that = this;
                $('#cookie-banner-close').on('click', function (e) {
                    e.preventDefault();
                    if (!Cookies.has(settings.cookieName)) {
                        Cookies.set(settings.cookieName, 1, settings.cookieExpire, settings.cookiePath, settings.cookieDomain, settings.cookieSecure, settings.cookieSamesite, settings.cookieHttpOnly);
                    }
                    that.removeBanner();
                });
            }
        }
    };

    // find cookiebanner script
    const script = Utils.getScriptById('cookiebanner');
    if (script && !$.cookiebanner) {
        const options = $(script).data();
        $.cookiebanner = new Cookiebanner();
        $.cookiebanner.init(options);
    }

})(jQuery);
