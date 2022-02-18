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
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, CellEdit.DEFAULTS, this.$element.data(), options);
            this.$target = $(this.options.target);
            if (this.$target && this.$target.length === 0) {
                this.$target = null;
            }
            this.$element.on('click', $.proxy(this._click, this));
            if (this.options.autoEdit) {
                this.$element.trigger('click');
            }
        }

        destroy() {
            if (this.$input) {
                this._cancel($.Event('blur'), false); // jslint ignore:line
            }
            this.$element.off('click', $.proxy(this._click, this));
            this.$element.removeData('cell-edit');
        }

        // -----------------------------
        // private functions
        // -----------------------------
        _click(e) {
            e.stopPropagation();
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
            const valid = !required || ('' + this.value);
            const className = valid ? options.inputClass : options.inputClass + ' is-invalid';
            const title = valid ? options.tooltipEdit : options.tooltipError ;
            const customClass = valid ? options.tooltipEditClass : options.tooltipErrorClass;

            this.$input = $('<input>', {
                'type': options.type,
                'value': this.value,
                'required': required,
                'class': className,
                'title': title,
                'data-custom-class': customClass,
            });

            this.$element.addClass(options.cellClass);
            this.$element.empty().append(this.$input);
            this.$element.parents('tr').addClass(options.rowClass);

            this.$input.on('blur', $.proxy(this._blur, this));
            this.$input.on('input', $.proxy(this._input, this));
            this.$input.on('keydown', $.proxy(this._keydown, this));
            if ($.isFunction(options.onStartEdit)) {
                options.onStartEdit();
            }
            this.$input.select().focus();
            if (title) {
                this.$input.tooltip('show');
            }

            return this;
        }

        _blur(e) {
            return this._cancel(e);
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
            switch (e.which) {
                case 13: // enter
                    return this._update(e);
                case 27: // escape
                    return this._cancel(e);
                default:
                    return this;
            }
        }

        _update(e) {
            e.stopPropagation();
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
            if ($.isFunction(options.onEndEdit)) {
                this.value = newValue;
                options.onEndEdit(oldValue, newValue);
            }
            if (options.autoDispose) {
                this.destroy();
            }
            return this;
        }

        _cancel(e, notify = true) {
            if (e) {
                e.stopPropagation();
            }
            if (this.$input) {
                this.$input.off('blur', $.proxy(this._blur, this));
                this.$input.off('input', $.proxy(this._input, this));
                this.$input.off('keydown', $.proxy(this._keydown, this));
                this.$input.tooltip('dispose');
                this.$input.remove();
                this.$input = null;
            }
            const options = this.options;
            this.$element.html(this.html || '');
            this.$element.removeClass(options.cellClass);
            this.$element.parents('tr').removeClass(options.rowClass);

            if (notify && $.isFunction(options.onCancelEdit)) {
                options.onCancelEdit();
            }
            if (notify && options.autoDispose) {
                this.destroy();
            }
            return this;
        }

        _removeInput() {
            if (this.$input) {
                this.$input.off('blur', $.proxy(this._blur, this));
                this.$input.off('input', $.proxy(this._input, this));
                this.$input.off('keydown', $.proxy(this._keydown, this));
                this.$input.tooltip('dispose');
                this.$input.remove();
                this.$input = null;
            }
        }

        _parse(value) {
            if ($.isFunction(this.options.parser)) {
                return this.options.parser(value);
            }
            return value;
        }

        _format(value) {
            if ($.isFunction(this.options.formatter)) {
                return this.options.formatter(value);
            }
            return value;
        }
    };

    // -----------------------------
    // CellEdit default options
    // -----------------------------
    CellEdit.DEFAULTS = {
        'type': 'text', // the input type
        'required': false, // the required input attribute

        'inputClass': 'form-control form-control-sm m-0', // the input class
        'cellClass': 'pt-1 pb-1', // the cell class to add when editing
        'rowClass': 'table-primary', // the row class to add when editing

        'tooltipEdit': 'Enter the value', // the edit tooltip
        'tooltipError': 'The value can not be empty.', // the error tooltip
        'tooltipEditClass': 'tooltip-secondary', // the edit tooltip class
        'tooltipErrorClass': 'tooltip-danger', // the error tooltip class

        'autoEdit': false, // the start edit on create
        'autoDispose': false, // the destroy on end edit or on cancel

        'parser': null, // the function to parser value
        'formatter': null, // the function to format value

        'onStartEdit': null, // the function on start edit event
        'onEndEdit': null, // the function on end edit event
        'onCancelEdit': null, // the function on the cancel edit event
    };

    // -------------------------------
    // CellEdit plugin definition
    // -------------------------------
    const oldCellEdit = $.fn.celledit;
    $.fn.celledit = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('cell-edit');
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data('cell-edit', data = new CellEdit(this, settings));
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
