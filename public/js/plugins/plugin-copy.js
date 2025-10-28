/* globals Toaster, ClipboardJS */

/**
 * Plugin to copy an element to the clipboard.
 */
$(function () {
    'use strict';

    /**
     * @typedef CopyEvent
     * @type {object}
     * @property {string} action
     * @property {string} text
     * @property {HTMLElement} trigger
     * @property {function} clearSelection
     */
    // --------------------------------------
    // CopyClipboard public class definition
    // --------------------------------------
    const CopyClipboard = class {

        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, CopyClipboard.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            if (this.clipboard) {
                this.clipboard.off('success', this.proxySuccess);
                this.clipboard.off('error', this.proxyError);
                this.clipboard.destroy();
                this.clipboard = null;
            }
            this.$element.removeData(CopyClipboard.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize clipboard.
         * @private
         */
        _init() {
            if (ClipboardJS && ClipboardJS.isSupported()) {
                this.proxySuccess = (e) => this._onCopySuccess(e);
                this.proxyError = (e) => this._onCopyError(e);
                this.clipboard = new ClipboardJS(this.$element[0], this.options);
                this.clipboard.on('success', this.proxySuccess);
                this.clipboard.on('error', this.proxyError);
            } else {
                this._removeOnError();
            }
        }

        /**
         * Notify a message.
         * @param {string} type - the message type.
         * @param {string} message - the message content.
         * @private
         */
        _notify(type, message) {
            Toaster.notify(type, message, this.options.title);
        }

        /**
         * Handle copy success event.
         * @param {CopyEvent} e
         * @private
         */
        _onCopySuccess(e) {
            if (typeof this.options.copySuccess === 'function') {
                this.options.copySuccess(e);
            }
            e.clearSelection();
            this._hideModal(e);
            this._notify(Toaster.NotificationTypes.SUCCESS, this.options.success);
        }

        /**
         * Handle copy error event.
         * @param {CopyEvent} e
         * @private
         */
        _onCopyError(e) {
            if (typeof this.options.copyError === 'function') {
                this.options.copyError(e);
            }
            e.clearSelection();
            this._hideModal(e);
            this._removeOnError();
            this._notify(Toaster.NotificationTypes.WARNING, this.options.error);
        }

        /**
         * Destroy this instance and remove element if applicable.
         * @private
         */
        _removeOnError() {
            this.destroy();
            if (this.options.removeOnError) {
                this.$element.fadeOut().remove();
            }
        }

        /**
         * Hide the parent's modal dialog if applicable.
         * @param {CopyEvent} e
         * @private
         */
        _hideModal(e) {
            if (this.options.hideModal) {
                const $trigger = $(e.trigger);
                const $dialog = $trigger.parents('.modal');
                if ($dialog.length) {
                    $trigger.trigger('blur');
                    $dialog.modal('hide');
                }
            }
        }
    };

    // -----------------------------
    // Default options
    // -----------------------------
    CopyClipboard.DEFAULTS = {
        title: 'Copy',
        success: 'The data has been successfully copied to the clipboard.',
        error: 'An error occurred while copying data to the clipboard.',
        removeOnError: true, // remove the element on error
        hideModal: false, // hide the parent's dialog on success or on error
        copySuccess: null, // function to call on success
        copyError: null, // function to call on error
    };

    // -------------------------------
    // The plugin name.
    // -------------------------------
    CopyClipboard.NAME = 'bs.copy-clipboard';

    // -------------------------------
    // CopyClipboard plugin definition
    // -------------------------------
    const oldCopyClipboard = $.fn.copyClipboard;
    $.fn.copyClipboard = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(CopyClipboard.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(CopyClipboard.NAME, new CopyClipboard(this, settings));
            }
        });
    };
    $.fn.copyClipboard.Constructor = CopyClipboard;

    // ------------------------------------
    // CopyClipboard no conflict
    // ------------------------------------
    $.fn.copyClipboard.noConflict = function () {
        $.fn.copyClipboard = oldCopyClipboard;
        return this;
    };
});
