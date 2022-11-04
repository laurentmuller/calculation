/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // -----------------------------------
    // Table editor public class definition
    // -----------------------------------
    const TableEditor = class {

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
            this.options = $.extend(true, {}, TableEditor.DEFAULTS, options);
            this.options.dotCellClass = this._space2Dot(this.options.cellClass);
            this.options.dotInputClass = this._space2Dot(this.options.inputClass);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            // remove handlers
            const options = this.options;
            this.$element.off('click', options.dotCellClass, this.clickProxy);
            this.$element.off('keydown', options.dotInputClass, this.keydownProxy);
            this.$element.off('input', options.dotInputClass, this.inputProxy);
            this.$element.off('blur', options.dotInputClass, this.blurProxy);

            // remove
            this.$element.removeData(TableEditor.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        _init() {
            // proxies
            this.clickProxy = (e) => this._click(e);
            this.keydownProxy = (e) => this._keydown(e);
            this.inputProxy = (e) => this._input(e);
            this.blurProxy = (e) => this._blur(e);

            // add handlers
            const options = this.options;
            this.$element.on('click', options.dotCellClass, this.clickProxy);
            this.$element.on('keydown', options.dotInputClass, this.keydownProxy);
            this.$element.on('input', options.dotInputClass, this.inputProxy);
            this.$element.on('blur', options.dotInputClass, this.blurProxy);
        }

        /**
         * Handles the click event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _click(e) {
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
            if (this._isFunction(this.options.onCreateInput)) {
                this.options.onCreateInput(e, $input);
            }

            // show
            $input.trigger('select').trigger('focus');
        }

        /**
         * Handles the key down event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _keydown(e) {
            const $input = $(e.currentTarget);
            switch (e.which) {
                case 13: // enter
                    e.preventDefault();
                    this._save(e, $input);
                    break;
                case 27:// escape
                    e.preventDefault();
                    this._update(e, $input);
                    break;
                default:
                    if (this._isFunction(this.options.onKeyDown)) {
                        this.options.onKeyDown(e, $input);
                    }
                    break;
            }
        }

        /**
         * Handles the input event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _input(e) {
            if (this._isFunction(this.options.onInput)) {
                this.options.onInput(e, $(e.currentTarget));
            }
        }

        /**
         * Handles the blur event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _blur(e) {
            this._update(e, $(e.currentTarget));
        }

        _save(e, $input) {
            if (this._isFunction(this.options.onSave)) {
                this.options.onSave(e, $input);
            }
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                this._update(e, $input, $input.val());
            }
        }

        /**
         * Update the cell content.
         * @param {Event} e - the event.
         * @param {JQuery} $input - the input.
         * @param {String} [content] - the input content.
         * @private
         */
        _update(e, $input, content) {
            if (this._isFunction(this.options.onRemoveInput)) {
                this.options.onRemoveInput(e, $input);
            }
            const $parent = $input.parents('td');
            content = content || $input.data('content');
            $parent.html(content).removeClass('p-0');
            $input.remove();
        }

        /**
         * Remove consecutive spaces.
         *
         * @param {string} className
         * @return {string}
         * @private
         */
        _space2Dot(className) {
            className = className.replaceAll(/\s{2,}/g, ' ').trim();
            return '.' + className.replaceAll(' ', '.').trim();
        }

        /**
         * Returns if the given value is a function.
         *
         * @param {*} value - the value to be tested.
         * @return {boolean} true if a function.
         * @private
         */
        _isFunction(value) {
            return typeof value === 'function' && value !== $.noop;
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

    /**
     * The plugin name.
     */
    TableEditor.NAME = 'bs.table-editor';

    // -----------------------------------
    // TableEditor plugin definition
    // -----------------------------------
    const oldTableEditor = $.fn.tableEditor;
    $.fn.tableEditor = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(TableEditor.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(TableEditor.NAME, new TableEditor(this, settings));
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
