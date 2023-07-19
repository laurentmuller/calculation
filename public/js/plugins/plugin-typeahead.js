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
            // remove element handlers and data
            const that = this;
            const $element = this.$element;
            $element.off('blur', that.blurProxy)
                .off('input', this.inputProxy)
                .off('focus', that.focusProxy)
                .off('keyup', that.keyUpProxy)
                .off('keydown', that.keyDownProxy)
                .off('keypress', that.keyPressProxy)
                .removeData(Typeahead.NAME);

            // remove menu handlers
            const $menu = this.$menu;
            const selector = this.options.selector;
            $menu.off('click', that.clickProxy)
                .off('mouseenter', selector, that.mouseEnterProxy)
                .off('mouseleave', selector, that.mouseLeaveProxy)
                .remove();
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
                this.$menu.hide();
                this.visible = false;
            }
            return this;
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize.
         *
         * @return {Typeahead} this instance for chaining.
         * @throws {Error} if the Ajax URL is not defined.
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
         * Start listen events.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _listen() {
            // add element handlers
            const $element = this.$element;
            this.blurProxy = () => this._blur();
            this.inputProxy = () => this._input();
            this.focusProxy = () => this._focus();
            this.keyUpProxy = (e) => this._keyup(e);
            this.keyDownProxy = (e) => this._keydown(e);
            this.keyPressProxy = (e) => this._keypress(e);
            $element.on('blur', this.blurProxy)
                .on('input', this.inputProxy)
                .on('focus', this.focusProxy)
                .on('keyup', this.keyUpProxy)
                .on('keydown', this.keyPressProxy)
                .on('keypress', this.keyPressProxy);

            // add menu handlers
            const $menu = this.$menu;
            const selector = this.options.selector;
            this.clickProxy = (e) => this._click(e);
            this.mouseEnterProxy = (e) => this._mouseenter(e);
            this.mouseLeaveProxy = () => this._mouseleave();
            $menu.on('click', this.clickProxy)
                .on('mouseenter', selector, this.mouseEnterProxy)
                .on('mouseleave', selector, this.mouseLeaveProxy);

            return this;
        }

        /**
         * Render the items.
         *
         * @param {Array.<Object>} items - the items to render.
         * @return {Typeahead} this instance for chaining.
         */
        _render(items) {
            /** @type {Array.<Object>} */
            const data = [];
            const that = this;

            let newSeparator;
            let oldSeparator = null;
            const separator = that.options.separator;
            const isStringSeparator = that._isString(separator);
            const isStringDisplay = that._isString(that.displayField);

            // run over items to add separators and categories
            items.forEach(function (value, index) {
                // get separator
                newSeparator = isStringSeparator ? value[separator] : separator(value);
                if (index > 0) {
                    oldSeparator = isStringSeparator ? items[index - 1][separator] : separator(items[index - 1]);
                }
                // inject separator
                if (index > 0 && newSeparator !== oldSeparator) {
                    data.push({
                        __type__: 'divider'
                    });
                }
                // inject category
                if (newSeparator && (index === 0 || newSeparator !== oldSeparator)) {
                    data.push({
                        __type__: 'category',
                        name: newSeparator
                    });
                }
                // inject value
                data.push(value);
            });

            // render categories, separators and items
            const regex = that._getHighlighter();
            /** @type {Array.<jQuery>} */
            const $items = data.map(function (item) {
                if (item.__type__ === 'category') {
                    return that._renderCategory(regex, item);
                } else if (item.__type__ === 'divider') {
                    return that._renderSeparator();
                } else {
                    return that._renderItem(regex, item, isStringDisplay);
                }
            });
            that.$menu.empty().append($items);
            if (that.options.autoSelect) {
                return that._first();
            }
            return that;
        }

        /**
         * Render a category.
         *
         * @param {RegExp} regex
         * @param {Object} item
         * @return {jQuery}
         * @private
         */
        _renderCategory(regex, item) {
            const html = this._highlight(regex, item.name);
            return $(this.options.header).html(html);
        }

        /**
         * Render a separator.
         *
         * @return {jQuery}
         * @private
         */
        _renderSeparator() {
            return $(this.options.divider);
        }

        /**
         * Render an item.
         *
         * @param {RegExp} regex
         * @param {Object} item
         * @param {boolean} isStringDisplay
         * @return {jQuery}
         * @private
         */
        _renderItem(regex, item, isStringDisplay) {
            let text;
            if (this._isObject(item)) {
                text = isStringDisplay ? item[this.displayField] : this.displayField(item);
            } else {
                text = item;
            }
            const value = JSON.stringify(item);
            const html = this._highlight(regex, text);
            return $(this.options.item).data('value', value).html(html);
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
            const $selectedItem = this._getSelectedItem();
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
                    this._first().show();
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
            // render items
            if (data.length) {
                this.ajax.xhr = null;
                this.$menu.removeClass('py-0');
                return this._render(data).show();
            }
            if (this.options.empty) {
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
         * Gets the selected item.
         *
         * @return {jQuery} the selected item.
         * @private
         */
        _getSelectedItem() {
            return this.$menu.find('.active');
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
         * Highlight the given text.
         *
         * @param {RegExp} regex - the regular expression.
         * @param {string} text - the text to highlight
         * @return {string} the highlighted text.
         * @private
         */
        _highlight(regex, text) {
            return text.replace(regex, this.options.highlight);
        }

        /**
         * Gets the regular expression used to highlight text.
         *
         * @return {RegExp}
         * @private
         */
        _getHighlighter() {
            const query = this.query.replace(/[\-\[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
            return new RegExp(query, 'gi');
        }


        /**
         * Select the next menu item.
         *
         * @return {Typeahead} this instance for chaining.
         * @private
         */
        _next() {
            const $active = this._getSelectedItem().removeClass('active');
            const $next = $active.nextAll(this.options.selector).first();
            if ($next.length) {
                $next.addClass('active');
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
            const $active = this._getSelectedItem().removeClass('active');
            const $prev = $active.prevAll(this.options.selector).first();
            if ($prev.length) {
                $prev.addClass('active');
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
            const $first = this._getItems().first();
            if ($first.length) {
                this._getSelectedItem().removeClass('active');
                $first.addClass('active');
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
            const $last = this._getItems().last();
            if ($last.length) {
                this._getSelectedItem().removeClass('active');
                $last.addClass('active');
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
                    if (e.altKey && !this.visible && this._hasItems() && this._isQueryText()) {
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
         * Handle the input change event.
         * @private
         */
        _input() {
            if (!this._isQueryText()) {
                this._clearTimeout()._abortAjax().hide();
            }
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
         * Handle the item click event.
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
         * Handle the item mouse enter event.
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
         * Handle the item mouse leave event.
         * @private
         */
        _mouseleave() {
            this.isMouseOver = false;
            if (!this.focused && this.visible) {
                this.hide();
            }
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
        highlight: '<span class="text-success fw-bold">$&</span>',

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
