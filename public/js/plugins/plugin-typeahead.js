/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // Typeahead public class definition
    // ------------------------------------
    const Typeahead = class {

        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor.
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} [options] - the options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, Typeahead.DEFAULTS, options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            // remove element handlers
            const that = this;
            const $element = this.$element;
            $element.off('focus', that.focusProxy);
            $element.off('blur', that.blurProxy);
            $element.off('keypress', that.keyPressProxy);
            $element.off('keyup', that.keyUpProxy);
            $element.off('keydown', that.keyDownProxy);

            // remove menu handlers
            const $menu = this.$menu;
            const selector = this.options.selector;
            $menu.off('click', that.clickProxy);
            $menu.off('mouseenter', selector, that.mouseEnterProxy);
            $menu.off('mouseleave', selector, that.mouseLeaveProxy);
            $menu.remove();

            // remove data
            $element.removeData(Typeahead.NAME);
        }

        /**
         * Show the drop-down menu.
         *
         * @return {Typeahead} this instance for chaining.
         */
        show() {
            if (!this._hasItems()) {
                return this;
            }
            const pos = $.extend({}, this.$element.position(), {
                height: this.$element[0].offsetHeight + 2
            });
            let width = '';
            if (this.options.alignWidth) {
                width = $(this.$element[0]).outerWidth();
            }
            this.$menu.css({
                top: pos.top + pos.height,
                left: pos.left,
                width: width
            });
            //this.$menu.dropdown('show');
            this.$menu.show();
            this.visible = true;
            return this;
        }

        /**
         * Hide the drop-down menu.
         *
         * @return {Typeahead} this instance for chaining.
         */
        hide() {
            if (this.visible) {
                //this.$menu.dropdown('hide');
                this.$menu.hide();
                this.visible = false;
            }
            return this;
        }

        /**
         * Render the items.
         * @param {Array.<Object>} items - the items to render.
         * @return {Typeahead} this instance for chaining.
         */
        render(items) {
            const data = [];
            const that = this;

            let display;
            let newSeparator;
            let oldSeparator = null;
            const separator = that.options.separator;
            const isStringSeparator = that._isString(separator);
            const isStringDisplay = that._isString(that.displayField);

            // run over items, add separators and categories if applicable
            $.each(items, function (key, value) {
                // get separators
                newSeparator = isStringSeparator ? value[separator] : separator(value);
                if (key > 0) {
                    oldSeparator = isStringSeparator ? items[key - 1][separator] : separator(items[key - 1]);
                }

                // inject separator
                if (key > 0 && newSeparator !== oldSeparator) {
                    data.push({
                        __type__: 'divider'
                    });
                }

                // inject category
                if (newSeparator && (key === 0 || newSeparator !== oldSeparator)) {
                    data.push({
                        __type__: 'category',
                        name: newSeparator
                    });
                }

                // inject value
                data.push(value);
            });

            // render categories, separators and items
            that.$menu.html($(data).map(function (_index, item) {
                // category
                if (item.__type__ === 'category') {
                    const html = that.highlighter(item.name);
                    return $(that.options.header).html(html)[0];
                }
                // separator
                if (item.__type__ === 'divider') {
                    return $(that.options.divider)[0];
                }
                // item
                if (that._isObject(item)) {
                    display = isStringDisplay ? item[that.displayField] : that.displayField(item);
                } else {
                    display = item;
                }
                const value = JSON.stringify(item);
                const html = that.highlighter(display);
                return $(that.options.item).data('value', value).html(html)[0];
            }));
            if (that.options.autoSelect) {
                that._first();
            }
            return that;
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _init() {
            // ajax?
            if (!this.options.ajax) {
                throw new Error('The ajax option must be set!');
            }

            // create menu
            this.$menu = $(this.options.menu).insertAfter(this.$element);

            // method overrides
            this.onSelect = this.options.onSelect || null;
            this.onError = this.options.onError || null;
            this.select = this.options.select || this.select;
            this.displayField = this.options.displayField;
            this.valueField = this.options.valueField;

            // ajax
            const ajax = this.options.ajax;
            if (this._isString(ajax)) {
                this.ajax = $.extend({}, Typeahead.DEFAULTS.ajax, {
                    url: ajax
                });
            } else {
                this.ajax = $.extend({}, Typeahead.DEFAULTS.ajax, ajax);
            }
            if (!this.ajax.url) {
                throw new Error('The ajax URL option must be set!');
            }

            this.ajaxExecuteProxy = () => this._ajaxExecute();
            this.ajaxSuccessProxy = (data) => this._ajaxSuccess(data);
            this.ajaxErrorProxy = (jqXHR, textStatus, errorThrown) => this._ajaxError(jqXHR, textStatus, errorThrown);
            this.visible = false;
            this.query = '';
            this._listen();
            return this;
        }

        /**
         * Returns if the given data is a string.
         * @param {*} data - the data to test.
         * @return {boolean} true if a string.
         * @private
         */
        _isString(data) {
            return typeof data === 'string';
        }

        /**
         * Returns if the given data is an object.
         * @param {*} data - the data to test.
         * @return {boolean} true if an object.
         * @private
         */
        _isObject(data) {
            return typeof data === 'object';
        }

        /**
         * Returns if the given data is a function.
         * @param {*} data - the data to test.
         * @return {boolean} true if a function.
         * @private
         */
        _isFunction(data) {
            return typeof data === 'function';
        }

        /**
         * Select the active menu item.
         *
         * @return {Typeahead}  this instance for chaining.
         * @private
         */
        _select() {
            const $selectedItem = this.$menu.find('.active');
            if ($selectedItem.length) {
                let item = JSON.parse($selectedItem.data('value'));
                let text = $selectedItem.text();
                if (this.valueField) {
                    text = item[this.valueField];
                }
                if (this.options.copyText) {
                    this.$element.val(text);
                }
                this.$element.trigger('change');
                if (this._isFunction(this.onSelect)) {
                    this.onSelect(item);
                }
            }
            return this.hide();
        }


        /**
         * Call ajax function.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _ajaxLookup() {
            if (!this._isQueryText()) {
                return this.hide();
            }
            const query = this._getQueryText();
            if (query === this.query) {
                if (!this.visible) {
                    this._first();
                    this.show();
                }
                return this;
            }

            // query changed
            this.query = query;

            // cancel last timer if set
            this._clearTimeout();

            if (!query || query.length < this.ajax.triggerLength) {
                // cancel the ajax callback if in progress
                this._abortAjax();
                return this.hide();
            }

            // query is good to send, set a timer
            this.ajax.timerId = setTimeout(this.ajaxExecuteProxy, this.ajax.timeout);
            return this;
        }

        /**
         * Execute the ajax function.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _ajaxExecute() {
            // cancel last call if already in progress
            this._abortAjax();
            const query = this.query;
            const data = this._isFunction(this.ajax.preDispatch) ? this.ajax.preDispatch(query) : {
                query: query
            };
            this.ajax.xhr = $.getJSON({
                success: this.ajaxSuccessProxy,
                error: this.ajaxErrorProxy,
                url: this.ajax.url,
                data: data
            });
            this.ajax.timerId = null;
            return this;
        }

        /**
         * Handle the ajax success call.
         *
         * @param {Object} data - the ajax data.
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _ajaxSuccess(data) {
            if (!this.ajax.xhr) {
                return this;
            }
            if (this._isFunction(this.ajax.preProcess)) {
                data = this.ajax.preProcess(data);
            }
            // save for selection retrieval
            this.ajax.data = data;

            // render items
            if (data.length) {
                this.ajax.xhr = null;
                this.$menu.removeClass('py-0');
                return this.render(data).show();
            }
            if (this.options.empty) {
                // this.$element.tooltip({
                //     html: true,
                //     trigger: 'manual',
                //     placement: 'right',
                //     customClass: 'tooltip-warning',
                //     title: this.options.empty
                // }).tooltip('show');
                const $item = $('<span/>', {
                    class: 'dropdown-item disabled',
                    text: this.options.empty
                });
                this.$menu.addClass('py-0').html($item);
                return this.show();
            }
            return this.hide();
        }

        /**
         * Handle the ajax error.
         *
         * @param {XMLHttpRequest} jqXHR the ajax request.
         * @param {String} textStatus - the text status.
         * @param {Error} errorThrown - the ajax error.
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _ajaxError(jqXHR, textStatus, errorThrown) {
            if (textStatus !== 'abort' && this._isFunction(this.onError)) {
                this.onError(jqXHR, textStatus, errorThrown);
            }
            return this;
        }

        /**
         * Gets the menu items.
         *
         * @return {jQuery} the items.
         * @private
         */
        _getItems() {
            return this.$menu.find(this.options.selector);
        }

        /**
         * Returns if one or more menu items are found.
         *
         * @return {boolean} true if menu items are found.
         * @private
         */
        _hasItems() {
            return this._getItems().length > 0;
        }

        /**
         * Gets the trimmed element text.
         *
         * @return {string} the text.
         * @private
         */
        _getQueryText() {
            return this.$element.val().trim();
        }

        /**
         * Returns the trimmed element text is not empty.
         *
         * @return {boolean} true if not empty.
         * @private
         */
        _isQueryText() {
            return this._getQueryText() !== '';
        }

        /**
         * Highlight the given item.
         *
         * @param {String} item - the item to highlight.
         *
         * @return {String}
         */
        highlighter(item) {
            const query = this.query.replace(/[\-\[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
            const pattern = `(${query})`;
            const flags = 'gi';
            const regex = new RegExp(pattern, flags);
            return item.replace(regex, function (_$1, match) {
                return `<span class="text-success">${match}</span>`;
            });
        }

        /**
         * Select the next menu item.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _next() {
            const selector = this.options.selector;
            const active = this.$menu.find('.active').removeClass('active');
            const next = active.nextAll(selector).first();
            if (next.length) {
                next.addClass('active');
            } else {
                this._first();
            }
            return this;
        }

        /**
         * Select the previous menu item.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _prev() {
            const selector = this.options.selector;
            const active = this.$menu.find('.active').removeClass('active');
            const prev = active.prevAll(selector).first();
            if (prev.length) {
                prev.addClass('active');
            } else {
                this._last();
            }
            return this;
        }

        /**
         * Select the first menu item.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _first() {
            const first = this._getItems().first();
            if (first.length) {
                this.$menu.find('.active').removeClass('active');
                first.addClass('active');
            }
            return this;
        }

        /**
         * Select the last menu item.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _last() {
            const last = this._getItems().last();
            if (last.length) {
                this.$menu.find('.active').removeClass('active');
                last.addClass('active');
            }
            return this;
        }

        /**
         * Handle the key event.
         *
         * @param {KeyboardEvent} e - the event.
         * @private
         */
        _move(e) {
            if (!this.visible) {
                return;
            }
            switch (e.key) {
                case 'Tab':
                case 'Enter':
                case 'Escape':
                    e.preventDefault();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this._prev();
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    this._next();
                    break;
                case 'Home':
                    e.preventDefault();
                    this._first();
                    break;
                case 'End':
                    e.preventDefault();
                    this._last();
                    break;
            }
            e.stopPropagation();
        }

        /**
         * Handle the key down event.
         *
         * @param {KeyboardEvent} e - the event.
         * @private
         */
        _keydown(e) {
            this.suppressKeyPressRepeat = $.inArray(e.key, ['Tab', 'Enter', 'Escape', 'ArrowUp', 'ArrowDown', 'Home', 'End']) !== -1;
            this._move(e);
        }

        /**
         * Handle the key press event.
         *
         * @param {KeyboardEvent} e - the event.
         * @private
         */
        _keypress(e) {
            if (!this.suppressKeyPressRepeat) {
                this._move(e);
            }
        }

        /**
         * Handle the key up event.
         *
         * @param {KeyboardEvent} e - the event.
         * @private
         */
        _keyup(e) {
            switch (e.key) {
                case 'ArrowDown':
                    if (!this.visible && this._hasItems() && this._isQueryText()) {
                        this.show();
                    }
                    break;
                case 'ArrowUp':
                    break;
                case 'Tab':
                case 'Enter':
                    if (this.visible) {
                        this._select();
                    }
                    break;
                case 'Escape':
                    this.hide();
                    break;
                default:
                    if (!e.shiftKey && !e.ctrlKey && !e.altKey) {
                        this._ajaxLookup();
                    }
                    break;
            }
            e.preventDefault();
        }

        /**
         * Handle the focus event.
         * @private
         */
        _focus() {
            this.focused = true;
        }

        /**
         * Handle the blur event.
         * @private
         */
        _blur() {
            this.focused = false;
            if (!this.isMouseOver && this.visible) {
                this.hide();
            }
        }

        /**
         * Handle the click event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _click(e) {
            e.preventDefault();
            this.$element.trigger('focus');
            this._select();
        }

        /**
         * Handle the mouse enter event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _mouseenter(e) {
            this.isMouseOver = true;
            this.$menu.find('.active').removeClass('active');
            $(e.currentTarget).addClass('active');
        }

        /**
         * Handle the mouse leave event.
         * @private
         */
        _mouseleave() {
            this.isMouseOver = false;
            if (!this.focused && this.visible) {
                this.hide();
            }
        }

        /**
         * Start listen events.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _listen() {
            // add element handlers
            const $element = this.$element;
            this.focusProxy = () => this._focus();
            this.blurProxy = () => this._blur();
            this.keyUpProxy = (e) => this._keyup(e);
            this.keyPressProxy = (e) => this._keypress(e);
            this.keyDownProxy = (e) => this._keydown(e);

            $element.on('focus', this.focusProxy);
            $element.on('blur', this.blurProxy);
            $element.on('keyup', this.keyUpProxy);
            $element.on('keypress', this.keyPressProxy);
            $element.on('keydown', this.keyPressProxy);

            // add menu handlers
            const $menu = this.$menu;
            const selector = this.options.selector;
            this.clickProxy = (e) => this._click(e);
            this.mouseEnterProxy = (e) => this._mouseenter(e);
            this.mouseLeaveProxy = () => this._mouseleave();

            $menu.on('click', this.clickProxy);
            $menu.on('mouseenter', selector, this.mouseEnterProxy);
            $menu.on('mouseleave', selector, this.mouseLeaveProxy);

            return this;
        }

        /**
         * Cancel last timer if set.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _clearTimeout() {
            if (this.ajax && this.ajax.timerId) {
                clearTimeout(this.ajax.timerId);
                this.ajax.timerId = null;
            }
            return this;
        }

        /**
         * Abort the ajax call if applicable.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _abortAjax() {
            if (this.ajax && this.ajax.xhr) {
                this.ajax.xhr.abort();
                this.ajax.xhr = null;
            }
            return this;
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    Typeahead.DEFAULTS = {
        autoSelect: true,
        alignWidth: true,
        valueField: 'id',
        displayField: 'name',
        separator: 'category',
        empty: null,

        // UI
        selector: '.dropdown-item',
        menu: '<div class="typeahead dropdown-menu" role="listbox" />',
        item: '<button class="dropdown-item" type="button" role="option" />',
        header: '<h6 class="dropdown-header text-uppercase" />',
        divider: '<hr class="dropdown-divider">',

        // functions
        onSelect: null,
        onError: null,

        // copy text from selection
        copyText: true,

        // ajax
        ajax: {
            url: null,
            timeout: 100,
            method: 'get',
            triggerLength: 1,
            preDispatch: null,
            preProcess: null
        }
    };

    /**
     * The plugin name.
     */
    Typeahead.NAME = 'bs.typeahead';

    // -----------------------------
    // Typeahead plugin definition
    // -----------------------------
    const oldTypeahead = $.fn.typeahead;
    $.fn.typeahead = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            let data = $this.data(Typeahead.NAME);
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data(Typeahead.NAME, data = new Typeahead(this, settings));
            }
            if (data._isString(options)) {
                data[options]();
            }
        });
    };
    $.fn.typeahead.Constructor = Typeahead;

    // ------------------------------------
    // Typeahead no conflict
    // ------------------------------------
    $.fn.typeahead.noConflict = function () {
        $.fn.typeahead = oldTypeahead;
        return this;
    };

    // --------------------------------
    // Typeahead data-api
    // --------------------------------
    $('document').on('focus.typeahead.data-api', '[data-provide="typeahead"]', function (e) {
        const $this = $(this);
        if (!$this.data(Typeahead.NAME)) {
            $this.typeahead($this.data());
            e.preventDefault();
        }
    });

}(jQuery));
