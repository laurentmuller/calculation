/**! compression tag for ftp-deployment */

/**
 * Override constructor
 */
const BootstrapTable = $.fn.bootstrapTable.Constructor;
const _init = BootstrapTable.prototype.init;

// add methods
$.fn.bootstrapTable.methods.push('isCustomView', 'isCardView', 'isTableView');
$.fn.bootstrapTable.methods.push('getCustomView', 'setDisplayMode', 'getDisplayMode');
$.fn.bootstrapTable.methods.push('isEmpty', 'getSelection', 'getSelectionIndex');
$.fn.bootstrapTable.methods.push('highlight', 'selectFirstRow');
$.fn.bootstrapTable.methods.push('enableKeys', 'disableKeys');
$.fn.bootstrapTable.methods.push('selectPageItem');

/**
 * Initialize.
 */
BootstrapTable.prototype.init = function () {
    'use strict';
    _init.apply(this, Array.prototype.slice.apply(arguments));

    const that = this;

    // handle page item click
    that.$pagination.on('keydown mousedown', '.page-link', function(e) {
        const $this = $(this);
        const isKeyEnter = e.type === 'keydown' && e.which === 13;
        const isActive = $this.parents('.page-item').hasClass('active');
        const isMouseDown = e.type === 'mousedown' &&  e.button === 0 && !isActive;
        if (isKeyEnter || isMouseDown) {
            const $parent = $this.parents('li');
            if ($parent.hasClass('page-pre')) {
                that.focusPageItem = 'previous';
            } else if ($parent .hasClass('page-next')) {
                that.focusPageItem = 'next';
            } else {
                that.focusPageItem = 'active';
            }
        }
    });

    that.onClickRow = function (_row, $element) {
        that.updateRow($element);
        that.enableKeys();
    };

    that.onDblClickRow = function (_row, $element, field) {
        that.updateRow($element);
        if (field !== 'action') {
            that.editRow($element);
        }
    };

    that.onPageChange = function () {
        // hide
        if (that.isCustomView()) {
            that.$el.closest('.bootstrap-table').find('.fixed-table-custom-view .custom-item').animate({
                'opacity': '0'
            }, 200);
            that.hideCustomViewMessage();
        }
    };

    that.onPostBody = function (content) {
        const isData = content.length !== 0;
        if (isData) {
            // select first row if none
            if (!that.getSelection()) {
                that.selectFirstRow();
            }
            // update
            that.updateCardView().highlight().updateHref(content);
        }
        that.updateHistory().toggleClass('table-hover', isData);

        // update pagination links
        that.$pagination.find('.page-link').each(function (_index, element) {
            const $element = $(element);
            const href = $element.closest('.page-item').hasClass('disabled') ? null : '#';
            $element.attr('href', href).attr('title', $element.attr('aria-label')).removeAttr('aria-label');
        });

        // set focus on selected page item
        if (that.focusPageItem) {
            that.selectPageItem();
        }
    };

    that.onCustomViewPostBody = function (data) {
        const $table = that.$el;
        const options = that.options;
        const $view = that.getCustomView();

        // data?
        if (data.length !== 0) {
            // hide empty data message
            that.hideCustomViewMessage();

            const params = that.getParameters();
            const selector = '.custom-view-actions:eq(%index%)';
            const callback = typeof options.onRenderCustomView === 'function' ? options.onRenderCustomView: false;

            that.$body.find('tr .actions').each(function (index, element) {
                // copy actions
                const $rowActions = $(element).children();
                const $cardActions = $view.find(selector.replace('%index%', index));
                $rowActions.appendTo($cardActions);

                if (callback) {
                    const row = data[index];
                    const $item = $cardActions.parents('.custom-item');
                    callback($table, row, $item, params);
                }
            });

            // display selection
            const $selection = $view.find(options.customSelector);
            if ($selection.length) {
                $selection.scrollInViewport();
            }
            that.highlight();
        } else {
            // show empty data message
            that.showCustomViewMessage();
        }
        that.saveParameters();
    };

    // save parameters
    that.onToggle = function () {
        that.saveParameters();
    };

    // select row on right click
    that.$body.on('mousedown', 'tr', function (e) {
        if (e.button === 2) {
            that.updateRow($(this));
        }
    });

    // handle items in custom view
    that.$el.closest('.bootstrap-table').on('mousedown', '.custom-item', function () {
        const index = $(this).parent().index();
        const $row = that.$body.find('tr:eq(' + index + ')');
        if ($row.length) {
            that.updateRow($row);
        }
    }).on('dblclick', '.custom-item.table-primary div:not(.rowlink-skip)', function (e) {
        if (e.button === 0) {
            that.editRow();
        }
    });

    that.enableKeys().highlight();
};

