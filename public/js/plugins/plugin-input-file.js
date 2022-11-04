/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // FileInput public class definition
    // ------------------------------------
    const FileInput = class {
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
            this.options = $.extend({}, FileInput.DEFAULTS, options);
            this._init();
        }

        /**
         * Display the selection file dialog by trigger the click event on the input file element.
         *
         * @param {Event} [e] - the source event.
         */
        selectFile(e) {
            if (e) {
                e.preventDefault();
            }
            this.$input.trigger('click');
        }

        /**
         * Destructor.
         */
        destroy() {
            this.$element.off('change.bs.file-input', this.changeProxy);
            this.$element.find('[data-dismiss="file-input"]').off('click.bs.file-input', this.clearProxy);
            this.$element.find('[data-trigger="file-input"]').off('click.bs.file-input', this.clickProxy);
            $(this.$input[0].form).off('reset.bs.file-input', this.resetProxy);
            this.$element.removeData(FileInput.NAME);
        }


        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize this plugin.
         * @private
         */
        _init() {
            this.$input = this.$element.find(':file');
            if (this.$input.length === 0) {
                return;
            }
            const options = this.options;
            this.name = this.$input.attr('name') || options.name;
            this.placeholder = this.$input.attr('placeholder') || options.placeholder || '';
            this.$hidden = this.$element.find('input[type=hidden][name="' + this.name + '"]');
            if (this.$hidden.length === 0) {
                this.$hidden = $('<input type="hidden">').insertBefore(this.$input);
            }
            this.$preview = this.$element.find('.file-input-preview');
            this.original = {
                exists: this.$element.hasClass('file-input-exists'),
                preview: this.$preview.html(),
                hiddenVal: this.$hidden.val()
            };
            this._listen();
            this._reset();
        }

        /**
         * Start listen events.
         * @return {FileInput} this instance for chaining.
         * @private
         */
        _listen() {
            this.changeProxy = (e) => this._change(e);
            this.clearProxy = (e) => this._clear(e);
            this.clickProxy = (e) => this.selectFile(e);
            this.resetProxy = (e) => this._reset(e);
            this.$input.on('change.bs.file-input', this.changeProxy);
            this.$element.find('[data-dismiss="file-input"]').on('click.bs.file-input', this.clearProxy);
            this.$element.find('[data-trigger="file-input"]').on('click.bs.file-input', this.clickProxy);
            $(this.$input[0].form).on('reset.bs.file-input', this.resetProxy);

            return this;
        }

        /**
         * Reset image.
         * @param {Event} [e] - the source event.
         * @return {FileInput} this instance for chaining.
         * @private
         */
        _reset(e) {
            this._clear(e);
            this.$hidden.val(this.original.hiddenVal);
            this.$preview.html(this.original.preview);
            this.$element.find('.file-input-filename').text(this.placeholder);
            if (this.original.exists) {
                this.$element.addClass('file-input-exists').removeClass('file-input-new');
            } else {
                this.$element.addClass('file-input-new').removeClass('file-input-exists');
            }
            this.$element.trigger('reseted.bs.file-input');
            return this;
        }

        /**
         * Handle the change event.
         *
         * @param {Event} [e] - the source event.
         * @return {FileInput} this instance for chaining.
         * @private
         */
        _change(e) {
            if (e) {
                e.preventDefault();
            }
            const files = $.isUndefined(e.target.files) ? e.target && e.target.value ? [{name: e.target.value.replace(/^.+\\/, '')}] : [] : e.target.files;
            if (files.length === 0) {
                this._clear(e);
                return;
            }
            if (!this._verifySizes(files)) {
                this.$element.trigger('max_size.bs.file-input');
                this._clear(e);
                return;
            }
            this.$hidden.val('');
            this.$hidden.attr('name', '');
            this.$input.attr('name', this.name);
            const file = files[0];
            if (this.$preview.length && (!$.isUndefined(file.type) ? file.type.match(/^image\/(gif|png|bmp|jpeg|svg\+xml)$/) : file.name.match(/\.(gif|png|bmp|jpe?g|svg)$/i)) && !$.isUndefined(FileReader)) {
                this._loadImage(files);
            } else {
                let text = file.name;
                const $nameView = this.$element.find('.file-input-filename');
                if (files.length > 1) {
                    text = $.map(files, function (file) {
                        return file.name;
                    }).join(', ');
                }
                $nameView.text(text);
                this.$preview.text(file.name);
                this.$element.addClass('file-input-exists').removeClass('file-input-new');
                this.$element.trigger('input.bs.file-input');
                this.$input.trigger('input');
            }
            return this;
        }

        /**
         * Clear image.
         *
         * @param {Event} [e] - the source event.
         * @return {FileInput} this instance for chaining.
         * @private
         */
        _clear(e) {
            if (e) {
                e.preventDefault();
            }

            this.$hidden.val('');
            this.$hidden.attr('name', this.name);
            if (this.options.clearName) {
                this.$input.attr('name', '');
            }
            this.$input.val('');
            this.$element.find('.file-input-filename').text(this.placeholder);
            this.$element.addClass('file-input-new').removeClass('file-input-exists');
            // empty image
            let found = false;
            const $img = this.$preview.find('img');
            const defaultImage = this.$preview.data('default');
            if ($img.length && defaultImage) {
                if ($img.attr('src') !== defaultImage) {
                    $img.attr('src', defaultImage);
                }
                found = true;
            }
            if (!found) {
                this.$preview.html('');
            }
            if (e) {
                this.$element.trigger('clear.bs.file-input');
                this.$input.trigger('input');
            }
            this.$input.focus();
            return this;
        }

        /**
         * Validate the file sizes.
         * @param {File[]} files - the files to validate.
         * @return {boolean} true if valid; false otherwise.
         * @private
         */
        _verifySizes(files) {
            if ($.isUndefined(this.options.maxSize)) {
                return true;
            }
            const max = $.parseFloat(this.options.maxSize);
            if (isNaN(max) || max !== this.options.maxSize) {
                return true;
            }
            for (let i = 0, len = files.length; i < len; i++) {
                if ($.isUndefined(files[i].size)) {
                    continue;
                }
                let size = files[i].size;
                size = size / 1000 / 1000; /* convert from bytes to MB */
                if (size > max) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Load the image from the first given files.
         *
         * @param {File[]} files - the selected files.
         * @private
         */
        _loadImage(files) {
            const that = this;
            const file = files[0];
            const reader = new FileReader();
            const $preview = that.$preview;
            const $element = that.$element;
            reader.onload = function (event) {
                let $img = $preview.find('img');
                if (!$img || !$img.length) {
                    $img = $('<img />', {
                        alt: file.name
                    });
                }
                $img[0].src = event.target.result;
                file.result = event.target.result;
                $element.find('.file-input-filename').text(file.name);
                // if parent has max-height, using `(max-)height: 100%` on
                // child doesn't take padding and border into account
                if ($preview.css('max-height') !== 'none') {
                    const mh = parseInt($preview.css('max-height'), 10) || 0;
                    const pt = parseInt($preview.css('padding-top'), 10) || 0;
                    const pb = parseInt($preview.css('padding-bottom'), 10) || 0;
                    const bt = parseInt($preview.css('border-top'), 10) || 0;
                    const bb = parseInt($preview.css('border-bottom'), 10) || 0;
                    $img.css('max-height', mh - pt - pb - bt - bb);
                }
                $preview.html($img);
                if (that.options.exif) {
                    // Fix image transformation if this is possible
                    that._setImageTransform($img, file);
                }
                $element.addClass('file-input-exists').removeClass('file-input-new');
                $element.trigger('change.bs.file-input', files);
            };
            reader.readAsDataURL(file);
        }

        /**
         * Load the image.
         *
         * @param {JQuery} $image - the target image element.
         * @param {File} file - the file to load.
         * @private
         */
        _setImageTransform($image, file) {
            const that = this;
            const reader = new FileReader();
            reader.onload = function () {
                const view = new DataView(reader.result);
                const exif = that._getImageExif(view);
                if (exif) {
                    that._resetOrientation($image, exif);
                }
            };
            reader.readAsArrayBuffer(file);
        }

        /**
         * Gets the Exif image.
         *
         * @param {DataView} view - the data view.
         * @return {number} the result.
         * @private
         */
        _getImageExif(view) {
            if (view.getUint16(0, false) !== 0xFFD8) {
                return -2;
            }
            const length = view.byteLength;
            let offset = 2;
            while (offset < length) {
                const marker = view.getUint16(offset, false);
                offset += 2;
                if (marker === 0xFFE1) {
                    offset += 2;
                    if (view.getUint32(offset, false) !== 0x45786966) {
                        return -1;
                    }
                    const little = view.getUint16(offset += 6, false) === 0x4949;
                    offset += view.getUint32(offset + 4, little);
                    const tags = view.getUint16(offset, little);
                    offset += 2;
                    for (let i = 0; i < tags; i++) {
                        if (view.getUint16(offset + i * 12, little) === 0x0112) {
                            return view.getUint16(offset + i * 12 + 8, little);
                        }
                    }
                } else if ((marker && 0xFF00) !== 0xFF00) {
                    break;
                } else {
                    offset += view.getUint16(offset, false);
                }
            }
            return -1;
        }

        /**
         * Reset the image orientation.
         *
         * @param {JQuery} $image - the image element.
         * @param {number} transform - the transform value.
         * @private
         */
        _resetOrientation($image, transform) {
            const img = new Image();
            img.onload = function () {
                const width = img.width;
                const height = img.height;
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                // set proper canvas dimensions before transform & export
                if ([5, 6, 7, 8].indexOf(transform) > -1) {
                    canvas.width = height;
                    canvas.height = width;
                } else {
                    canvas.width = width;
                    canvas.height = height;
                }
                // transform context before drawing image
                switch (transform) {
                    case 2:
                        ctx.transform(-1, 0, 0, 1, width, 0);
                        break;
                    case 3:
                        ctx.transform(-1, 0, 0, -1, width, height);
                        break;
                    case 4:
                        ctx.transform(1, 0, 0, -1, 0, height);
                        break;
                    case 5:
                        ctx.transform(0, 1, 1, 0, 0, 0);
                        break;
                    case 6:
                        ctx.transform(0, 1, -1, 0, height, 0);
                        break;
                    case 7:
                        ctx.transform(0, -1, -1, 0, height, width);
                        break;
                    case 8:
                        ctx.transform(0, -1, 1, 0, 0, width);
                        break;
                    default:
                        ctx.transform(1, 0, 0, 1, 0, 0);
                }
                // draw image
                ctx.drawImage(img, 0, 0);
                // export base64
                $image.attr('src', canvas.toDataURL());
            };
            img.src = $image.attr('src');
        }
    };

    // -----------------------------
    // Default options
    // -----------------------------
    FileInput.DEFAULTS = {
        clearName: true
    };


    /**
     * The plugin name.
     */
    FileInput.NAME = 'bs.file-input';

    // ------------------------------------
    // FileInput plugin definition
    // ------------------------------------
    const oldFileInput = $.fn.fileinput;
    $.fn.fileinput = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            const data = $this.data(FileInput.NAME);
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data(FileInput.NAME, new FileInput(this, settings));
            }
        });
    };
    $.fn.fileinput.Constructor = FileInput;

    // ------------------------------------
    // FileInput no conflict
    // ------------------------------------
    $.fn.fileinput.noConflict = function () {
        $.fn.fileinput = oldFileInput;
        return this;
    };

    // ------------------------------------
    // FileInput data-api
    // ------------------------------------
    $(document).on('click.bs.file-input.data-api', '[data-provider="file-input"]', function (e) {
        // already initialized?
        const $this = $(this);
        if ($this.data(FileInput.NAME)) {
            return;
        }

        // initialize
        $this.fileinput($this.data());
        const $target = $(e.target);
        if ($target.is('img')) {
            $this.data(FileInput.NAME).selectFile(e);
        } else {
            const $closest = $target.closest('[data-dismiss="file-input"],[data-trigger="file-input"]');
            if ($closest.length > 0) {
                e.preventDefault();
                $closest.trigger('click.bs.file-input');
            }
        }
    });
}(jQuery));
