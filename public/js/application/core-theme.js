/**! compression tag for ftp-deployment */

/* global bootstrap */

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

    /**
     * The theme cookie key name.
     * @type {string}
     */
    const THEME_COOKIE_KEY = 'THEME';

    /**
     * The theme chanel name.
     * @type {string}
     */
    const THEME_CHANNEL = 'theme';

    /**
     * The theme changed event name.
     * @type {string}
     */
    const THEME_EVENT_NAME = 'theme_changed';

    /**
     * The match media to get value for or to listen for.
     * @type {string}
     */
    const THEME_MEDIA = '(prefers-color-scheme: dark)';

    /**
     * Gets the stored theme.
     * @return {string} the stored theme.
     */
    const getStoredTheme = () => {
        return window.Cookie.getValue(THEME_COOKIE_KEY, THEME_AUTO);
    };

    /**
     * Sets the stored theme.
     * @param {string} theme - the theme to store.
     */
    const setStoredTheme = (theme) => {
        const path = document.body.dataset.cookiePath || '/';
        window.Cookie.setValue(THEME_COOKIE_KEY, theme, path);
    };

    /**
     * Return a value indicating if the preferred color scheme is dark.
     * @return {boolean} true if dark.
     */
    const isPreferredDark = () => window.matchMedia(THEME_MEDIA).matches;

    /**
     * Gets the preferred theme.
     * @return {string} the preferred theme.
     */
    const getPreferredTheme = () => {
        const theme = getStoredTheme();
        if (theme) {
            return theme;
        }
        return isPreferredDark() ? THEME_DARK : THEME_LIGHT;
    };

    /**
     * Sets the theme.
     * @param {string} theme - the theme to apply.
     */
    const setTheme = (theme) => {
        if (theme === THEME_AUTO) {
            theme = isPreferredDark() ? THEME_DARK : THEME_LIGHT;
        }
        document.documentElement.setAttribute('data-bs-theme', theme);
    };

    // apply the preferred theme
    setTheme(getPreferredTheme());

    /**
     * Update the active theme.
     * @param {string} theme - the selected theme.
     */
    const updateActiveTheme = (theme) => {
        // remove check icon
        document.querySelectorAll('[data-theme].dropdown-item-checked-right').forEach((element) => {
            element.classList.remove('dropdown-item-checked-right');
        });

        // update
        document.querySelectorAll('.theme-switcher').forEach((themeSwitcher) => {
            // get values
            const sourceTheme = themeSwitcher.parentElement.querySelector(`[data-theme="${theme}"]`);
            const sourceIcon = sourceTheme.querySelector('.theme-icon');
            const sourceText = sourceTheme.querySelector('.theme-text');

            // add check icon
            sourceTheme.classList.add('dropdown-item-checked-right');

            // set values
            const targetIcon = themeSwitcher.querySelector('.theme-icon');
            const targetText = themeSwitcher.querySelector('.theme-text');
            targetIcon.className = sourceIcon.className;
            targetText.textContent = sourceText.textContent;

            // raise event for sidebar
            window.dispatchEvent(new Event('resize'));
        });
    };

    /**
     * Hide the nav bar.
     */
    const hideNavBar = () => {
        document.querySelectorAll('.navbar-collapse.collapse.show').forEach((element) => {
            const collapse = bootstrap.Collapse.getInstance(element);
            if (collapse) {
                collapse.hide();
            }
        });
    };

    /**
     * Hide the theme switcher tooltips
     */
    const hideThemeTooltip = () => {
        document.querySelectorAll('[data-theme][data-bs-toggle="tooltip"]').forEach((element) => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
                tooltip.hide();
            }
        });
    };

    /**
     * Handle the prefer color scheme change.
     */
    window.matchMedia(THEME_MEDIA).addEventListener('change', () => {
        const storedTheme = getStoredTheme();
        if (storedTheme !== THEME_LIGHT && storedTheme !== THEME_DARK) {
            setTheme(getPreferredTheme());
        }
    });

    /*
     * Channel to update theme in other tabs
     */
    const channel = new window.BroadcastChannel(THEME_CHANNEL);
    channel.addEventListener('message', (e) => {
        if (e.data === THEME_EVENT_NAME) {
            const theme = getStoredTheme();
            updateActiveTheme(theme);
            setTheme(theme);
        }
    });

    /**
     * Handle the content loaded event.
     */
    window.addEventListener('DOMContentLoaded', () => {
        updateActiveTheme(getPreferredTheme());
        document.querySelectorAll('[data-theme]').forEach((element) => {
            element.addEventListener('mousedown', () => {
                setTimeout(() => hideThemeTooltip(), 100);
            });
            element.addEventListener('click', (e) => {
                e.preventDefault();
                const theme = element.getAttribute('data-theme');
                updateActiveTheme(theme);
                setStoredTheme(theme);
                setTheme(theme);
                // notify
                channel.postMessage(THEME_EVENT_NAME);
            });
        });
        document.querySelectorAll('.navbar-horizontal .dropdown-item,.navbar-horizontal .navbar-brand').forEach((element) => {
            element.addEventListener('click', () => hideNavBar());
        });
    });

})();
