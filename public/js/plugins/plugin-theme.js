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
         * Handle the link click event.
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
            const url = that.$element.data('url');
            if (!url) {
                return;
            }
            $.get(url, function (data) {
                if (data) {
                    const $dialog = $(data);
                    $dialog.appendTo($('.page-content'));
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
            const $dialog = $('#theme_modal');
            return $dialog.length ? $dialog : null;
        }

        /**
         * Show the dialog.
         * @private
         */
        _showDialog() {
            bootstrap.Modal.getOrCreateInstance('#theme_modal').show();
        }

        /**
         * Hide the dialog.
         * @private
         */
        _hideDialog() {
            bootstrap.Modal.getOrCreateInstance('#theme_modal').hide();
            $('.page-content').trigger('focus');
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
            $dialog.data('theme', false);
            const theme = this._getCookieValue();
            $('#theme_modal .form-check-input').each(function () {
                const $this = $(this);
                $this.attr('checked', $this.val() === theme);
                const $icon = $this.parent().find('label .theme-icon');
                if (theme === THEME_LIGHT) {
                    $icon.removeClass($this.data('icon-light'))
                        .addClass($this.data('icon-dark'));
                } else {
                    $icon.removeClass($this.data('icon-dark'))
                        .addClass($this.data('icon-light'));
                }
            });
            if (document.querySelectorAll('#theme_modal .form-check-input:checked').length === 0) {
                document.querySelector('#theme_modal .form-check-input').checked = true;
            }
            $(window).trigger('resize');
        }

        /**
         * Handle the dialog visible event.
         * @private
         */
        _onDialogVisible() {
            const $dialog = this._getDialog();
            if (!$dialog) {
                return;
            }
            $('#theme_modal .form-check-input:checked').trigger('focus');
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
            const theme = $dialog.data('theme');
            if (!theme) {
                return;
            }
            this._setTheme(theme);
            this._setCookieValue(theme);
            const $link = $('#theme_modal .form-check-input:checked');
            if ($link.length) {
                const message = $link.data('success');
                const title = $('#theme_modal .modal-title').text();
                Toaster.success(message, title, {dataset: '#flashes'});
            }
        }

        /**
         * Handle the dialog key down event.
         * @param {KeyboardEvent} e
         * @param {jQuery} $button
         * @private
         */
        _onDialogKeyDown(e, $button) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                $button.trigger('click');
            }
        }

        /**
         * Handle the OK button click event.
         * @private
         */
        _onDialogOk() {
            const $dialog = this._getDialog();
            const $input = $('#theme_modal .form-check-input:checked');
            if ($input.length) {
                $dialog.data('theme', $input.val());
                const $label = $input.parent().find('label');
                if ($label.length) {
                    const icon = $input.data('icon-light');
                    const text = $label.children('.theme-text').text();
                    $('.theme-link').each(function () {
                        $(this).children('.theme-icon').attr('class', icon);
                        $(this).children('.theme-text').text(text);
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

            const $btnOk = $('#theme_modal .btn-ok')
                .on('click', () => this._onDialogOk());

            $dialog.on('show.bs.modal', () => this._onDialogShow())
                .on('shown.bs.modal', () => this._onDialogVisible())
                .on('hidden.bs.modal', () => this._onDialogHidden())
                .on('keydown', (e) => this._onDialogKeyDown(e, $btnOk));

            $('#theme_modal .help-text').on('click', function () {
                $(this).parent().children('.form-check-input').trigger('click');
            });

            $('#theme_modal .form-check').on('dblclick', () => $btnOk.trigger('click'));
        }

        /**
         * Return if the media color scheme is dark.
         * @return {boolean} true if selected.
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
            if (theme) {
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
            if (theme === THEME_AUTO && this._isMediaDark()) {
                document.body.setAttribute('data-bs-theme', THEME_DARK);
            } else {
                document.body.setAttribute('data-bs-theme', theme);
            }
        }

        /**
         * Gets the cookie theme value.
         * @return {string} the cookie theme, if found; the 'auto' otherwise.
         * @private
         */
        _getCookieValue() {
            const name = COOKIE_ENTRY;
            const decodedCookie = decodeURIComponent(document.cookie);
            const entries = decodedCookie.split(';');
            for (let i = 0; i < entries.length; i++) {
                const entry = entries[i].trimStart();
                if (entry.indexOf(name) === 0) {
                    return entry.substring(name.length, entry.length);
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
    };

    /**
     * The default options.
     */
    ThemeListener.DEFAULTS = {
        // the dialog identifier
        dialogId: '#theme_modal',
        // the target where to add dialog
        targetId: '.page-content',
        // the radio inputs selector
        input: '.form-check-input',
        // the label icon selector
        labelIcon: '.theme-text',
        // the label text selector
        labelText: '.theme-text',
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
