/**! compression tag for ftp-deployment */

(() => {
    'use strict';

    /**
     * The auto theme.
     * @type {string}
     */
    const THEME_AUTO = 'auto';

    /**
     * The light theme.
     * @type {string}
     */
    const THEME_LIGHT = 'light';

    /**
     * The dark theme.
     * @type {string}
     */
    const THEME_DARK = 'dark';

    // const target = document.querySelector('.theme-switcher.nav-link-toggle');
    // const setAttribute = target.setAttribute;
    // target.setAttribute = (key, value) => {
    //     window.console.log(`${key}=${value}`);
    //     setAttribute.call(target, key, value);
    // };

    /**
     * Gets the stored theme.
     * @return {string|null} the stored theme.
     */
    const getStoredTheme = () => localStorage.getItem('theme');

    /**
     * Sets the stored theme.
     * @param {string} theme - the theme to store.
     */
    const setStoredTheme = theme => localStorage.setItem('theme', theme);

    /**
     * Return a value indicating if the preferred color scheme is dark.
     * @return {boolean}
     */
    const isPreferredDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

        /**
     * Gets the preferred theme.
     * @return {string} the preferred theme.
     */
    const getPreferredTheme = () => {
        const storedTheme = getStoredTheme();
        if (storedTheme) {
            return storedTheme;
        }
        return isPreferredDark() ? THEME_DARK : THEME_LIGHT;
    };

    /**
     * Sets the theme.
     * @param {string} theme - the theme to apply.
     */
    const setTheme = theme => {
        if (theme === THEME_AUTO) {
            theme = isPreferredDark() ? THEME_DARK : THEME_LIGHT;
        }
        document.documentElement.setAttribute('data-bs-theme', theme);
    };

    setTheme(getPreferredTheme());

    /**
     * Show the active theme and update UI.
     * @param {string} theme - the theme to apply.
     * @param {boolean} focus - true to set focus of the selected item.
     */
    const showActiveTheme = (theme, focus = false) => {
        // remove check icon
        document.querySelectorAll('[data-theme-value]').forEach(element => {
            element.classList.remove('dropdown-item-checked');
        });

        // update
        document.querySelectorAll('.theme-switcher').forEach(themeSwitcher => {
            //source
            const menu = themeSwitcher.nextElementSibling;
            const btnToActive = menu.querySelector(`[data-theme-value="${theme}"]`);
            const iconToActive = btnToActive.querySelector('.theme-icon');
            const textToActive = btnToActive.querySelector('.theme-text');

            // add check icon
            btnToActive.classList.add('dropdown-item-checked');

            // target
            const activeThemeIcon = themeSwitcher.querySelector('.theme-icon-active');
            const activeThemeText = themeSwitcher.querySelector('.theme-text-active');
            activeThemeIcon.innerHTML = iconToActive.innerHTML;
            activeThemeText.textContent = textToActive.textContent;

            // collapse menu
            if (themeSwitcher.classList.contains('nav-link-toggle') && themeSwitcher.getAttribute('aria-expanded') === 'true') {
                themeSwitcher.click();
            }
            if (focus) {
                themeSwitcher.focus();
            }
        });
    };

    /**
     * Handle the prefer color scheme change.
     */
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const storedTheme = getStoredTheme();
        if (storedTheme !== THEME_LIGHT && storedTheme !== THEME_DARK) {
            setTheme(getPreferredTheme());
        }
    });

    /**
     * Handle the content loaded event.
     */
    window.addEventListener('DOMContentLoaded', () => {
        showActiveTheme(getPreferredTheme());
        document.querySelectorAll('[data-theme-value]')
            .forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const theme = toggle.getAttribute('data-theme-value');
                    setStoredTheme(theme);
                    setTheme(theme);
                    showActiveTheme(theme, true);
                });
            });
    });
})();
