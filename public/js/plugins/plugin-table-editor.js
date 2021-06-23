/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * Table editor.
     */
    var TableEditor = function (element, options) {
        this.$element = $(element);
        this.options = $.extend(true, {}, TableEditor.DEFAULTS, options);
        this.options.dotCellClass = this.space2Dot(this.options.cellClass);
        this.options.dotInputClass = this.space2Dot(this.options.inputClass);
        this.init();
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

    TableEditor.prototype = {

        constructor: TableEditor,

        init: function () {
            // proxies
            this.clickProxy = $.proxy(this.click, this);
            this.keydownProxy = $.proxy(this.keydown, this);
            this.inputProxy = $.proxy(this.input, this);
            this.blurProxy = $.proxy(this.blur, this);

            // add handlers
            const options = this.options;
            this.$element.on('click', options.dotCellClass, this.clickProxy);
            this.$element.on('keydown', options.dotInputClass, this.keydownProxy);
            this.$element.on('input', options.dotInputClass, this.inputProxy);
            this.$element.on('blur', options.dotInputClass, this.blurProxy);
        },

        destroy: function () {
            // remove handlers
            const options = this.options;
            this.$element.off('click', options.dotCellClass, this.clickProxy);
            this.$element.off('keydown', options.dotInputClass, this.keydownProxy);
            this.$element.off('input', options.dotInputClass, this.inputProxy);
            this.$element.off('blur', options.dotInputClass, this.blurProxy);

            // remove
            this.$element.removeData('tableEditor');
        },

        click: function (e) {
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
        },

        keydown: function (e) {
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
        },

        input: function (e) {
            if (this.isFunction(this.options.onInput)) {
                this.options.onInput(e, $(e.currentTarget));
            }
        },

        blur: function (e) {
            this.update(e, $(e.currentTarget));
        },

        save: function (e, $input) {
            if (this.isFunction(this.options.onSave)) {
                this.options.onSave(e, $input);
            }
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                this.update(e, $input, $input.val());
            }
        },

        update: function (e, $input, content) {
            const $parent = $input.parents('td');
            if (this.isFunction(this.options.onRemoveInput)) {
                this.options.onRemoveInput(e, $input);
            }
            content = content || $input.data('content');
            $parent.html(content).removeClass('p-0');
            $input.remove();
        },

        space2Dot: function (className) {
            // remove consecutive spaces
            className = className.replaceAll(/\s{2,}/g, ' ').trim();
            return '.' + className.replaceAll(' ', '.').trim();
        },

        isFunction: function (value) {
            if (typeof value === 'function') {
                return value !== $.noop;
            }
            return false;
        },
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
