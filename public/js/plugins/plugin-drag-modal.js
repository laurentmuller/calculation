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
            this.$body = $('body');
            this.$header = this.$element.find('.modal-header');
            this.$dialog = this.$element.find('.modal-dialog');
            this.$content = this.$element.find('.modal-content');
            this.$closeButton = this.$element.find('.close');
            this.margin = $.parseInt(this.$dialog.css('margin-top'));

            // proxies
            this.headerMouseDownProxy = (e) => this._headerMouseDown(e);
            this.bodyMouseMoveProxy = (e) => this._bodyMouseMove(e);
            this.bodyMouseUpProxy = (e) => this._bodyMouseUp(e);
            this.elementShowProxy = () => this._elementShow();
            this.elementShownProxy = () => this._elementShown();
            this.elementHideProxy = () => this._elementHide();
            this.elementHiddenProxy = () => this._elementHidden();

            // start listening
            this.$element.on('show.bs.modal', this.elementShowProxy)
                .on('shown.bs.modal', this.elementShownProxy)
                .on('hide.bs.modal', this.elementHideProxy)
                .on('hidden.bs.modal', this.elementHiddenProxy);
            this.$header.on('mousedown.drag.header', this.headerMouseDownProxy);
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
            const options = this.options;
            this.startX = e.pageX - this.$header.offset().left;
            this.startY = e.pageY - this.$header.offset().top;
            this.right = window.innerWidth - this.margin - this.$content.width();
            this.bottom = window.innerHeight - this.margin - this.$content.height() - options.marginBottom;
            if (this.options.focusOnShow) {
                this.$focused = $(':focus');
            }

            // update style
            this.$header.toggleClass(options.className);
            this.$closeButton.toggleClass(options.className);

            // handle events
            this.$body.on('mousemove.drag.body', this.bodyMouseMoveProxy)
                .one('mouseup.drag.body', this.bodyMouseUpProxy);
        }

        /**
         * Handle the body mouse move event.
         *
         * @param {MouseEvent} e - the event.
         * @private
         */
        _bodyMouseMove(e) {
            const offsetX = Math.max(this.margin, Math.min(this.right, e.pageX - this.startX));
            const offsetY = Math.max(this.margin, Math.min(this.bottom, e.pageY - this.startY));
            this.$dialog.offset({
                left: offsetX,
                top: offsetY
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
            if (this.options.savePosition && this.positionX && this.positionY) {
                this.$dialog.css({
                    left: this.positionX,
                    top: this.positionY
                });
            }
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
            if (this.options.savePosition) {
                this.positionX = this.$dialog.css('left');
                this.positionY = this.$dialog.css('top');
            }
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
        savePosition: false,
        className: 'bg-primary text-white'
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
        $.fn.draggableModal = oldDraggableModal;
        return this;
    };
}(jQuery));