/**
 * Return if the custom view mode is displayed.
 *
 * @return {boolean} true if the custom view mode is displayed.
 */
BootstrapTable.prototype.isCustomView = function () {
    'use strict';
    return this.showCustomView;
};
/**
 * Return if the card view mode is displayed.
 *
 * @return {boolean} true if the card view mode is displayed.
 */
BootstrapTable.prototype.isCardView = function () {
    'use strict';
    return this.options.cardView;
};

/**
 * Return if the table view mode is displayed.
 *
 * @return {boolean} true if the table view mode is displayed.
 */
BootstrapTable.prototype.isTableView = function () {
    'use strict';
    return !this.isCardView() && !this.isCustomView();
};
/**
 * Returns if no loaded data (rows) is displayed.
 *
 * @return {boolean} true if not data is displayed.
 */
BootstrapTable.prototype.isEmpty = function () {
    'use strict';
    return this.options.data.length === 0;
};
/**
 * Gets the selected row.
 *
 * @return {JQuery} the selected row, if any; null otherwise.
 */
BootstrapTable.prototype.getSelection = function () {
    'use strict';
    const selector = 'tr.' + this.options.rowClass;
    const $row = this.$body.find(selector);
    return $row.length ? $row : null;
};
/**
 * Gets the selected row index.
 *
 * @return {int} the selected row index, if any; -1 otherwise.
 */
BootstrapTable.prototype.getSelectionIndex = function () {
    'use strict';
    const $row = this.getSelection();
    return $row ? $row.index() : -1;
};

/**
 * Gets the custom view container.
 *
 * @return {JQuery} the custom view container, if displayed, null otherwise.
 */
BootstrapTable.prototype.getCustomView = function () {
    'use strict';
    if (this.isCustomView()) {
        const $view = this.$container.find('.fixed-table-custom-view');
        return $view.length ? $view : null;
    }
    return null;
};

/**
 * Sets the display mode.
 *
 * @param {string}
 *            mode - the display mode to set ('table', 'card' or 'custom').
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.setDisplayMode = function (mode) {
    'use strict';
    switch (mode) {
    case 'custom':
        if (!this.isCustomView()) {
            this.toggleCustomView();
        }
        break;
    case 'card':
        if (!this.isCardView()) {
            this.toggleView();
        }
        if (this.isCustomView()) {
            this.toggleCustomView();
        }
        break;
    default: // table
        if (this.isCardView()) {
            this.toggleView();
        }
        if (this.isCustomView()) {
            this.toggleCustomView();
        }
        break;
    }
    return this;
};

/**
 * Gets the display mode.
 *
 * @return {string} the display mode ('table', 'card' or 'custom').
 */
BootstrapTable.prototype.getDisplayMode = function () {
    'use strict';
    if (this.isCustomView()) {
        return 'custom';
    } else if (this.isCardView()) {
        return 'card';
    } else {
        return 'table';
    }
};

/**
 * Highlight matching text.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.highlight = function () {
    'use strict';
    const text = '' + this.options.searchText;
    if (text.length > 0) {
        const options = {
            element: 'span',
            className: 'text-success',
            separateWordSearch: false,
            ignorePunctuation: ["'", ","]
        };
        if (this.isCustomView()) {
            this.getCustomView().find('.custom-item').mark(text, options);
        } else {
            this.$body.find('td:not(.rowlink-skip)').mark(text, options);
        }
    }
    return this;
};

/**
 * Update the selected row.
 *
 * @param {JQuery}
 *            $row - the row to update.
 * @return {boolean} this function returns always true.
 */
