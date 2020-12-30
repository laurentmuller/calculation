/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // Typeahead public class definition
    // ------------------------------------
    var Typeahead = function (element, options) {
        const that = this;
        that.$element = $(element);
        that.options = $.extend(true, {}, Typeahead.DEFAULTS, options);
        that.$menu = $(that.options.menu).insertAfter(that.$element);

        // Method overrides
        that.eventSupported = that.options.eventSupported || that.eventSupported;
        that.grepper = that.options.grepper || that.grepper;
        that.highlighter = that.options.highlighter || that.highlighter;
        that.lookup = that.options.lookup || that.lookup;
        that.matcher = that.options.matcher || that.matcher;
        that.render = that.options.render || that.render;
        that.onSelect = that.options.onSelect || null;
        that.onError = that.options.onError || null;
        that.sorter = that.options.sorter || that.sorter;
        that.select = that.options.select || that.select;
        that.source = that.options.source || that.source;
        that.displayField = that.options.displayField;
        that.valueField = that.options.valueField;

        if (that.options.ajax) {
            const ajax = that.options.ajax;
            if (that.isString(ajax)) {
                that.ajax = $.extend({}, Typeahead.DEFAULTS.ajax, {
                    url: ajax
                });
            } else {
                if (that.isString(ajax.displayField)) {
                    that.displayField = that.options.displayField = ajax.displayField;
                }
                if (that.isString(ajax.valueField)) {
                    that.valueField = that.options.valueField = ajax.valueField;
                }
                that.ajax = $.extend({}, Typeahead.DEFAULTS.ajax, ajax);
            }
            if (!that.ajax.url) {
                that.ajax = null;
            }
            that.query = '';
        } else {
            that.source = that.options.source;
            that.ajax = null;
        }
        that.visible = false;
        that.listen();
    };

    Typeahead.DEFAULTS = {
        source: [],
        items: 15,
        autoSelect: true,
        alignWidth: true,
        valueField: 'id',
        displayField: 'name',
        separator: 'category',

        // UI
        selector: '.dropdown-item',
        menu: '<div class="typeahead dropdown-menu" role="listbox"></div>',
        item: '<button class="dropdown-item" type="button" role="option" />',
        header: '<h6 class="dropdown-header text-uppercase"></h6>',
        divider: '<div class="dropdown-divider"></div>',

        // functions
        onSelect: null,
        onError: null,

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

    Typeahead.prototype = {
        constructor: Typeahead,
        eventSupported: function (eventName) {
            let isSupported = eventName in this.$element;
            if (!isSupported) {
                this.$element.setAttribute(eventName, 'return;');
                isSupported = $.type(this.$element[eventName]) === 'function';
            }
            return isSupported;
        },
        isString: function (data) {
            return $.type(data) === 'string';
        },
        isObject: function (data) {
            return $.type(data) === 'object';
        },
        select: function () {
            const $selectedItem = this.$menu.find('.active');
            if ($selectedItem.length) {
                var item = JSON.parse($selectedItem.data('value'));
                var text = $selectedItem.text();
                if (this.valueField) {
                    text = item[this.valueField];
                }
                this.$element.val(text).change();
                if ($.isFunction(this.onSelect)) {
                    this.onSelect(item);
                }
            }
            return this.hide();
        },
        updater: function (item) {
            return item;
        },
        show: function () {
            const pos = $.extend({}, this.$element.position(), {
                height: this.$element[0].offsetHeight
            });
            this.$menu.css({
                top: pos.top + pos.height,
                left: pos.left
            });
            if (this.options.alignWidth) {
                const width = $(this.$element[0]).outerWidth();
                this.$menu.css({
                    width: width
                });
            }
            this.$menu.show();
            this.visible = true;
            return this;
        },
        hide: function () {
            if (this.visible) {
                this.$menu.hide();
                this.visible = false;
            }
            return this;
        },
        ajaxLookup: function () {
            const query = this.$element.val().trim();
            if (query === this.query) {
                return this;
            }

            // query changed
            this.query = query;

            // cancel last timer if set
            if (this.ajax.timerId) {
                clearTimeout(this.ajax.timerId);
                this.ajax.timerId = null;
            }
            if (!query || query.length < this.ajax.triggerLength) {
                // cancel the ajax callback if in progress
                if (this.ajax.xhr) {
                    this.ajax.xhr.abort();
                    this.ajax.xhr = null;
                }
                return this.hide();
            }

            // query is good to send, set a timer
            this.ajax.timerId = setTimeout($.proxy(this.ajaxExecute, this), this.ajax.timeout);
            return this;
        },
        ajaxExecute: function () {
            // cancel last call if already in progress
            if (this.ajax.xhr) {
                this.ajax.xhr.abort();
            }
            const query = this.query;
            const data = $.isFunction(this.ajax.preDispatch) ? this.ajax.preDispatch(query) : {
                query: query
            };
            this.ajax.xhr = $.getJSON({
                data: data,
                url: this.ajax.url,
                success: $.proxy(this.ajaxSuccess, this),
                error: $.proxy(this.ajaxError, this),
            });
            this.ajax.timerId = null;
            return this;
        },
        ajaxSuccess: function (data) {
            if (!this.ajax.xhr) {
                return this;
            }
            if ($.isFunction(this.ajax.preProcess)) {
                data = this.ajax.preProcess(data);
            }
            // save for selection retreival
            this.ajax.data = data;

            // manipulate objects
            const items = this.grepper(this.ajax.data) || [];
            if (!items.length) {
                return this.hide();
            }
            this.ajax.xhr = null;
            return this.render(items.slice(0, this.options.items)).show();
        },
        ajaxError: function (jqXHR, textStatus, errorThrown) {
            if (textStatus !== 'abort' && $.isFunction(this.onError)) {
                this.onError(jqXHR, textStatus, errorThrown);
            }
            return this;
        },
        lookup: function () {
            this.query = this.$element.val();
            if (!this.query) {
                return this.hide();
            }
            const items = this.grepper(this.source);
            if (!items || items.length === 0) {
                return this.hide();
            }
            return this.render(items.slice(0, this.options.items)).show();
        },
        matcher: function (item) {
            return item.toLowerCase().indexOf(this.query.toLowerCase()) !== -1;
        },
        sorter: function (items) {
            if (!this.options.ajax) {
                const beginswith = [];
                const caseSensitive = [];
                const caseInsensitive = [];
                let item = items.shift();

                while (item !== null) {
                    if (!item.toLowerCase().indexOf(this.query.toLowerCase())) {
                        beginswith.push(item);
                    } else if (item.indexOf(this.query) !== -1) {
                        caseSensitive.push(item);
                    } else {
                        caseInsensitive.push(item);
                    }
                    item = items.shift();
                }
                return beginswith.concat(caseSensitive, caseInsensitive);
            } else {
                return items;
            }
        },
        highlighter: function (item) {
            const query = this.query.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&');
            const pattern = '(' + query + ')';
            const flags = 'gi';
            const regex = new RegExp(pattern, flags);
            return item.replace(regex, function ($1, match) {
                return '<strong>' + match + '</strong>';
            });
        },
        render: function (items) {
            const data = [];
            const that = this;

            let display;
            let separatorKey;
            let oldSeparator = null;
            const separator = that.options.separator;
            const isStrSeparator = that.isString(separator);
            const isStrDisplayField = that.isString(that.displayField);

            // run over items and add separators and categories if applicable
            $.each(items, function (key, value) {
                // get separators
                separatorKey = isStrSeparator ? value[separator] : separator(value);
                if (key > 0) {
                    oldSeparator = isStrSeparator ? items[key - 1][separator] : separator(items[key - 1]);
                }

                // inject separator
                if (key > 0 && separatorKey !== oldSeparator) {
                    data.push({
                        __type__: 'divider'
                    });
                }

                // inject category
                if (separatorKey && (key === 0 || separatorKey !== oldSeparator)) {
                    data.push({
                        __type__: 'category',
                        name: separatorKey
                    });
                }

                // inject value
                data.push(value);
            });

            // render categories, separators and items
            items = $(data).map(function (i, item) {
                // category
                if ((item.__type__ || false) === 'category') {
                    return $(that.options.header).text(item.name)[0];
                }
                // separator
                if ((item.__type__ || false) === 'divider') {
                    return $(that.options.divider)[0];
                }
                // item
                if (that.isObject(item)) {
                    display = isStrDisplayField ? item[that.displayField] : that.displayField(item);
                } else {
                    display = item;
                }
                const value = JSON.stringify(item);
                const html = that.highlighter(display);
                return $(that.options.item).data('value', value).html(html)[0];
            });

            if (that.options.autoSelect) {
                const selector = that.options.selector;
                items.filter(selector).first().addClass('active');
            }
            this.$menu.html(items);
            return this;
        },
        grepper: function (data) {
            // filters relevent results
            let items;
            let display;
            const that = this;
            const isStrDisplayField = that.isString(that.displayField);

            if (isStrDisplayField && data && data.length) {
                if (data[0].hasOwnProperty(that.displayField)) {
                    items = $.grep(data, function (item) {
                        display = isStrDisplayField ? item[that.displayField] : that.displayField(item);
                        return that.matcher(display);
                    });
                } else if (that.isString(data[0])) {
                    items = $.grep(data, function (item) {
                        return that.matcher(item);
                    });
                } else {
                    return null;
                }
            } else {
                return null;
            }
            return this.sorter(items);
        },
        next: function () {
            const that = this;
            const selector = that.options.selector;
            const active = that.$menu.find('.active').removeClass('active');
            let next = active.nextAll(selector).first();
            if (!next.length) {
                next = that.$menu.find(selector).first();
            }
            if (next.length) {
                next.addClass('active');
            }
        },
        prev: function () {
            const that = this;
            const selector = that.options.selector;
            const active = that.$menu.find('.active').removeClass('active');
            let prev = active.prevAll(selector).first();
            if (!prev.length) {
                prev = that.$menu.find(selector).last();
            }
            if (prev.length) {
                prev.addClass('active');
            }
        },
        move: function (e) {
            const that = this;
            if (!that.visible) {
                return;
            }
            switch (e.keyCode) {
            case 9: // tab
            case 13: // enter
            case 27: // escape
                e.preventDefault();
                break;
            case 38: // up arrow
                e.preventDefault();
                that.prev();
                break;
            case 40: // down arrow
                e.preventDefault();
                that.next();
                break;
            }
            e.stopPropagation();
        },
        keydown: function (e) {
            const that = this;
            that.suppressKeyPressRepeat = $.inArray(e.keyCode, [40, 38, 9, 13, 27]) !== -1;
            that.move(e);
        },
        keypress: function (e) {
            if (this.suppressKeyPressRepeat) {
                return;
            }
            this.move(e);
        },
        keyup: function (e) {
            switch (e.keyCode) {
            case 40: // down arrow
                if (e.ctrlKey && !this.visible && this.query !== '') {
                    this.show();
                }
                break;
            case 38: // up arrow
            case 16: // shift
            case 17: // ctrl
            case 18: // alt
                break;
            case 9: // tab
            case 13: // enter
                if (this.visible) {
                    this.select();
                }
                break;
            case 27: // escape
                if (this.visible) {
                    this.hide();
                }
                break;
            default:
                if (this.ajax) {
                    this.ajaxLookup();
                } else {
                    this.lookup();
                }
                break;
            }
            e.stopPropagation();
            e.preventDefault();
        },
        focus: function () {
            this.focused = true;
        },
        blur: function () {
            this.focused = false;
            if (!this.isMouseOver && this.visible) {
                this.hide();
            }
        },
        click: function (e) {
            e.stopPropagation();
            e.preventDefault();
            this.$element.focus();
            this.select();
        },
        mouseenter: function (e) {
            this.isMouseOver = true;
            this.$menu.find('.active').removeClass('active');
            $(e.currentTarget).addClass('active');
        },
        mouseleave: function () {
            this.isMouseOver = false;
            if (!this.focused && this.visible) {
                this.hide();
            }
        },
        listen: function () {
            // add element handlers
            const $element = this.$element;
            $element.on('focus', $.proxy(this.focus, this));
            $element.on('blur', $.proxy(this.blur, this));
            $element.on('keypress', $.proxy(this.keypress, this));
            $element.on('keyup', $.proxy(this.keyup, this));
            if (this.eventSupported('keydown')) {
                $element.on('keydown', $.proxy(this.keydown, this));
            }

            // add menu handlers
            const $menu = this.$menu;
            const selector = this.options.selector;
            $menu.on('click', $.proxy(this.click, this));
            $menu.on('mouseenter', selector, $.proxy(this.mouseenter, this));
            $menu.on('mouseleave', selector, $.proxy(this.mouseleave, this));
        },
        destroy: function () {
            // remove element handlers
            const $element = this.$element;
            $element.off('focus', $.proxy(this.focus, this));
            $element.off('blur', $.proxy(this.blur, this));
            $element.off('keypress', $.proxy(this.keypress, this));
            $element.off('keyup', $.proxy(this.keyup, this));
            if (this.eventSupported('keydown')) {
                $element.off('keydown', $.proxy(this.keydown, this));
            }

            // remove menu handlers
            const $menu = this.$menu;
            const selector = this.options.selector;
            $menu.off('click', $.proxy(this.click, this));
            $menu.off('mouseenter', selector, $.proxy(this.mouseenter, this));
            $menu.off('mouseleave', selector, $.proxy(this.mouseleave, this));

            // remove data
            $element.removeData('typeahead');
        }
    };

    // -----------------------------
    // Typeahead plugin definition
    // -----------------------------
    const oldTypeahead = $.fn.typeahead;

    $.fn.typeahead = function (option) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('typeahead');
            if (!data) {
                const options = typeof option === 'object' && option;
                $this.data('typeahead', data = new Typeahead(this, options));
            }
            if (data.isString(option)) {
                data[option]();
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
    (function ($) {
        $('body').on('focus.typeahead.data-api', '[data-provide="typeahead"]', function (e) {
            const $this = $(this);
            if (!$this.data('typeahead')) {
                $this.typeahead($this.data());
                e.preventDefault();
            }
        });
    }(jQuery));

}(jQuery));
