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

            // check functions
            this.options.onStartEdit = this._checkFunction(this.options.onStartEdit);
            this.options.onCancelEdit = this._checkFunction(this.options.onCancelEdit);
            this.options.onEndEdit = this._checkFunction(this.options.onEndEdit);

            this.options.parser = this._checkFunction(this.options.parser);
            this.options.formatter = this._checkFunction(this.options.formatter);

            // proxies
            const that = this;
            this.clickProxy = function (e) {
                that._click(e);
            };
            this.blurProxy = function (e) {
                that._blur(e);
            };
            this.inputProxy = function () {
                that._input();
            };
            this.keydownProxy = function (e) {
                that._keydown(e);
            };
            this.$element.on('click', this.clickProxy);
            if (this.options.autoEdit) {
                this.$element.trigger('click');
            }
        }

        destroy() {
            if (this.$input) {
                this._cancel(null, false);
            }
            this.$element.off('click', this.clickProxy)
                .removeData('cell-edit');
        }

        /**
         * Gets the input element.
         *
         * @return {jQuery|null|JQuery<HTMLElement>}
         */
        getInput() {
            return this.$input;
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
            const valid = !required || '' + this.value;
            const className = valid ? options.inputClass : options.inputClass + ' is-invalid';
            const customClass = valid ? options.tooltipEditClass : options.tooltipErrorClass;
            const title = valid ? options.tooltipEdit : options.tooltipError;

            //$.extend({}, object1, object2);

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
                    this.$input.data('input-number-format').destroy();
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
        'type': 'text', // the input type
        'required': false, // the required input attribute
        'inputClass': 'form-control form-control-sm m-0', // the input class
        'cellClass': 'pt-1 pb-1', // the cell class to add when editing
        'rowClass': 'table-primary', // the row class to add when editing
        'attributes': {}, // the input attributes to merge

        'useNumberFormat': false, // true to use the input number plugin
        'numberFormatOptions': {}, // the options to use with the input number plugin

        'tooltipEdit': 'Enter the value', // the edit tooltip
        'tooltipError': 'The value can not be empty.', // the error tooltip
        'tooltipEditClass': 'tooltip-secondary', // the edit tooltip class
        'tooltipErrorClass': 'tooltip-danger', // the error tooltip class

        'autoEdit': false, // start edit on create
        'autoDispose': false, // destroy on end edit or on cancel

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
            if (!$this.data('cell-edit')) {
                const settings = typeof options === 'object' && options;
                $this.data('cell-edit', new CellEdit(this, settings));
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