BootstrapTable.prototype.updateRow = function ($row) {
    'use strict';
    const options = this.options;

    // already selected or no data?
    if ($row.hasClass(options.rowClass) || $row.hasClass('no-records-found')) {
        return true;
    }

    // remove old selection
    this.$body.find(options.rowSelector).removeClass(options.rowClass);

    // add selection
    $row.addClass(options.rowClass);

    // custom view?
    if (this.isCustomView()) {
        const $view = this.getCustomView();
        $view.find('.custom-item').removeClass(options.rowClass);
        const $selection = $view.find('.custom-item:eq(' + $row.index() + ')');
        if ($selection.length) {
            $selection.addClass(options.rowClass).scrollInViewport();
        }
    } else {
        $row.scrollInViewport();
    }

    // notify
    this.trigger('update-row', $row);

    return true;
};

/**
 * Call the edit action for the selected row (if any).
 *
 * @return {boolean} true if the action is called.
 */
BootstrapTable.prototype.editRow = function () {
    'use strict';
    const $row = this.getSelection();
    if ($row) {
        const $link = this.findAction('a.btn-default');
        if ($link) {
            $link[0].click();
            return true;
        }
    }
    return false;
};

/**
 * Call the delete action for the selected row (if any).
 *
 * @return {boolean} true if the action is called.
 */
BootstrapTable.prototype.deleteRow = function () {
    'use strict';
    const $row = this.getSelection();
    if ($row) {
        const $link = this.findAction('a.btn-delete');
        if ($link) {
            $link[0].click();
            return true;
        }
    }
    return false;
};

/**
 * Select the first row.
 *
 * @return {boolean} true if the first row is selected.
 */
BootstrapTable.prototype.selectFirstRow = function () {
    'use strict';
    const $row = this.getSelection();
    const $first = this.$body.find('tr:first');
    if ($first.length && $first !== $row) {
        return this.updateRow($first);
    }
    return false;
};

/**
 * Select the last row.
 *
 * @return {boolean} true if the last row is selected.
 */
BootstrapTable.prototype.selectLastRow = function () {
    'use strict';
    const $row = this.getSelection();
    const $last = this.$body.find('tr:last');
    if ($last.length && $last !== $row) {
        return this.updateRow($last);
    }
    return false;
};

/**
 * Select the previous row.
 *
 * @return {boolean} true if the previous row is selected.
 */
BootstrapTable.prototype.selectPreviousRow = function () {
    'use strict';
    const $row = this.getSelection();
    const $prev = $row.prev('tr');
    if ($row.length && $prev.length) {
        return this.updateRow($prev);
    }

    // previous page
    if (this.options.pageNumber > 1) {
        const that = this;
        that.$el.one('post-body.bs.table', function () {
            that.selectLastRow();
        });
        that.prevPage();
        return true;
    }
    return false;
};

/**
 * Select the next row.
 *
 * @return {boolean} true if the next row is selected.
 */
BootstrapTable.prototype.selectNextRow = function () {
    'use strict';
    const $row = this.getSelection();
    const $next = $row.next('tr');
    if ($row.length && $next.length) {
        return this.updateRow($next);
    }

    // next page
    if (this.options.pageNumber < this.options.totalPages) {
        this.nextPage();
        return true;
    }
    return false;
};

/**
 * Gets the parameters.
 *
 * @return {Object} the parameters.
 */
BootstrapTable.prototype.getParameters = function () {
    'use strict';
    const options = this.options;
    let params = {
        'caller': options.caller,
        'sort': options.sortName,
        'order': options.sortOrder,
        'offset': (options.pageNumber - 1) * options.pageSize,
        'limit': options.pageSize,
        'view': this.getDisplayMode()
    };

    // add search
    if (('' + options.searchText).length) {
        params.search = options.searchText;
    }

    // query parameters function?
    if (typeof options.queryParams === 'function') {
        return $.extend(params, options.queryParams(params));
    }
    return params;
};

