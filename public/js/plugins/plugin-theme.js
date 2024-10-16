/**! compression tag for ftp-deployment */

/* globals Toaster, bootstrap */

/**
 * Plugin to handle theme.
 */
$(function () {
    'use strict';

    /**
     * The cookie entry name.
     * @type {string}
     */
    const COOKIE_KEY = 'THEME';

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
     * The theme chanel name.
     * @type {string}
     */
    const THEME_CHANNEL = 'theme_channel';

    /**
     * The theme changed event-name.
     * @type {string}
     */
    const THEME_EVENT_NAME = 'theme_changed';

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
            $.getJSON(url, function (data) {
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
            const theme = this._getCookieValue();
            const selector = this._getInputSelector();
            $dialog.data('old-theme', theme).data('new-theme', false);
            $(selector).each(function () {
                const $this = $(this);
                $this.prop('checked', $this.val() === theme);
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
         * Hide the navigation bar and set focus to the element.
         * @private
         */
        _setFocus() {
            $('.navbar-collapse.collapse.show').removeClass('show');
            this.$element.trigger('focus');
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

            this._setFocus();
            const oldTheme = $dialog.data('old-theme');
            const newTheme = $dialog.data('new-theme');
            if (oldTheme === newTheme) {
                return;
            }
            if (!newTheme) {
                if (oldTheme !== this._getTheme()) {
                    this._setTheme(oldTheme);
                }
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
         * Handle the click event of the OK button.
         * @private
         */
        _onDialogAccept() {
            const $dialog = this._getDialog();
            if (!$dialog) {
                return;
            }
            const $input = $(this._getInputCheckedSelector());
            if (0 === $input.length) {
                return;
            }
            const options = this.options;
            $dialog.data('new-theme', $input.val());

            const icon = $input.data(options.icon);
            if (icon) {
                $(options.switcherIcon).attr('class', icon);
            }
            const text = $input.data(options.text);
            if (text) {
                $(options.switcherText).text(text);
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
         * Notify that the theme has changed.
         * @private
         */
        _notifyTheme() {
            const channel = new window.BroadcastChannel(THEME_CHANNEL);
            channel.postMessage(THEME_EVENT_NAME);
            channel.close();
        }

        /**
         * Sets the theme document element.
         * @param {string} theme - the theme to apply.
         * @private
         */
        _setTheme(theme) {
            if (theme === THEME_AUTO) {
                theme = this._isMediaDark() ? THEME_DARK : THEME_LIGHT;
            }
            $(document.documentElement).attr('data-bs-theme', theme);
            this._notifyTheme();
        }


        /**
         * Gets the document element theme.
         * @return {string} the selected theme.
         * @private
         */
        _getTheme() {
            return $(document.documentElement).attr('data-bs-theme') || THEME_AUTO;
        }

        /**
         * Gets the cookie theme value.
         * @return {string} the cookie theme, if found; the 'auto' otherwise.
         * @private
         */
        _getCookieValue() {
            return window.Cookie.getValue(COOKIE_KEY, THEME_AUTO);
        }

        /**
         * Sets the cookie theme value.
         * @param {string} value - the theme to set.
         * @private
         */
        _setCookieValue(value) {
            const path = document.body.dataset.cookiePath || '/';
            window.Cookie.setValue(COOKIE_KEY, value, path);
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
        // the URL to get dialog
        url: null,
        // the dialog identifier
        dialogId: '#theme_modal',
        // the target selector where to add dialog
        targetId: 'body',
        // the radio inputs selector
        input: '.form-check-input',
        // the title message selector in the modal dialog
        title: '.modal-title',
        // the success data message selector in dialog
        success: 'success',
        // the OK button selector in the modal dialog
        ok: '.btn-ok',
        // the data key for the icon class
        icon: 'class',
        // the data key for the text content
        text: 'text',
        // the theme switcher text selector
        switcherText: '.theme-switcher .theme-text',
        // the theme switcher icon selector
        switcherIcon: '.theme-switcher .theme-icon'
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
});
