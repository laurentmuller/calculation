/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    
    "use strict";

    const isIE = window.navigator.appName === 'Microsoft Internet Explorer';

    // ------------------------------------
    // Fileinput public class definition
    // ------------------------------------
    var Fileinput = function(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, Fileinput.DEFAULTS, options);
        
        this.$input = this.$element.find(':file');
        if (this.$input.length === 0) {
            return;
        }
        
        this.name = this.$input.attr('name') || options.name;
        this.placeholder = this.$input.attr('placeholder') || options.placeholder || '';

        this.$hidden = this.$element.find('input[type=hidden][name="' + this.name + '"]');
        if (this.$hidden.length === 0) {
            this.$hidden = $('<input type="hidden">').insertBefore(this.$input);
        }

        this.$preview = this.$element.find('.fileinput-preview');
        
        this.original = {
            exists: this.$element.hasClass('fileinput-exists'),
            preview: this.$preview.html(),
            hiddenVal: this.$hidden.val()
        };

        this.listen();
        this.reset();
    };

    // -----------------------------
    // Default options
    // -----------------------------
    Fileinput.DEFAULTS = {
        clearName: true
    };

    Fileinput.prototype.listen = function() {
        this.$input.on('change.bs.fileinput', $.proxy(this.change, this));
        $(this.$input[0].form).on('reset.bs.fileinput', $.proxy(this.reset, this));

        this.$element.find('[data-trigger="fileinput"]').on('click.bs.fileinput', $.proxy(this.trigger, this));
        this.$element.find('[data-dismiss="fileinput"]').on('click.bs.fileinput', $.proxy(this.clear, this));
    };

    Fileinput.prototype.verifySizes = function(files) {
        if ($.isUndefined( this.options.maxSize)) {
            return true;
        }

        const max = parseFloat(this.options.maxSize);
        if (max !== this.options.maxSize) {
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
    };

    Fileinput.prototype.change = function(e) {
        e.stopPropagation();
        const files = $.isUndefined(e.target.files) ? e.target && e.target.value ? [{name: e.target.value.replace(/^.+\\/, '')}] : [] : e.target.files;
        if (files.length === 0) {
            this.clear();
            this.$element.trigger('clear.bs.fileinput');
            return;
        }

        if (!this.verifySizes(files)) {
            this.$element.trigger('max_size.bs.fileinput');
            this.clear();
            this.$element.trigger('clear.bs.fileinput');
            return;
        }

        this.$hidden.val('');
        this.$hidden.attr('name', '');
        this.$input.attr('name', this.name);

        const file = files[0];

        if (this.$preview.length > 0 && (!$.isUndefined(file.type) ? file.type.match(/^image\/(gif|png|bmp|jpeg|svg\+xml)$/) : file.name.match(/\.(gif|png|bmp|jpe?g|svg)$/i)) && !$.isUndefined(FileReader)) {
            const Fileinput = this;
            const reader = new FileReader();
            const preview = this.$preview;
            const element = this.$element;

            reader.onload = function(event) {
                let $img = preview.find('img');
                if (!$img || !$img.length) {
                    $img = $('<img>');
                }
                $img[0].src = event.target.result;
                files[0].result = event.target.result;

                element.find('.fileinput-filename').text(file.name);

                // if parent has max-height, using `(max-)height: 100%` on child
                // doesn't take padding and border into account
                if (preview.css('max-height') !== 'none') {
                    const mh = parseInt(preview.css('max-height'), 10) || 0;
                    const pt = parseInt(preview.css('padding-top'), 10) || 0;
                    const pb = parseInt(preview.css('padding-bottom'), 10) || 0;
                    const bt = parseInt(preview.css('border-top'), 10) || 0;
                    const bb = parseInt(preview.css('border-bottom'), 10) || 0;
                    $img.css('max-height', mh - pt - pb - bt - bb);
                }

                preview.html($img);
                if (Fileinput.options.exif) {
                    // Fix image tranformation if this is possible
                    Fileinput.setImageTransform($img, file);
                }
                element.addClass('fileinput-exists').removeClass('fileinput-new');
                element.trigger('change.bs.fileinput', files);
            };

            reader.readAsDataURL(file);
        } else {
            let text = file.name;
            const $nameView = this.$element.find('.fileinput-filename');

            if (files.length > 1) {
                text = $.map(files, function(file) {
                    return file.name;
                }).join(', ');
            }

            $nameView.text(text);
            this.$preview.text(file.name);
            this.$element.addClass('fileinput-exists').removeClass('fileinput-new');
            this.$element.trigger('change.bs.fileinput');
        }
    };

    Fileinput.prototype.setImageTransform = function($img, file) {
        const Fileinput = this;
        const reader = new FileReader();
        reader.onload = function() {
            const view = new DataView(reader.result);
            const exif = Fileinput.getImageExif(view);
            if (exif) {
                Fileinput.resetOrientation($img, exif);
            }
        };

        reader.readAsArrayBuffer(file);
    };

    Fileinput.prototype.getImageExif = function(view) {
        if (view.getUint16(0, false) !== 0xFFD8) {
            return -2;
        }
        const length = view.byteLength;
        let offset = 2;
        while (offset < length) {
            const marker = view.getUint16(offset, false);
            offset += 2;
            if (marker === 0xFFE1) {
                if (view.getUint32(offset += 2, false) !== 0x45786966) {
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
    };

    Fileinput.prototype.resetOrientation = function($img, transform) {
        const img = new Image();

        img.onload = function() {
            const width = img.width;
            const height = img.height;
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext("2d");

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
            $img.attr('src', canvas.toDataURL());
        };

        img.src = $img.attr('src');
    };

    Fileinput.prototype.clear = function(e) {
        if (e) {
            e.preventDefault();
        }

        this.$hidden.val('');
        this.$hidden.attr('name', this.name);
        if (this.options.clearName) {
            this.$input.attr('name', '');
        }

        // ie8+ doesn't support changing the value of input with type=file so
        // clone instead
        if (isIE) {
            const inputClone = this.$input.clone(true);
            this.$input.after(inputClone);
            this.$input.remove();
            this.$input = inputClone;
        } else {
            this.$input.val('');
        }

        this.$preview.html('');
        this.$element.find('.fileinput-filename').text(this.placeholder);
        this.$element.addClass('fileinput-new').removeClass('fileinput-exists');
        
        if (!$.isUndefined(e)) {
            this.$input.trigger('change');
            this.$element.trigger('clear.bs.fileinput');            
        }
        this.$input.focus();
    };

    Fileinput.prototype.reset = function() {
        this.clear();
        this.$hidden.val(this.original.hiddenVal);
        this.$preview.html(this.original.preview);
        this.$element.find('.fileinput-filename').text(this.placeholder);

        if (this.original.exists) {
            this.$element.addClass('fileinput-exists').removeClass('fileinput-new');
        } else {
            this.$element.addClass('fileinput-new').removeClass('fileinput-exists');
        }

        this.$element.trigger('reseted.bs.fileinput');
    };

    Fileinput.prototype.trigger = function(e) {
        this.$input.trigger('click');
        e.preventDefault();
    };


    // ------------------------------------
    // FileInput plugin definition
    // ------------------------------------
    const oldFileInput = $.fn.fileinput;

    $.fn.fileinput = function(options) {
        return this.each(function() {
            const $this = $(this);
            let data = $this.data('bs.fileinput');
            if (!data) {
                const settings = typeof options === "object" && options;
                $this.data('bs.fileinput', data = new Fileinput(this, settings));
            }
        });
    };

    $.fn.fileinput.Constructor = Fileinput;

    // ------------------------------------
    // FileInput no conflict
    // ------------------------------------
    $.fn.fileinput.noConflict = function() {
        $.fn.fileinput = oldFileInput;
        return this;
    };

    // ------------------------------------
    // FileInput data-api
    // ------------------------------------
    $(document).on('click.fileinput.data-api', '[data-provides="fileinput"]', function(e) {
        const $this = $(this);
        if ($this.data('bs.fileinput')) {
            return;
        }

        $this.fileinput($this.data());
        
        const $target = $(e.target);
        if ($target.is('img')) {
            $this.data('bs.fileinput').trigger(e);
        } else {
            const $closest = $target.closest('[data-dismiss="fileinput"],[data-trigger="fileinput"]');
            if ($closest.length > 0) {
                e.preventDefault();
                $closest.trigger('click.bs.fileinput');
            }
        }
    });

})(jQuery);