/**
 * Update the history state.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.updateHistory = function () {
    'use strict';
    const params = this.getParameters();
    delete params.caller;

    let url = '';
    for (const [key, value] of Object.entries(params)) {
        url += url.match('[?]') ? '&' : '?';
        url += key + '=' + encodeURIComponent(value);
    }
    window.history.pushState({}, '', url);
    return this;
};

/**
 * Hide the empty data message in custom view.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.hideCustomViewMessage = function () {
    'use strict';
    const $view = this.getCustomView();
    if ($view) {
        $view.find('.no-records-found').remove();
    }
    return this;
};

/**
 * Show the empty data message in custom view.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.showCustomViewMessage = function () {
    'use strict';
    const $view = this.getCustomView();
    if ($view && $view.find('.no-records-found').length === 0) {
        $('<p/>', {
            class:'no-records-found text-center border-top p-2 mb-1',
            text: this.options.formatNoMatches()
        }).appendTo($view);
    }
    return this;
};

/**
 * Finds an action for the given selector
 *
 * @param {string}
 *            actionSelector - the action selector.
 * @return {JQuery} the action, if found; null otherwise.
 */
BootstrapTable.prototype.findAction = function (actionSelector) {
    'use strict';
    let $link;
    let $parent;
    let selector;
    if (this.isCustomView()) {
        $parent = this.getCustomView();
        selector = this.options.customSelector;
    } else {
        $parent = this.$body;
        selector = this.options.rowSelector;
    }
    $link = $parent.find(selector + ' ' + actionSelector);
    return $link.length ? $link : null;
};

/**
 * Enable the key handler.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.enableKeys = function () {
    'use strict';
    // get or create the key handler
    let keyHandler = this.keysHandler;

    if (!keyHandler) {
        keyHandler = function (e) {
            if ((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                return;
            }

            switch(e.keyCode) {
                case 13:  // enter (edit action on selected row)
                    if (this.editRow()) {
                        e.preventDefault();
                    }
                    break;
                case 33: // page up (previous page)
                    if (this.options.pageNumber > 1) {
                        this.prevPage();
                        e.preventDefault();
                    }
                    break;
                case 34: // page down (next page)
                    if (this.options.pageNumber < this.options.totalPages) {
                        this.nextPage();
                        e.preventDefault();
                    }
                    break;
                case 35: // end (last row of the current page)
                    if (this.selectLastRow()) {
                        e.preventDefault();
                    }
                    break;
                case 36: // home (first row of the current page)
                    if (this.selectFirstRow()) {
                        e.preventDefault();
                    }
                    break;
                case 37: // left arrow (previous row of the current page)
                case 38: // up arrow
                    if (this.selectPreviousRow()) {
                        e.preventDefault();
                    }
                    break;
                case 39: // right arrow (next row of the current page)
                case 40: // down arrow
                    if (this.selectNextRow()) {
                        e.preventDefault();
                    }
                    break;
                case 46: // delete (delete action of the selected row)
                    if (this.deleteRow()) {
                        e.preventDefault();
                    }
                    break;
            }
        };
        this.keyHandler= keyHandler;
    }

    // add handlers
    $(document).off('keydown.bs.table', keyHandler).on('keydown.bs.table', keyHandler);

    return this;
};

/**
 * Disable the key handler.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.disableKeys = function () {
    'use strict';
    const keyHandler = this.keyHandler;
    if (keyHandler) {
        $(document).off('keydown.bs.table', keyHandler);
    }
    return this;
};

/**
 * Save parameters to the session.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.saveParameters = function () {
    'use strict';
    const url = this.options.saveUrl;
    if (url) {
        $.post(url, this.getParameters());
    }
    return this;
};

/**
 * Update the href attribute of the actions.
 *
 * @param {array}
 *            rows - the rendered data.
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.updateHref = function (rows) {
    'use strict';
    const that = this;
    const $table = this.$el;
    const options = this.options;
    const params = this.getParameters();
    const callback = typeof options.onRenderAction === 'function' ? options.onRenderAction : false;

    this.$body.find('tr .dropdown-item-path').each(function () {
        const $link = $(this);
        const $row = $link.parents('tr');
        const row = rows[$row.index()];
        that.updateLink($link, row, params);
        if (callback) {
            callback($table, row, $row, $link);
        }

    });

    // set default action if only one by row
    this.$body.find('tr').each(function () {
        const $actions = $(this).find('.dropdown-item-path');
        if ($actions.length === 1) {
            $actions.addClass('btn-default');
        }
    });

    return this;
};

/**
 * Update the href attribute of the given link (action).
 *
 * @param {JQuery}
 *            $link - the link to update.
 * @param {Object}
 *            row - the row data.
 * @param {Object}
 *            params - the query parameters.
 */
