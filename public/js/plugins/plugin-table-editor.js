/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * Table editor.
     */
    const TableEditor = class {
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, TableEditor.DEFAULTS, options);
            this.options.dotCellClass = this.space2Dot(this.options.cellClass);
            this.options.dotInputClass = this.space2Dot(this.options.inputClass);
            this.init();
        }

        init() {
            // proxies
            const that = this;
            that.clickProxy = function (e) {
                that.click(e);
            };
            that.keydownProxy = function (e) {
                that.keydown(e);
            };
            that.inputProxy = function (e) {
                that.input(e);
            };
            that.blurProxy = function (e) {
                that.blur(e);
            };

            // add handlers
            const options = this.options;
            that.$element.on('click', options.dotCellClass, that.clickProxy);
            that.$element.on('keydown', options.dotInputClass, that.keydownProxy);
            that.$element.on('input', options.dotInputClass, that.inputProxy);
            that.$element.on('blur', options.dotInputClass, that.blurProxy);
        }

        destroy() {
            // remove handlers
            const options = this.options;
            this.$element.off('click', options.dotCellClass, this.clickProxy);
            this.$element.off('keydown', options.dotInputClass, this.keydownProxy);
            this.$element.off('input', options.dotInputClass, this.inputProxy);
            this.$element.off('blur', options.dotInputClass, this.blurProxy);

            // remove
            this.$element.removeData('tableEditor');
        }

        click(e) {
            const options = this.options;
            const $this = $(e.currentTarget);

            // already editing?
            if ($this.find(options.dotInputClass).length) {
                return;
            }

            // create input
            const content = $this.html();
            const css = $.extend(true, {
                'text-align': $this.css('text-align'),
                'height': $this.innerHeight(),
                'width': $this.innerWidth()
            }, options.inputCss);

            const $input = $('<input/>', {
                'type': options.inputType,
                'class': options.inputClass,
                'css': css,
                'data': {
                    'content': content,
                },
                'value': content
            });

            // update
            $this.addClass('p-0').html($input);

            // handler?
            if (this.isFunction(this.options.onCreateInput)) {
                this.options.onCreateInput(e, $input);
            }

            // show
            $input.select().focus();
        }

        keydown(e) {
            const $input = $(e.currentTarget);
            const keyCode = e.keyCode || e.which;

            switch (keyCode) {
            case 13: // enter
                e.stopPropagation();
                this.save(e, $input);
                break;

            case 27:// escape
                e.preventDefault();
                e.stopPropagation();
                this.update(e, $input);
                break;

            default:
                if (this.isFunction(this.options.onKeyDown)) {
                    this.options.onKeyDown(e, $input);
                }
                break;
            }
        }

        input(e) {
            if (this.isFunction(this.options.onInput)) {
                this.options.onInput(e, $(e.currentTarget));
            }
        }

        blur(e) {
            this.update(e, $(e.currentTarget));
        }

        save(e, $input) {
            if (this.isFunction(this.options.onSave)) {
                this.options.onSave(e, $input);
            }
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                this.update(e, $input, $input.val());
            }
        }

        update(e, $input, content) {
            const $parent = $input.parents('td');
            if (this.isFunction(this.options.onRemoveInput)) {
                this.options.onRemoveInput(e, $input);
            }
            content = content || $input.data('content');
            $parent.html(content).removeClass('p-0');
            $input.remove();
        }

        space2Dot(className) {
            // remove consecutive spaces
            className = className.replaceAll(/\s{2,}/g, ' ').trim();
            return '.' + className.replaceAll(' ', '.').trim();
        }

        isFunction(value) {
            return typeof value === 'function' &&  value !== $.noop;
        }
    };

    TableEditor.DEFAULTS = {
        'cellClass': 'cell-editable',
        'inputType': 'text',
        'inputClass': 'form-control cell-editor',
        'inputCss': {},
        'onCreateInput': $.noop,
        'onRemoveInput': $.noop,
        'onKeyDown': $.noop,
        'onInput': $.noop,
        'onSave': $.noop,
    };

    // TableEditor plugin definition
    const oldTableEditor = $.fn.tableEditor;
    $.fn.tableEditor = function (options) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('tableEditor');
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data('tableEditor', data = new TableEditor(this, settings));
            }
        });
    };
    $.fn.tableEditor.Constructor = TableEditor;

    // TableEditor no conflict
    $.fn.tableEditor.noConflict = function () {
        $.fn.tableEditor = oldTableEditor;
        return this;
    };

}(jQuery));
