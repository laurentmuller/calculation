/**! compression tag for ftp-deployment */

(() => {
    'use strict';

    const THEME_AUTO = 'auto';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    const COOKIE_NAME = "THEME";

    const getCookieValue = function () {
        const name = COOKIE_NAME + "=";
        const decodedCookie = decodeURIComponent(document.cookie);
        const entries = decodedCookie.split(';');
        for (let i = 0; i < entries.length; i++) {
            const entry = entries[i].trimStart();
            if (entry.indexOf(name) === 0) {
                return entry.substring(name.length, entry.length);
            }
        }
        return '';
    };

    const setCookieValue = function (value, days = 365) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 3600 * 1000));
        const path = document.body.dataset.cookiePath || '/';
        let entry = COOKIE_NAME + '=' + encodeURIComponent(value) + ';';
        entry += 'expires=' + date.toUTCString() + ';';
        entry += 'path=' + path + ';';
        entry += 'samesite=lax;';
        document.cookie = entry;
    };

    const storedTheme = getCookieValue();

    const isMediaDark = () => {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    };

    const getPreferredTheme = () => {
        if (storedTheme) {
            return storedTheme;
        }
        return isMediaDark() ? THEME_DARK : THEME_LIGHT;
    };

    const setTheme = function (theme) {
        if (theme === THEME_AUTO && isMediaDark()) {
            document.body.setAttribute('data-bs-theme', THEME_DARK);
        } else {
            document.body.setAttribute('data-bs-theme', theme);
        }
    };

    setTheme(getPreferredTheme());

    const showActiveTheme = (theme, notify = false) => {
        const themeSwitchers = document.querySelectorAll('.theme-switcher');
        if (themeSwitchers.length === 0) {
            return;
        }

        const selector = `[data-bs-theme-value="${theme}"]`;
        const link = document.querySelector(selector);
        const linkIcon = link.querySelector('.theme-icon');
        const linkText = link.querySelector('.theme-text');

        themeSwitchers.forEach((element) => {
            element.querySelector('.theme-icon').textContent = linkIcon.textContent;
            element.querySelector('.theme-text').textContent = linkText.textContent;
        });

        document.querySelectorAll('[data-bs-theme-value]').forEach((element) => {
            element.classList.remove('dropdown-item-checked', 'disabled');
        });
        document.querySelectorAll(selector).forEach((element) => {
            element.classList.add('dropdown-item-checked', 'disabled');
        });

        if (notify) {
            window.dispatchEvent(new CustomEvent('theme', {
                view: window,
                bubbles: true,
                cancelable: false,
                detail: theme
            }));
            const themeSwitcher = document.querySelector('.theme-switcher');
            themeSwitcher.dispatchEvent(new MouseEvent('click', {
                view: window,
                bubbles: true,
                cancelable: true,
            }));
        }
    };

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (storedTheme !== THEME_LIGHT || storedTheme !== THEME_DARK) {
            setTheme(getPreferredTheme());
        }
    });

    window.addEventListener('DOMContentLoaded', () => {
        showActiveTheme(getPreferredTheme());
        document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
            element.addEventListener('click', () => {
                const theme = element.getAttribute('data-bs-theme-value');
                if (theme !== getCookieValue()) {
                    setCookieValue(theme);
                    setTheme(theme);
                }
                showActiveTheme(theme, true);
            });
        });
    });
})();