BootstrapTable.prototype.updateLink = function ($link, row, params) {
    'use strict';
    const regex = /\bid=\d+/;
    const values = $link.attr('href').split('?');

    values[0] = values[0].replace(/\/\d+/, '/' + row.action);
    if (values.length > 1 && values[1].match(regex)) {
        params.id = row.action;
    } else {
        delete params.id;
    }
    const href = values[0] + '?' + $.param(params);
    $link.attr('href', href);
};

/**
 * Gets the visible columns of the card view.
 *
 * @return {array} the visible columns.
 */
BootstrapTable.prototype.getCardViewColumns = function () {
    'use strict';
    return this.getVisibleColumns().filter((c) => c.cardVisible);
};

/**
 * Update this card view UI.
 *
 * @return {BootstrapTable} this instance for chaining.
 */
BootstrapTable.prototype.updateCardView = function () {
    'use strict';
    if (!this.isCardView()) {
        return this;
    }

    const that = this;
    const $table = this.$el;
    const $body = this.$bdoy;
    const options = this.options;
    const columns = this.getCardViewColumns();
    const callback = typeof options.onRenderCardView === 'function' ? options.onRenderCardView : false;
    const data = callback ? options.data : null;

    $body.find('tr').each(function () {
        const $row = $(this);
        const $views = $row.find('.card-views:first');

        // move actions (if any) to a new column
        const $actions = $views.find('.card-view-value:last:has(button)');
        if ($actions.length) {
            const $td = $('<td/>', {
                class: 'actions d-print-none rowlink-skip'
            });
            $actions.removeAttr('class').appendTo($td);
            $td.appendTo($row).on('click', function () {
                that.updateRow($row);
            });
            $views.find('.card-view:last').remove();
        }

        // update class
        $views.find('.card-view-value').each(function (index, element) {
            if (columns[index].cardClass) {
                $(element).addClass(columns[index].cardClass);
            }
        });

        // callback
        if (callback) {
            const row = data[$row.index()];
            callback($table, row, $row);
        }
    });

    // update classes
    $body.find('.undefined').removeClass('undefined');
    $body.find('.card-view-title, .card-view-value').addClass('user-select-none');
    $body.find('.card-view-title').addClass('text-muted');

    return this;
};

/**
 * Set focus on selected page item (if any).
 */
BootstrapTable.prototype.selectPageItem = function () {
    'use strict';
    let selector = false;
    const page = this.focusPageItem || '';
    switch (page) {
        case 'previous':
            selector = 'li.page-pre:not(.disabled) .page-link';
            break;
        case 'next':
            selector = 'li.page-next:not(.disabled) .page-link';
            break;
        case 'active':
            selector = 'li.active .page-link';
            break;
    }
    if (selector) {
        let $link = this.$pagination.find(selector);
        if (0 === $link.length) {
            $link = this.$pagination.find('li.active .page-link');
        }
        $link.focus();
    }
    this.focusPageItem = false;
    return this;
};
