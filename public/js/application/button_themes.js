/* globals Toaster, THEME */

/**
 * Gets a value indicating if start view transition is supported.
 * @return {boolean} true if supported.
 */
function supportTransition() {
    'use strict';
    return document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Gets the preferred theme.
 * @return {string} the preferred theme.
 */
function getPreferredTheme() {
    'use strict';
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? THEME.DARK : THEME.LIGHT;
}

/**
 * Handle theme input change.
 */
function initThemeInput() {
    'use strict';
    $('.dropdown-theme-entry').on('click', function (e) {
        e.preventDefault();
        // get values
        const $this = $(this);
        const path = document.body.dataset.cookiePath || '/';
        const oldTheme = window.Cookie.getValue(THEME.KEY, THEME.AUTO);
        let theme = $this.data('value');

        // update cookie
        if (!theme || theme === THEME.AUTO) {
            theme = getPreferredTheme();
            window.Cookie.clearValue(THEME.KEY, path);
        } else {
            window.Cookie.setValue(THEME.KEY, theme, path);
        }

        // same theme?
        if (oldTheme === theme) {
            return;
        }

        // apply theme
        const callback = () => {
            document.documentElement.setAttribute(THEME.ATTRIBUTE, theme);
        };
        if (supportTransition()) {
            document.startViewTransition(callback);
        } else {
            callback();
        }

        // update checked theme and icon
        $('.dropdown-theme-entry').removeClass('dropdown-item-checked-right');
        $('.btn-theme-dropdown i').attr('class', $this.data('icon'));
        $this.addClass('dropdown-item-checked-right');

        // update other theme switchers
        $('.theme-switcher:not(.dropdown-theme-dialog) .theme-text')
            .text($this.text())
        $('.theme-switcher:not(.dropdown-theme-dialog) .theme-icon')
            .attr('class', $this.data('icon'));

        // show success message
        const title = $('.btn-theme-dropdown').data('title');
        const message = $this.data('success');
        Toaster.success(message, title);
    });
}

/**
 * Handle theme changed event.
 */
function initThemeChanged() {
    'use strict';
    $('body').on(THEME.EVENT, '.theme-switcher', function (e, theme) {
        $('.dropdown-theme-entry').each(function () {
            const $this = $(this);
            if ($this.data('value') === theme) {
                $this.addClass('dropdown-item-checked-right');
                $('.btn-theme-dropdown i').attr('class', $this.data('icon'));
            } else {
                $this.removeClass('dropdown-item-checked-right');
            }
        })
    });
}

function initThemeButtons() {
    'use strict';
    initThemeInput();
    initThemeChanged();
}
