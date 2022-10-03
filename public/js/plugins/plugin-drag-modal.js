/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // DraggableModal public class definition
    // ------------------------------------
    const DraggableModal = class {

        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} options - the plugin options.
         */
        constructor(element, options) {
            // modal dialog?
            if (!element.classList.contains('modal')) {
                throw 'The element must be a modal dialog!';
            }
            this.$element = $(element);
            this.options = $.extend(true, {}, DraggableModal.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            this.$header.off('mousedown.drag.header', this.headerMouseDownProxy);
            this.$body.off('mousemove.drag.body', this.bodyMouseMoveProxy)
                .off('mouseup.drag.body', this.bodyMouseUpProxy);
            this.$element.off('show.bs.modal', this.elementShowProxy)
                .off('shown.bs.modal', this.elementShownProxy)
                .off('hide.bs.modal', this.elementHideProxy)
                .off('hidden.bs.modal', this.elementHiddenProxy)
                .removeData(DraggableModal.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize the plugin.
         * @private
         */
        _init() {
            const that = this;
            that.$body = $('body');
            that.$header = that.$element.find('.modal-header');
            that.$dialog = that.$element.find('.modal-dialog');
            that.$content = that.$element.find('.modal-content');
            that.$closeButton = that.$element.find('.close');
            that.margin = $.parseInt(that.$dialog.css('margin-top'));

            // proxies
            that.headerMouseDownProxy = function (e) {
                that._headerMouseDown(e);
            };
            that.bodyMouseMoveProxy = function (e) {
                that._bodyMouseMove(e);
            };
            that.bodyMouseUpProxy = function (e) {
                that._bodyMouseUp(e);
            };
            that.elementShowProxy = function () {
                that._elementShow();
            };
            that.elementShownProxy = function () {
                that._elementShown();
            };
            that.elementHideProxy = function () {
                that._elementHide();
            };
            that.elementHiddenProxy = function () {
                that._elementHidden();
            };

            // start listening
            that.$element.on('show.bs.modal', this.elementShowProxy)
                .on('shown.bs.modal', this.elementShownProxy)
                .on('hide.bs.modal', this.elementHideProxy)
                .on('hidden.bs.modal', this.elementHiddenProxy);
            that.$header.on('mousedown.drag.header', that.headerMouseDownProxy);
        }

        /**
         * Handle the header mouse down event.
         *
         * @param {MouseEvent} e - the event.
         * @private
         */
        _headerMouseDown(e) {
            // left button?
            if (e.button !== 0) {
                return;
            }
            // close button dialog?
            if ($(e.target).closest('.close').length) {
                return;
            }

            // save values
            const that = this;
            const options = that.options;
            that.startX = e.pageX - that.$header.offset().left;
            that.startY = e.pageY - that.$header.offset().top;
            that.right = window.innerWidth - that.margin - that.$content.width();
            that.bottom = window.innerHeight - that.margin - that.$content.height() - options.marginBottom;
            if (this.options.focusOnShow) {
                this.$focused = $(':focus');
            }

            // update style
            that.$header.toggleClass(options.className);
            that.$closeButton.toggleClass(options.className);

            // handle events
            that.$body.on('mousemove.drag.body', that.bodyMouseMoveProxy)
                .one('mouseup.drag.body', that.bodyMouseUpProxy);
        }

        /**
         * Handle the body mouse move event.
         *
         * @param {MouseEvent} e - the event.
         * @private
         */
        _bodyMouseMove(e) {
            const left = Math.max(this.margin, Math.min(this.right, e.pageX - this.startX));
            const top = Math.max(this.margin, Math.min(this.bottom, e.pageY - this.startY));
            this.$dialog.offset({
                left: left,
                top: top
            });
        }

        /**
         * Handle the body mouse up event.
         *
         * @param {MouseEvent} e - the event.
         * @private
         */
        _bodyMouseUp(e) {
            const options = this.options;
            this.$body.off('mousemove.drag.body', this.bodyMouseMoveProxy);
            this.$header.toggleClass(options.className);
            this.$closeButton.toggleClass(options.className);
            if (options.focusOnShow) {
                this._setFocus();
            }
        }

        /**
         * Handle the show dialog event.
         * @private
         */
        _elementShow() {
            if (!this.options.focusOnShow) {
                this.$focused = $(':focus');
            }
        }

        /**
         * Handle the shown dialog event.
         * @private
         */
        _elementShown() {
            if (this.options.focusOnShow) {
                this.$focused = $(':focus');
            }
        }

        /**
         * Handle the hide dialog event.
         * @private
         */
        _elementHide() {
            this.$body.off('mousemove.drag.body', this.bodyMouseMoveProxy)
                .off('mouseup.drag.body', this.bodyMouseUpProxy);
        }

        /**
         * Handle the hidden dialog event.
         * @private
         */
        _elementHidden() {
            if (!this.options.focusOnShow) {
                this._setFocus();
            }
            this.$dialog.css({'left': '', 'top': ''});
        }

        /**
         * Sets focus of the previous selected element (if any).
         * @private
         */
        _setFocus() {
            if (this.$focused && this.$focused.length) {
                this.$focused.trigger('focus');
            }
            this.$focused = null;
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    DraggableModal.DEFAULTS = {
        marginBottom: 0,
        focusOnShow: false,
        className: 'bg-primary text-white',
    };

    /**
     * The plugin name.
     */
    DraggableModal.NAME = 'bs.draggable-modal';

    // -----------------------------
    // sidebar plugin definition
    // -----------------------------
    const oldDraggableModal = $.fn.draggableModal;

    $.fn.draggableModal = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(DraggableModal.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(DraggableModal.NAME, new DraggableModal(this, settings));
            }
        });
    };

    $.fn.draggableModal.Constructor = DraggableModal;

    // ------------------------------------
    // sidebar no conflict
    // ------------------------------------
    $.fn.draggableModal.noConflict = function () {
        $.fn.sidebar = oldDraggableModal;
        return this;
    };
}(jQuery));
