/**
 * Ready function
 */
$(function () {
    'use strict';

    // ------------------------------------
    // ImageInput public class definition
    // ------------------------------------
    const ImageInput = class {
        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLInputElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend({}, ImageInput.DEFAULTS, options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            this._removeListeners();
            this.$element.removeData(ImageInput.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize this plugin.
         * @private
         */
        _init() {
            const options = this.options;
            this.$parent = this.$element.parents(options.parentClass);
            this.$preview = this.$parent.find(options.previewClass);
            this.$browse = this.$parent.find(options.browseClass);
            this.$delete = this.$parent.find(options.deleteClass);
            this.$edit = this.$parent.find(options.editClass);
            this._createProxies();
            this._addListeners();
        }

        /**
         * Create listener proxies.
         * @private
         */
        _createProxies() {
            this.changeProxy = (e) => this._change(e);
            this.deleteProxy = (e) => this._delete(e);
            this.editProxy = (e) => this._edit(e);

            this.dragEnterProxy = (e) => this._dragEnter(e);
            this.dragOverProxy = (e) => this._dragOver(e);
            this.dropProxy = (e) => this._drop(e);
        }

        /**
         * Add listeners.
         * @private
         */
        _addListeners() {
            this.$element.on('change', this.changeProxy);
            this.$delete.on('click', this.deleteProxy);
            this.$preview.on('click', this.editProxy);
            this.$browse.on('click', this.editProxy);
            this.$edit.on('click', this.editProxy);

            this.$preview.on('dragenter', this.dragEnterProxy);
            this.$preview.on('dragover', this.dragOverProxy);
            this.$preview.on('drop', this.dropProxy);
        }

        /**
         * Remove listeners.
         * @private
         */
        _removeListeners() {
            this.$element.off('change', this.changeProxy);
            this.$delete.off('click', this.deleteProxy);
            this.$preview.off('click', this.editProxy);
            this.$browse.off('click', this.editProxy);
            this.$edit.off('click', this.editProxy);

            this.$preview.off('dragenter', this.dragEnterProxy);
            this.$preview.off('dragover', this.dragOverProxy);
            this.$preview.off('drop', this.dropProxy);
        }

        /**
         * Handle the drag enter event.
         * @param {DragEnterEvent} e the source event.
         * @private
         */
        _dragEnter(e) {
            e.preventDefault();
            e.stopPropagation();
            const dataTransfer = this._getDataTransfer(e);
            if (!dataTransfer) {
                return;
            }
            if (!this._isDataFiles(dataTransfer)) {
                dataTransfer.effectAllowed = 'none';
            }
        }

        /**
         * Handle the drag over event.
         * @param {DragOverEvent} e the source event.
         * @private
         */
        _dragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            const dataTransfer = this._getDataTransfer(e);
            if (!dataTransfer) {
                return;
            }
            if (this._isDataFiles(dataTransfer)) {
                e.originalEvent.dataTransfer.dropEffect = 'copy';
            } else {
                e.originalEvent.dataTransfer.dropEffect = 'none';
            }
        }

        /**
         * Handle the drop event.
         * @param {DropEvent} e the source event.
         * @private
         */
        _drop(e) {
            e.preventDefault();
            e.stopPropagation();
            const dataTransfer = this._getDataTransfer(e);
            if (!dataTransfer) {
                return;
            }
            if (!this._isDataFiles(dataTransfer)) {
                return;
            }
            const files = dataTransfer.files;
            if (this._setImageFromFiles(e, files, false)) {
                this.$element[0].files = files;
            }
        }

        /**
         * Handle the input file change event.
         *
         * @param {Event} e - the source event.
         * @private
         */
        _change(e) {
            e.preventDefault();
            this._setImageFromFiles(e, e.target.files, true);
        }

        /**
         * Handle the delete button click event.
         *
         * @param {Event} e - the source event.
         * @private
         */
        _delete(e) {
            e.preventDefault();
            const $image = this._findImage(false);
            if ($image) {
                const src = this.$preview.data('default') || false;
                if (src) {
                    $image.attr('src', src)
                        .attr('alt', this.$preview.attr('title') || '');
                } else {
                    $image.remove();
                }
            }
            this.$parent.removeClass('image-input-exists').addClass('image-input-new');
            this.$element.val('').trigger('input');
            this.$browse.trigger('focus');
        }

        /**
         * Handle the edit (browse or edit buttons) event.
         *
         * @param {Event} e - the source event.
         * @private
         */
        _edit(e) {
            e.preventDefault();
            this.$element.trigger('click');
        }

        /**
         * Find or create (if applicable) the HTML image
         * @param {boolean} createIfMissing true to create the HTML image if not found
         * @return {jQuery|HTMLImageElement|null}
         * @private
         */
        _findImage(createIfMissing) {
            let $image = this.$preview.find('img');
            if ($image.length) {
                return $image;
            }
            if (createIfMissing) {
                $image = $('<img />', {
                    alt: this.$preview.attr('title') || ''
                });
                return $image.appendTo(this.$preview);
            }
            return null;
        }

        /**
         * Check if the given file is allowed.
         * @param {File}  file
         * @return {boolean} true if allowed; false otherwise.
         * @private
         */
        _accept(file) {
            const accept = this.$element.attr('accept');
            if (!accept) {
                return true;
            }
            const allowed = accept.split(',');
            return allowed.includes(file.type);
        }

        /**
         * Gets the data transfer, if applicable.
         * @param {DragEnterEvent|DragOverEvent|DropEvent} e the event to get data transfer for.
         * @return {DataTransfer|null} the data transfer or null if not found.
         * @private
         */
        _getDataTransfer(e) {
            return e.originalEvent && e.originalEvent.dataTransfer;
        }

        /**
         * Check if the data transfer type is for files.
         * @param  {DataTransfer} dataTransfer
         * @return {boolean} true if is for files.
         * @private
         */
        _isDataFiles(dataTransfer) {
            return dataTransfer.types.includes('Files');
        }

        /**
         * Sets the displayed image from the given list of files.
         * @param {Event|DropEvent} e the source event.
         * @param {FileList} files the list of files.
         * @param {boolean} deleteOnError true to delete image if invalid.
         * @return {boolean} true if image is set; false otherwise.
         * @private
         */
        _setImageFromFiles(e, files, deleteOnError) {
            // validate files
            if (!files || files.length === 0) {
                if (deleteOnError) {
                    this._delete(e);
                }
                return false;
            }

            // validate file
            const file = files[0];
            if (!file || !this._accept(file)) {
                if (deleteOnError) {
                    this._delete(e);
                }
                return false;
            }

            // set image
            this._findImage(true).attr('src', URL.createObjectURL(file));
            this.$parent.removeClass('image-input-new').addClass('image-input-exists');
            this.$edit.trigger('focus');
            return true;
        }
    };

    // -----------------------------
    // Default options
    // -----------------------------
    ImageInput.DEFAULTS = {
        parentClass: '.image-input',
        editClass: '.image-input-edit',
        deleteClass: '.image-input-delete',
        browseClass: '.image-input-browse',
        previewClass: '.image-input-preview',
    };

    /**
     * The plugin name.
     */
    ImageInput.NAME = 'bs.image-input';

    // ------------------------------------
    // ImageInput plugin definition
    // ------------------------------------
    const oldImageInput = $.fn.imageInput;
    $.fn.imageInput = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            const data = $this.data(ImageInput.NAME);
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data(ImageInput.NAME, new ImageInput(this, settings));
            }
        });
    };
    $.fn.imageInput.Constructor = ImageInput;

    // ------------------------------------
    // ImageInput no conflict
    // ------------------------------------
    $.fn.imageInput.noConflict = function () {
        $.fn.imageInput = oldImageInput;
        return this;
    };
});
