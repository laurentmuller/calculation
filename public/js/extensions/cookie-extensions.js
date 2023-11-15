/**! compression tag for ftp-deployment */

'use strict';

/**
 * Sets a cookie value.
 *
 * @param {string} key the cookie key.
 * @param {string|number|boolean} value the cookie value.
 * @param {string} path the cookie path.
 * @param {string|Date} [expires] the expires date.
 * @param {string} samesite the same site behavior.
 * @param {boolean} secure true if secure.
 */
function setCookie(key, value, path = '/', expires = null, samesite = 'lax', secure = true) {
    if (!expires) {
        expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
    }
    if (expires instanceof Date) {
        expires = expires.toUTCString();
    }
    let cookie = `${key}=${JSON.stringify(value)};expires=${expires};path=${path};samesite=${samesite};`;
    if (secure) {
        cookie += 'secure';
    }
    document.cookie = cookie;
}

/**
 * Gets a cookie value.
 *
 * @param {string} key the cookie key.
 * @param {string} [defaultValue] the default value.
 *
 * @return {string} the cookie value, if found; the default value otherwise.
 */
function getCookie(key, defaultValue) {
    key = `${key}=`;
    const decodedCookie = decodeURIComponent(document.cookie);
    const entries = decodedCookie.split(';');
    for (let i = 0; i < entries.length; i++) {
        const entry = entries[i].trim();
        if (entry.startsWith(key)) {
            return entry.substring(key.length);
        }
    }
    return defaultValue;
}

/**
 * Gets a cookie value as integer.
 *
 * @param {string} key the cookie key.
 * @param {number} defaultValue the default value.
 *
 * @return {number} the cookie value, if found; the default value otherwise.
 */
function getCookieInt(key, defaultValue = 0) {
    const str = getCookie(key, defaultValue.toString(10));
    const value = Number.parseInt(str, 10);
    return Number.isNaN(value) ? defaultValue : value;
}
