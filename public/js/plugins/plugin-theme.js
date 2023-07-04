/**! compression tag for ftp-deployment */

/* globals Toaster, bootstrap */

/**
 * Plugin to handle theme.
 */
(function ($) {
    'use strict';

    /**
     * The cookie entry name.
     * @type {string}
     */
    const COOKIE_ENTRY = 'THEME=';

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

    // ------------------------------------
    // Theme public class definition
    // ------------------------------------
    const ThemeListener = class {
        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, ThemeListener.DEFAULTS, options);
            this._init();
        }

        /**
         * Remove handlers and data.
         */
        destroy() {
            this.$element.off('click', this.clickProxy);
            this.$element.removeData(ThemeListener.NAME);
            window.matchMedia('(prefers-color-scheme: dark)').removeEventListener('change', this.changeProxy);
            const $dialog = this._getDialog();
            if ($dialog) {
                $dialog.remove();
            }
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize this plugin.
         * @private
         */
        _init() {
            this.clickProxy = () => this._click();
            this.changeProxy = () => this._change();
            this.$element.on('click', this.clickProxy);
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', this.changeProxy);
        }

        /**
         * Handle the element click event.
         * @private
         */
        _click() {
            if (this._getDialog()) {
                this._showDialog();
            } else {
                this._loadDialog();
            }
        }

        /**
         * Handle the prefer color scheme change.
         * @private
         */
        _change() {
            const theme = this._getCookieValue();
            if (theme !== THEME_LIGHT || theme !== THEME_DARK) {
                this._setTheme(this._getPreferredTheme());
            }
        }

        /**
         * Load dialog from server.
         * @private
         */
        _loadDialog() {
            const that = this;
            const options = that.options;
            const url = options.url || that.$element.data('url');
            if (!url) {
                return;
            }
            $.get(url, function (data) {
                if (data) {
                    const $dialog = $(data);
                    $dialog.appendTo($(options.targetId));
                    that._initDialog();
                    that._showDialog();
                }
            });
        }

        /**
         * Gets the dialog
         * @return {jQuery|null} the dialog, if found; null otherwise.
         * @private
         */
        _getDialog() {
            const $dialog = $(this._getDialogId());
            return $dialog.length ? $dialog : null;
        }

        /**
         * Gets the dialog identifier.
         * @return {string}
         * @private
         */
        _getDialogId() {
            return this.options.dialogId;
        }

        /**
         * Show the modal dialog.
         * @private
         */
        _showDialog() {
            this._getModal().show();
        }

        /**
         * Hide the modal dialog.
         * @private
         */
        _hideDialog() {
            this._getModal().hide();
            $(this.options.targetId).trigger('focus');
        }

        /**
         * Gets the modal instance.
         * @return Modal
         * @private
         */
        _getModal() {
            const id = this._getDialogId();
            return bootstrap.Modal.getOrCreateInstance(id);
        }

        /**
         * Handle the dialog show event.
         * @private
         */
        _onDialogShow() {
            const $dialog = this._getDialog();
            if (!$dialog) {
                return;
            }
            const options = this.options;
            const theme = this._getCookieValue();
            const selector = this._getInputSelector();
            const iconSelector = `label ${options.labelIcon}`;
            const isDark = theme === THEME_DARK || this._isMediaDark();
            $dialog.data('old-theme', theme).data('new-theme', false);
            $(selector).each(function () {
                const $this = $(this);
                $this.prop('checked', $this.val() === theme);
                const $icon = $this.parent().find(iconSelector);
                if (isDark) {
                    $icon.removeClass($this.data(options.iconDark))
                        .addClass($this.data(options.iconLight));
                } else {
                    $icon.removeClass($this.data(options.iconLight))
                        .addClass($this.data(options.iconDark));
                }
            });
            if (document.querySelectorAll(this._getInputCheckedSelector()).length === 0) {
                document.querySelector(selector).checked = true;
            }
        }

        /**
         * Handle the dialog visible (shown) event.
         * @private
         */
        _onDialogVisible() {
            const $dialog = this._getDialog();
            if ($dialog) {
                $(this._getInputCheckedSelector()).trigger('focus');
            }
        }

        /**
         * Handle the dialog hidden event.
         * @private
         */
        _onDialogHidden() {
            const $dialog = this._getDialog();
            if (!$dialog) {
                return;
            }
            const oldTheme = $dialog.data('old-theme');
            const newTheme = $dialog.data('new-theme');
            if (oldTheme === newTheme) {
                $(window).trigger('resize');
                return;
            }
            if (!newTheme) {
                if (oldTheme !== this._getTheme()) {
                    this._setTheme(oldTheme);
                }
                $(window).trigger('resize');
                return;
            }
            this._setTheme(newTheme);
            this._setCookieValue(newTheme);
            const $link = $(this._getInputCheckedSelector());
            if ($link.length) {
                const message = $link.data(this.options.success);
                const title = $(this._getTitleSelector()).text();
                Toaster.success(message, title);
            }
            $(window).trigger('resize');
        }

        /**
         * Handle the dialog key down event.
         * @param {KeyboardEvent} e
         * @private
         */
        _onDialogKeyDown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                this._onDialogAccept();
            }
        }

        /**
         * Handle the OK button click event.
         * @private
         */
        _onDialogAccept() {
            const $dialog = this._getDialog();
            const $input = $(this._getInputCheckedSelector());
            const options = this.options;
            if ($input.length) {
                $dialog.data('new-theme', $input.val());
                const $label = $input.siblings('label');
                if ($label.length) {
                    const icon = $input.data(options.iconLight);
                    const text = $label.children(options.labelText).text();
                    $(options.label).each(function () {
                        $(this).children(options.labelIcon).attr('class', icon);
                        $(this).children(options.labelText).text(text);
                    });
                }
            }
            this._hideDialog();
        }

        /**
         * Initialize the dialog.
         * @private
         */
        _initDialog() {
            const $dialog = this._getDialog();
            if (!$dialog) {
                return;
            }

            const options = this.options;
            $(`${options.dialogId} ${options.ok}`)
                .on('click', () => this._onDialogAccept());

            $dialog.on('show.bs.modal', () => this._onDialogShow())
                .on('shown.bs.modal', () => this._onDialogVisible())
                .on('hidden.bs.modal', () => this._onDialogHidden())
                .on('keydown', (e) => this._onDialogKeyDown(e));
        }

        /**
         * Return if prefers color scheme color is dark.
         * @return {boolean} true if dark.
         * @private
         */
        _isMediaDark() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        /**
         * Gets the preferred theme.
         * @return {string} the preferred theme if found; an empty string otherwise.
         * @private
         */
        _getPreferredTheme() {
            const theme = this._getCookieValue();
            if (theme && theme !== THEME_AUTO) {
                return theme;
            }
            return this._isMediaDark() ? THEME_DARK : THEME_LIGHT;
        }

        /**
         * Sets the theme to the body.
         * @param {string} theme - the theme to apply.
         * @private
         */
        _setTheme(theme) {
            if (theme === THEME_AUTO) {
                theme = this._isMediaDark() ? THEME_DARK : THEME_LIGHT;
            }
            document.documentElement.setAttribute('data-bs-theme', theme);
        }

        /**
         * Gets the body theme.
         * @return {string} the selected theme.
         * @private
         */
        _getTheme() {
            return document.documentElement.getAttribute('data-bs-theme') || THEME_AUTO;
        }

        /**
         * Gets the cookie theme value.
         * @return {string} the cookie theme, if found; the 'auto' otherwise.
         * @private
         */
        _getCookieValue() {
            const decodedCookie = decodeURIComponent(document.cookie);
            const entries = decodedCookie.split(';');
            for (let i = 0; i < entries.length; i++) {
                const entry = entries[i].trim();
                if (entry.startsWith(COOKIE_ENTRY)) {
                    return entry.substring(COOKIE_ENTRY.length);
                }
            }
            return THEME_AUTO;
        }

        /**
         * Sets the cookie theme value.
         * @param {string} value - the theme to set.
         * @private
         */
        _setCookieValue(value) {
            const date = new Date();
            date.setFullYear(date.getFullYear() + 1);
            const path = document.body.dataset.cookiePath || '/';
            let entry = `${COOKIE_ENTRY}${encodeURIComponent(value)};`;
            entry += `expires=${date.toUTCString()};`;
            entry += `path=${path};`;
            entry += 'samesite=lax;';
            document.cookie = entry;
        }

        _getInputSelector() {
            const options = this.options;
            return `${options.dialogId} ${options.input}`;
        }

        _getInputCheckedSelector() {
            return `${this._getInputSelector()}:checked`;
        }

        _getTitleSelector() {
            const options = this.options;
            return `${options.dialogId} ${options.title}`;
        }
    };

    /**
     * The default options.
     */
    ThemeListener.DEFAULTS = {
        // the Ajax URL to get dialog
        url: null,
        // the dialog identifier
        dialogId: '#theme_modal',
        // the target where to add dialog
        targetId: 'body',
        // the radio inputs selector
        input: '.form-check-input',
        // the label selector
        label: '.theme-link',
        // the label icon class selector
        labelIcon: '.theme-icon',
        // the label text selector
        labelText: '.theme-text',
        // the title message selector
        title: '.modal-title',
        // the success data message selector
        success: 'success',
        // the OK button selector
        ok: '.btn-ok',
        // the light icon data key
        'iconLight': 'icon-light',
        // the dark icon data key
        'iconDark': 'icon-dark'
    };

    /**
     * The plugin name.
     */
    ThemeListener.NAME = 'bs.theme-listener';

    // ------------------------------------
    // ThemeListener plugin definition
    // ------------------------------------
    const oldThemeListener = $.fn.themeListener;
    $.fn.themeListener = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(ThemeListener.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(ThemeListener.NAME, new ThemeListener(this, settings));
            }
        });
    };
    $.fn.themeListener.Constructor = ThemeListener;

    // ------------------------------------
    // ThemeListener no conflict
    // ------------------------------------
    $.fn.themeListener.noConflict = function () {
        $.fn.themeListener = oldThemeListener;
        return this;
    };
}(jQuery));
