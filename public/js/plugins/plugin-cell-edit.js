/**! compression tag for ftp-deployment */

/**
 * Plugin to edit the value of a table cell.
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // CellEdit public class definition
    // ------------------------------------
    const CellEdit = class {

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
            this.options = $.extend(true, CellEdit.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            if (this.$input) {
                this._cancel(null, false);
            }
            this.$element.off('click', this.clickProxy)
                .removeData(CellEdit.NAME);
        }

        /**
         * Gets the input element.
         *
         * @return {JQuery|null|JQuery<HTMLElement>}
         */
        getInput() {
            return this.$input;
        }

        // -----------------------------
        // private functions
        // -----------------------------

        _init() {
            const options = this.options;
            this.$target = $(options.target);
            if (this.$target && this.$target.length === 0) {
                this.$target = null;
            }

            // check functions
            options.onStartEdit = this._checkFunction(options.onStartEdit);
            options.onCancelEdit = this._checkFunction(options.onCancelEdit);
            options.onEndEdit = this._checkFunction(options.onEndEdit);

            options.parser = this._checkFunction(options.parser);
            options.formatter = this._checkFunction(options.formatter);

            // proxies
            this.clickProxy = e => this._click(e);
            this.blurProxy = e => this._blur(e);
            this.inputProxy = () => this._input();
            this.keydownProxy = e => this._keydown(e);

            this.$element.on('click', this.clickProxy);
            if (options.autoEdit) {
                this.$element.trigger('click');
            }
        }

        _click(e) {
            if (e) {
                e.stopPropagation();
            }
            if (this.$input && this.$input.is(':focus')) {
                return this;
            }

            const options = this.options;
            this.value = this.html = this.$element.html();
            if (this.$target) {
                this.value = this.$target.val();
            }
            this.value = this._parse(this.value);

            const required = options.required;
            const valid = !required || '' + this.value;
            const className = valid ? options.inputClass : options.inputClass + ' is-invalid';
            const customClass = valid ? options.tooltipEditClass : options.tooltipErrorClass;
            const title = valid ? options.tooltipEdit : options.tooltipError;

            const attributes = $.extend(true, {
                'data-custom-class': customClass,
                'type': options.type,
                'required': required,
                'value': this.value,
                'class': className,
                'title': title
            }, options.attributes);
            this.$input = $('<input>', attributes);

            this.$element.addClass(options.cellClass)
                .empty().append(this.$input)
                .parents('tr').addClass(options.rowClass);

            this.$input.on('blur', this.blurProxy)
                .on('input', this.inputProxy)
                .on('keydown', this.keydownProxy);

            if (options.useNumberFormat) {
                this.$input.inputNumberFormat(options.numberFormatOptions);
            }
            if (options.onStartEdit) {
                options.onStartEdit();
            }
            this.$input.trigger('select').trigger('focus');
            if (title) {
                this.$input.tooltip('show');
            }

            return this;
        }

        _blur(e) {
            return this._cancel(e, true);
        }

        _input() {
            const options = this.options;
            const required = options.required;
            const valid = !required || this.$input.val();
            const title = valid ? options.tooltipEdit : options.tooltipError;
            if (this.$input.attr('data-original-title') === title) {
                return;
            }
            if (valid) {
                this.$input.removeClass('is-invalid');
                this.$input.data('customClass', options.tooltipEditClass);
            } else {
                this.$input.addClass('is-invalid');
                this.$input.data('customClass', options.tooltipErrorClass);
            }
            if (title) {
                this.$input.attr('title', title).tooltip('dispose').tooltip('toggle');
            }

            return this;
        }

        _keydown(e) {
            if (e) {
                switch (e.which) {
                    case 13: // enter
                        return this._update(e);
                    case 27: // escape
                        return this._cancel(e, true);
                    default:
                        return this;
                }
            }
        }

        _update(e) {
            if (e) {
                e.stopPropagation();
            }
            if (!this.$input.val()) {
                return;
            }
            const options = this.options;
            const oldValue = this.value;
            const newValue = this._parse(this.$input.val());
            this.html = this._format(newValue);
            this._cancel(e, false);

            // copy if applicable
            if (this.$target && oldValue !== newValue) {
                this.$target.val(newValue);
            }

            // notify
            if (options.onEndEdit) {
                this.value = newValue;
                options.onEndEdit(oldValue, newValue);
            }
            if (options.autoDispose) {
                this.destroy();
            }
            return this;
        }

        _cancel(e, notify) {
            if (e) {
                e.stopPropagation();
            }
            const options = this.options;
            if (this.$input) {
                if (options.useNumberFormat) {
                    this.$input.data('bs.input-number-format').destroy();
                }
                this.$input.off('blur', this.blurProxy)
                    .off('input', this.inputProxy)
                    .off('keydown', this.keydownProxy)
                    .tooltip('dispose');
                this.$input.remove();
                this.$input = null;
            }
            this.$element.html(this.html || '')
                .removeClass(options.cellClass)
                .parents('tr').removeClass(options.rowClass);
            if (notify && options.onCancelEdit) {
                options.onCancelEdit();
            }
            if (notify && options.autoDispose) {
                this.destroy();
            }
            return this;
        }

        _parse(value) {
            if (this.options.parser) {
                return this.options.parser(value);
            }
            return value;
        }

        _format(value) {
            if (this.options.formatter) {
                return this.options.formatter(value);
            }
            return value;
        }

        _checkFunction(value) {
            return typeof value === 'function' ? value : false;
        }
    };

    // -----------------------------
    // CellEdit default options
    // -----------------------------
    CellEdit.DEFAULTS = {
        // the input type
        'type': 'text',
        // the required input attribute
        'required': false,
        // the input class
        'inputClass': 'form-control form-control-sm m-0',
        // the cell class to add when editing
        'cellClass': 'pt-1 pb-1',
        // the row class to add when editing
        'rowClass': 'table-primary',
        // the input attributes to merge
        'attributes': {},
        // true to use the input number plugin
        'useNumberFormat': false,
        // the options to use with the input number plugin
        'numberFormatOptions': {},
        // the edit tooltip
        'tooltipEdit': 'Enter the value',
        // the error tooltip
        'tooltipError': 'The value can not be empty.',
        // the edit tooltip class
        'tooltipEditClass': 'tooltip-secondary',
        // the error tooltip class
        'tooltipErrorClass': 'tooltip-danger',
        // start edit on create
        'autoEdit': false,
        // destroy on end edit or on cancel
        'autoDispose': false,
        // the function to parse the value
        'parser': null,
        // the  function to format the value
        'formatter': null,
        // the function on start edit event
        'onStartEdit': null,
        // the function on end edit event
        'onEndEdit': null,
        // the function on the cancel edit event
        'onCancelEdit': null,
    };

    /**
     * The plugin name.
     */
    CellEdit.NAME = 'cell-edit';

    // -------------------------------
    // CellEdit plugin definition
    // -------------------------------
    const oldCellEdit = $.fn.celledit;
    $.fn.celledit = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(CellEdit.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(CellEdit.NAME, new CellEdit(this, settings));
            }
        });
    };
    $.fn.celledit.Constructor = CellEdit;

    // ------------------------------------
    // CellEdit no conflict
    // ------------------------------------
    $.fn.celledit.noConflict = function () {
        $.fn.celledit = oldCellEdit;
        return this;
    };
}(jQuery));
