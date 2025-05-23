/**
 * @typedef {Object} Options - the table options
 * @property {number} id - the selected object identifier.
 * @property {string} searchText - the search text.
 * @property {number} totalRows - the total numbers of rows.
 * @property {number} pageNumber - the current page number.
 * @property {number} totalPages - the total numbers of pages.
 * @property {number} pageSize - the page size.
 * @property {number[]} pageList - the pages list.
 * @property {string} rowClass - the row class.
 * @property {string} rowSelector - the row selector.
 * @property {string} customSelector - the custom view selector.
 * @property {string} saveUrl - the URL to save state.
 * @property {string} sortName the sort field.
 * @property {string} sortOrder the sort order ('asc' or 'desc').
 *
 * @typedef {Object} Parameters - the query parameters.
 * @property {number} id - the item identifier.
 * @property {string} caller - the page caller.
 * @property {string} sort - the sorted field.
 * @property {string} order - the sort order.
 * @property {number} offset - the page offset.
 * @property {number} limit - the page size.
 * @property {string} view - the display mode ('table' or 'custom').
 * @property {string} searchText - the search text.
 *
 * @typedef {jQuery|HTMLTableElement|*} jQueryTable - the bootstrap table.
 * @property {Options} getOptions - the table options.
 * @property {boolean} isCustomView - true if custom view is displayed.
 * @property {jQuery<HTMLDivElement>} getCustomView - get the custom view.
 * @property {jQueryTable} enableKeys - enable the key handler.
 * @property {jQueryTable} disableKeys - disable the key handler.
 */

(function ($) {
    'use strict';

    /**
     * The loading message.
     * @type {string|null}
     */
    let loadingMessage = null;

    /**
     * Gets the loading template.
     *
     * @param {string} message - the loading message.
     * @returns {string} the loading template.
     */
    window.loadingTemplate = function (message) {
        if (loadingMessage === null) {
            loadingMessage = $('#loading-template').html();
        }
        return loadingMessage.replace('%message%', message);
    };

    /**
     * JQuery extension for Bootstrap tables rows and cells.
     */
    $(function () {
        $.fn.extend({

            // -------------------------------
            // Row extension
            // -------------------------------

            /**
             * Update the href attribute of drop-down actions.
             *
             * @param {Object} row - the row data.
             * @param {Object} params - the query parameters.
             */
            updateLink: function (row, params) {
                const $link = $(this);
                // console.log($link.attr('href'));
                const regex = /\bid=\d+/i;
                const values = $link.attr('href').split('?');
                values[0] = values[0].replace(/\/\d+/, '/' + row.action);
                if (values.length > 1 && values[1].match(regex)) {
                    params.id = row.action;
                } else {
                    delete params.id;
                }
                const href = values[0] + '?' + $.param(params);
                $link.attr('href', href);
            },

            /**
             * Update the custom view row.
             *
             * @param {jQueryTable} $table - the bootstrap table.
             * @return {boolean} true if the row is found and updated.
             */
            updateCustomRow: function ($table) {
                const index = $(this).parents('.col-custom-view').index();
                const $row = $table.find(`tbody tr[data-index]:eq(${index})`);
                if ($row.length) {
                    return $row.updateRow($table);
                }
                return false;
            },

            /**
             * Update the selected row.
             *
             * @param {jQueryTable} $table - the bootstrap table.
             * @return {boolean} this function returns always true.
             */
            updateRow: function ($table) {
                const $row = $(this);
                // no data?
                if ($row.hasClass('no-records-found')) {
                    return true;
                }
                // get selection class
                const options = $table.getOptions();
                const rowClass = options.rowClass;
                // remove old selection
                $table.find(options.rowSelector).removeClass(rowClass);
                // add selection
                $row.addClass(rowClass);
                // custom view?
                if ($table.isCustomView()) {
                    // remove selection
                    const $view = $table.getCustomView();
                    $view.find(options.customSelector).removeClass(rowClass);
                    // add selection
                    const index = $row.index();
                    const selector = `.custom-item:eq(${index})`;
                    $view.find(selector).addClass(rowClass);
                }
                $table.showSelection();
                $table.trigger('update-row.bs.table', this);

                return true;
            },

            // -------------------------------
            // Table extension
            // -------------------------------

            /**
             * Initialize the table-boostrap.
             *
             * @param {Object} options - the options to merge with default.
             * @return {jQueryTable} this instance for chaining.
             */
            initBootstrapTable: function (options) {
                /** @var jQueryTable $this */
                const $this = $(this);

                // settings
                const defaults = {
                    // select row on click
                    onClickRow: function (_row, $element) {
                        $element.updateRow($this);
                        $this.enableKeys();
                    },
                    // edit row on double-click
                    onDblClickRow: function (_row, $element, field) {
                        $element.updateRow($this);
                        if (field !== 'action') {
                            $element.editRow();
                        }
                    },

                    /**
                     * Handle post-body event
                     * @param {Object[]} data - the rendered data.
                     */
                    onPostBody: function (data) {
                        // remove opacity
                        $this.css('opacity', 1);
                        const isData = data.length !== 0;
                        if (isData) {
                            // select first row if none
                            if (!$this.getSelection()) {
                                $this.selectFirstRow();
                            }
                            // update
                            $this.highlight().updateHref(data);
                        }
                        $this.updateHistory().toggleClass('table-hover', isData);

                        // update pagination links
                        const modalTitle = $this.data('pagination-title');
                        $('.fixed-table-pagination .page-item').each(function (_index, element) {
                            const $item = $(element);
                            const $link = $item.find('.page-link');
                            const isModal = $item.is('.page-first-separator,.page-last-separator');
                            const title = isModal ? modalTitle : $link.attr('aria-label');
                            $link.attr({
                                'href': '#', 'title': title, 'aria-label': title
                            });
                            if (isModal) {
                                $item.removeClass('disabled');
                            }
                        });
                        // update action buttons
                        const title = $this.data('no-action-title');
                        $this.find('td.actions button[data-bs-toggle="dropdown"]').each(function () {
                            const $button = $(this);
                            if ($button.siblings('.dropdown-menu').children().length === 0) {
                                $button.attr('title', title).toggleDisabled(true);
                            }
                        });
                    },

                    /**
                     * Handle post-custom-body event
                     * @param {Object[]} data - the rendered data.
                     */
                    onCustomViewPostBody: function (data) {
                        // data?
                        if (data.length !== 0) {
                            // hide the empty data message
                            $this.hideCustomViewMessage();
                            const $view = $this.getCustomView();
                            const params = $this.getParameters();
                            const selector = '.custom-view-actions:eq(%index%)';
                            const callback = typeof options.onRenderCustomView === 'function' ? options.onRenderCustomView : false;
                            $this.find('tbody tr[data-index] .actions').each(function (index, element) {
                                // copy actions
                                const $rowActions = $(element).children();
                                const rowSelector = selector.replace('%index%', '' + index);
                                const $customActions = $view.find(rowSelector);
                                $rowActions.appendTo($customActions);
                                if (callback) {
                                    const row = data[index];
                                    const $item = $customActions.parents('.custom-item');
                                    callback($this, row, $item, params);
                                }
                            });
                            // display selection
                            const $row = $this.getSelection();
                            if ($row) {
                                $row.updateRow($this);
                            }
                            $this.highlight();
                        } else {
                            // show the empty data message
                            $this.showCustomViewMessage();
                        }
                    },

                    /**
                     * Handle search event.
                     * @param {String} searchText
                     */
                    onSearch: function (searchText) {
                        $this.data('search-text', searchText);
                    }
                };
                const settings = $.extend(true, defaults, options);

                // initialize
                $this.bootstrapTable(settings);
                $this.enableKeys().highlight();
                // select row on right click
                $this.find('tbody').on('mousedown', 'tr[data-index]', function (e) {
                    if (e.button === 2) {
                        $(this).updateRow($this);
                    }
                });
                // handle items in custom view
                $this.getTableContainer().on('mousedown', '.custom-item', function () {
                    $(this).updateCustomRow($this);
                }).on('focus', '.custom-item a.item-link,.custom-item button[data-bs-toggle="dropdown"]', function () {
                    $(this).updateCustomRow($this);
                }).on('dblclick', '.custom-item.table-primary div:not(.rowlink-skip)', function (e) {
                    if (e.button === 0) {
                        $this.editRow();
                    }
                });
                // handle page item click
                $('.fixed-table-pagination').on('keydown mousedown', '.page-link', function (e) {
                    const $that = $(this);
                    const isKeyEnter = e.type === 'keydown' && e.key === 'Enter';
                    const isActive = $that.parents('.page-item').hasClass('active');
                    const isMouseDown = e.type === 'mousedown' && e.button === 0 && !isActive;
                    if (isKeyEnter || isMouseDown) {
                        const $parent = $that.parents('li');
                        if ($parent.hasClass('page-pre')) {
                            $this.data('focusPageItem', 'previous');
                        } else if ($parent.hasClass('page-next')) {
                            $this.data('focusPageItem', 'next');
                        } else {
                            $this.data('focusPageItem', 'active');
                        }
                    }
                });
                // search focus
                $this.getSearchInput().on('focus', function () {
                    $(this).trigger('select');
                });
                return $this;
            },

            /**
             * Gets the boostrap-table option.
             *
             * @return {Options} options the options.
             */
            getOptions: function () {
                return $(this).bootstrapTable('getOptions');
            },

            /**
             * Gets the sortable columns.
             *
             * @return {Object[]} the sortable columns.
             */
            getSortableColumns() {
                const columns = $(this).getOptions().columns[0];
                return columns.filter((column) => column.visible && column.sortable)
                    .map(function (column) {
                        return {
                            'field': column.field,
                            'title': column.title,
                            'order': column.sortOrder,
                            'default': column.default
                        };
                    });
            },

            /**
             * Gets the boostrap-table parameters.
             *
             * @return {Parameters} the parameters.
             */
            getParameters: function () {
                const $this = $(this);
                const options = $this.getOptions();
                const params = {
                    caller: options.caller,
                    sort: options.sortName,
                    order: options.sortOrder,
                    offset: (options.pageNumber - 1) * options.pageSize,
                    limit: options.pageSize,
                    view: $this.getDisplayMode()
                };
                // add search if applicable
                const search = $this.getSearchText();
                if (search.length) {
                    params.search = search;
                }
                // query parameters function?
                if (typeof options.queryParams === 'function') {
                    return $.extend(params, options.queryParams(params));
                }
                return params;
            },

            /**
             * Gets the search text.
             *
             * @return {String} the search text.
             */
            getSearchText: function () {
                return String($(this).data('search-text')) || '';
            },

            /**
             * Gets the search input.
             *
             * @return {jQuery} the search input.
             */
            getSearchInput: function () {
                const options = $(this).getOptions();
                if (typeof options.searchSelector === 'string') {
                    return $(options.searchSelector);
                }
                return $('.bootstrap-table .search-input');
            },

            /**
             * Return if a search text is not empty.
             *
             * @return {boolean} true if a search text is not empty.
             */
            isSearchText: function () {
                return $(this).getSearchText().length > 0;
            },

            /**
             * Return if the custom view mode is displayed.
             *
             * @return {boolean} true if displayed.
             */
            isCustomView: function () {
                const data = $(this).getBootstrapTable();
                return data && data.customViewDefaultView;
            },

            /**
             * Returns if no loaded data (rows) is displayed.
             *
             * @return {boolean} true if not data is displayed.
             */
            isEmpty: function () {
                return $(this).getData().length === 0;
            },

            /**
             * Get the loaded data (rows) of table when this method is called.
             *
             * @return {Object[]} the loaded data.
             */
            getData: function () {
                return $(this).bootstrapTable('getData');
            },

            /**
             * Gets the bootstrap table.
             *
             * @return {Object} the bootstrap table.
             */
            getBootstrapTable: function () {
                return $(this).data('bootstrap.table');
            },

            /**
             * Gets the selected row.
             *
             * @return {jQuery} the selected row, if any; null otherwise.
             */
            getSelection: function () {
                const $this = $(this);
                const $row = $this.find($this.getOptions().rowSelector);
                return $row.length ? $row : null;
            },

            /**
             * Gets the selected row index.
             *
             * @return {int} the selected row index, if any; -1 otherwise.
             */
            getSelectionIndex: function () {
                const $row = $(this).getSelection();
                return $row ? $row.index() : -1;
            },

            /**
             * Scroll the selected row, if any, into the visible area of the browser window.
             *
             * @return {jQuery} this instance for chaining.
             */
            showSelection: function () {
                const $this = $(this);
                let $row = $this.getSelection();
                if (!$row) {
                    return this;
                }
                if ($this.isCustomView()) {
                    const index = $row.index();
                    const options = $this.getOptions();
                    const rowClass = options.rowClass;
                    const $view = $this.getCustomView();
                    const selector = `.custom-item:eq(${index})`;
                    $row = $view.find(selector);
                    $row.addClass(rowClass);
                }
                $row.scrollInViewport();

                return $this;
            },


            /**
             * Gets the custom view container.
             *
             * @return {jQuery<HTMLDivElement>|null} the custom view container, if displayed, null otherwise.
             */
            getCustomView: function () {
                const $this = $(this);
                if (!$this.isCustomView()) {
                    return null;
                }
                const $parent = $this.getTableContainer();
                return $parent.find('.fixed-table-custom-view');
            },

            /**
             * Gets the bootstrap table container.
             * @return {jQuery|HTMLDivElement|*}
             */
            getTableContainer: function () {
                return $(this).parents('.bootstrap-table');
            },

            /**
             * Save parameters to the session.
             *
             * @return {jQuery} this instance for chaining.
             */
            saveParameters: function () {
                const $this = $(this);
                const url = $this.getOptions().saveUrl;
                if (url) {
                    const data = {view: $this.getDisplayMode()};
                    $.post(url, data);
                }
                return $this;
            },

            /**
             * Update the href attribute of actions.
             *
             * @param {Object[]} rows - the rendered data.
             * @return {jQuery} this instance for chaining.
             */
            updateHref: function (rows) {
                const $this = $(this);
                const options = $this.getOptions();
                const params = $this.getParameters();
                const onUpdateHref = typeof options.onUpdateHref === 'function' ? options.onUpdateHref : false;
                const onRenderAction = typeof options.onRenderAction === 'function' ? options.onRenderAction : false;
                // run over rows
                $this.find('tbody tr[data-index]').each(function (index) {
                    const $row = $(this);
                    const row = rows[index];
                    const $links = $(this).find('a.dropdown-item-path');
                    if (!$links.length) {
                        return true;
                    }
                    $links.each(function () {
                        const $link = $(this);
                        $link.updateLink(row, params);
                        if (onRenderAction) {
                            onRenderAction($this, row, $row, $link);
                        }
                    });
                    if (onUpdateHref) {
                        onUpdateHref($this, $links);
                    }
                });
                return $this;
            },

            /**
             * Refresh/reload the remote server data.
             *
             * @param {Object} [options] - the refresh options.
             * @return {jQuery} this instance for chaining.
             */
            refresh: function (options) {
                return $(this).bootstrapTable('refresh', options || {});
            },

            /**
             * Reset the search text.
             *
             * @param {String} [text] - the optional search text.
             * @return {jQuery} this instance for chaining.
             */
            resetSearch: function (text) {
                return $(this).bootstrapTable('resetSearch', text || '');
            },

            /**
             * Refresh the table options.
             *
             * @param {Object} [options] - the options to refresh.
             * @return {jQuery} this instance for chaining.
             */
            refreshOptions: function (options) {
                return $(this).bootstrapTable('refreshOptions', options || {});
            },

            /**
             * Toggles the view between the table and the custom view.
             *
             * @return {jQuery} this instance for chaining.
             */
            toggleCustomView: function () {
                return $(this).bootstrapTable('toggleCustomView');
            },

            /**
             * Toggles the display mode.
             *
             * @param {String} mode - the display mode to set ('table' or 'custom').
             * @return {jQuery} this instance for chaining.
             */
            setDisplayMode: function (mode) {
                const $this = $(this);
                if ($this.getDisplayMode() !== mode) {
                    $this.toggleCustomView();
                    $this.saveParameters();
                }
                return $this;
            },

            /**
             * Gets the display mode.
             *
             * @return {String} the display mode ('table' or 'custom').
             */
            getDisplayMode: function () {
                const $this = $(this);
                return $this.isCustomView() ? 'custom' : 'table';
            },

            /**
             * Highlight matching text.
             *
             * @return {jQuery} this instance for chaining.
             */
            highlight: function () {
                const $this = $(this);
                const text = $this.getSearchText();
                if (!text) {
                    return $this;
                }
                const options = {
                    element: 'span',
                    className: 'text-success',
                    separateWordSearch: false,
                    ignorePunctuation: ['\'', ',']
                };
                if ($this.isCustomView()) {
                    $this.getCustomView().find('.custom-item').mark(text, options);
                } else {
                    $this.find('tbody td:not(.rowlink-skip)').mark(text, options);
                }
                return $this;
            },

            /**
             * Shows the previous page.
             *
             * @param {boolean} selectLast - true to select the last row.
             * @return {boolean} true if the previous page is displayed.
             */
            showPreviousPage: function (selectLast) {
                const $this = $(this);
                const options = $this.getOptions();
                if (options.pageNumber > 1) {
                    if (selectLast) {
                        $this.one('post-body.bs.table', function () {
                            $this.selectLastRow();
                        });
                    }
                    $this.bootstrapTable('prevPage');
                    return true;
                }
                return false;
            },

            /**
             * Shows the next page.
             *
             * @return {boolean} true if the next page is displayed.
             */
            showNextPage: function () {
                const $this = $(this);
                const options = $this.getOptions();
                if (options.pageNumber < options.totalPages) {
                    $this.bootstrapTable('nextPage');
                    return true;
                }
                return false;
            },

            /**
             * Select the given page.
             * Do nothing if the page is out of allowed values.
             *
             * @param {number} page - the page to select.
             * @param {boolean} [force] - true to load the page, even if the page is displayed
             *
             * @return {boolean} true if the page is displayed.
             */
            selectPage: function (page, force) {
                const $this = $(this);
                const options = $this.getOptions();
                if (page >= 1 && page <= options.totalPages && (page !== options.pageNumber || force)) {
                    $this.bootstrapTable('selectPage', page);
                    return true;
                }
                return false;
            },

            /**
             * Sort the data.
             *
             * @param {String} sortName - the sort field.
             * @param {String} sortOrder - the sort order ('asc' or 'desc').
             *
             * @return {jQuery} this instance for chaining.
             */
            sort: function (sortName, sortOrder) {
                const options = this.getOptions();
                if (options.sortName !== sortName || options.sortOrder !== sortOrder) {
                    $(this).bootstrapTable('sortBy', {
                        field: sortName, sortOrder: sortOrder
                    });
                }
                return this;
            },

            /**
             * Select the first row.
             *
             * @return {boolean} true if the first row is selected.
             */
            selectFirstRow: function () {
                const $this = $(this);
                const $row = $this.getSelection();
                const $first = $this.find('tbody tr[data-index]:first');
                if ($first.length && $first !== $row) {
                    return $first.updateRow($this);
                }
                return false;
            },

            /**
             * Select the last row.
             *
             * @return {boolean} true if the last row is selected.
             */
            selectLastRow: function () {
                const $this = $(this);
                const $row = $this.getSelection();
                const $last = $this.find('tbody tr[data-index]:last');
                if ($last.length && $last !== $row) {
                    return $last.updateRow($this);
                }
                return false;
            },

            /**
             * Select the previous row and if no row is available, the previous page is displayed.
             *
             * @return {boolean} true if the previous row is selected.
             */
            selectPreviousRow: function () {
                const $this = $(this);
                const $row = $this.getSelection();
                if (!$row) {
                    return false;
                }
                const $prev = $row.prev('tr[data-index]');
                if ($row.length && $prev.length) {
                    return $prev.updateRow($this);
                }
                // previous page
                return $this.showPreviousPage(true);
            },

            /**
             * Select the next row and if no row is available, the next page is displayed.
             *
             * @return {boolean} true if the next row is selected.
             */
            selectNextRow: function () {
                const $this = $(this);
                const $row = $this.getSelection();
                if (!$row) {
                    return false;
                }
                const $next = $row.next('tr[data-index]');
                if ($row.length && $next.length) {
                    return $next.updateRow($this);
                }
                // next page
                return $this.showNextPage();
            },

            /**
             * Finds an action for the given selector
             *
             * @param {String} actionSelector - the action selector.
             * @return {jQuery} the action, if found; null otherwise.
             */
            findAction: function (actionSelector) {
                let $link;
                let $parent;
                let selector;
                const $this = $(this);
                if ($this.isCustomView()) {
                    $parent = $this.getCustomView();
                    selector = $this.getOptions().customSelector;
                } else {
                    $parent = $this;
                    selector = $this.getOptions().rowSelector;
                }
                $link = $parent.find(`${selector} ${actionSelector}`);
                return $link.length ? $link : null;
            },

            /**
             * Call the edit action for the selected row (if any).
             *
             * @return {boolean} true if the action is called.
             */
            editRow: function () {
                const $link = $(this).findAction('a.btn-default');
                if ($link) {
                    $link[0].click();
                    return true;
                }
                return false;
            },

            /**
             * Call the delete action for the selected row (if any).
             *
             * @return {boolean} true if the action is called.
             */
            deleteRow: function () {
                const $link = $(this).findAction('a.btn-delete');
                if ($link) {
                    $link[0].click();
                    return true;
                }
                return false;
            },

            /**
             * Enable the key handler.
             *
             * @return {jQuery} this instance for chaining.
             */
            enableKeys: function () {
                const $this = $(this);
                let keyHandler = $this.data('keys.handler');
                if (!keyHandler) {
                    /** @param {KeyboardEvent} e */
                    keyHandler = function (e) {
                        if (e.key === '' || e.ctrlKey || e.metaKey || e.altKey) {
                            return;
                        }
                        switch (e.key) {
                            case 'Enter':
                                if ($this.editRow()) {
                                    e.preventDefault();
                                }
                                break;
                            case 'Home':
                                if ($this.selectFirstRow()) {
                                    e.preventDefault();
                                }
                                break;
                            case 'End':
                                if ($this.selectLastRow()) {
                                    e.preventDefault();
                                }
                                break;
                            case 'PageUp':
                                if ($this.showPreviousPage(false)) {
                                    e.preventDefault();
                                }
                                break;
                            case 'PageDown':
                                if ($this.showNextPage()) {
                                    e.preventDefault();
                                }
                                break;
                            case 'ArrowLeft':
                            case 'ArrowUp':
                                if ($this.selectPreviousRow()) {
                                    e.preventDefault();
                                }
                                break;
                            case 'ArrowRight':
                            case 'ArrowDown':
                                if ($this.selectNextRow()) {
                                    e.preventDefault();
                                }
                                break;
                            case 'Delete':
                                if ($this.deleteRow()) {
                                    e.preventDefault();
                                }
                                break;
                        }
                    };
                    $this.data('keys.handler', keyHandler);
                }
                $(document).off('keydown.bs.table', keyHandler).on('keydown.bs.table', keyHandler);
                return $this;
            },

            /**
             * Disable the key handler.
             *
             * @return {jQuery} this instance for chaining.
             */
            disableKeys: function () {
                const $this = $(this);
                const keyHandler = $this.data('keys.handler');
                if (keyHandler) {
                    $(document).off('keydown.bs.table', keyHandler);
                }
                return $this;
            },

            /**
             * Update the history state.
             */
            updateHistory: function () {
                const $this = $(this);
                const params = $this.getParameters();
                delete params.caller;

                let url = '';
                for (const [key, value] of Object.entries(params)) {
                    url += url.match('[?]') ? '&' : '?';
                    url += key + '=' + encodeURIComponent(String(value));
                }
                window.history.pushState({}, '', url);
                return $this;
            },

            /**
             * Hide the empty data message in custom view.
             */
            hideCustomViewMessage: function () {
                const $view = $(this).getCustomView();
                if ($view) {
                    $view.find('.no-records-found').remove();
                }
            },

            /**
             * Show the empty data message in custom view.
             */
            showCustomViewMessage: function () {
                const $this = $(this);
                const $view = $this.getCustomView();
                if ($view && $view.find('.no-records-found').length === 0) {
                    $('<p/>', {
                        class: 'no-records-found text-center text-secondary border p-2 mt-2 mb-0',
                        text: $this.getOptions().formatNoMatches()
                    }).appendTo($view);
                }
            },

            /**
             * Format the pages range.
             *
             * @param {Options} [options] the options to get pages from.
             * @param {number} [pageNumber] the optional current page.
             * @return {String} the formatted pages.
             */
            formatPages: function (options, pageNumber) {
                const $this = $(this);
                options = options ? options : $this.getOptions();
                pageNumber = pageNumber || options.pageNumber;
                const text = $('#modal-page').data('page');
                return text.replace('%page%', $.formatInt(pageNumber))
                    .replace('%pages%', $.formatInt(options.totalPages));
            },

            /**
             * Format the records range.
             *
             * @param {Options} [options] the options to get pages from.
             * @param {number} [pageNumber] the optional current page.
             */
            formatRecords: function (options, pageNumber) {
                const $this = $(this);
                options = options ? options : $this.getOptions();
                pageNumber = pageNumber || options.pageNumber;
                const pageSize = options.pageSize;
                const record = Math.max(1 + (pageNumber - 1) * pageSize, 1);
                const records = Math.min(record + pageSize - 1, options.totalRows);
                const text = $('#modal-page').data('record');
                return text.replace('%record%', $.formatInt(record))
                    .replace('%records%', $.formatInt(records));
            },

            /**
             * Initialize the select page dialog.
             */
            initPageDialog: function () {
                const $dialog = $('#modal-page');
                if ($dialog.length === 0 || $dialog.data('initialized')) {
                    return;
                }
                $dialog.data('initialized', true);

                const $this = $(this);
                const $range = $('#page-range');
                const $labelRecord = $('#page-record');
                const $labelLabel = $('#page-label');
                const $button = $('#page-button');
                $dialog.on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        $button.trigger('click');
                    }
                }).on('show.bs.modal', function () {
                    $this.disableKeys();
                    $.hideDropDownMenus();
                    const options = $this.getOptions();
                    $('#page-range').val(options.pageNumber)
                        .attr('max', options.totalPages)
                        .data('options', options)
                        .trigger('input');
                }).on('shown.bs.modal', function () {
                    $range.trigger('focus');
                }).on('hide.bs.modal', function () {
                    $this.enableKeys();
                }).on('hidden.bs.modal', function () {
                    const $source = $dialog.data('source');
                    if ($source) {
                        $dialog.removeData('source');
                        $source.trigger('focus');
                    }
                });

                const modalOptions = $this.getOptions().draggableModal || false;
                if (modalOptions) {
                    $dialog.draggableModal(modalOptions);
                }

                $range.on('input', function () {
                    const value = $range.intVal();
                    const title = $this.formatPages($range.data('options'), value);
                    const records = $this.formatRecords($range.data('options'), value);
                    $range.attr('title', title);
                    $labelLabel.text(title);
                    $labelRecord.text(records);
                });

                $button.on('click', function () {
                    $dialog.modal('hide');
                    $this.selectPage($range.intVal(), false);
                });
            },

            /**
             * Initialize the sort dialog.
             */
            initSortDialog: function () {
                const $dialog = $('#modal-sort');
                if ($dialog.length === 0 || $dialog.data('initialized')) {
                    return;
                }
                $dialog.data('initialized', true);

                const $this = $(this);
                const $sortName = $('#sort-name');
                const $button = $('#sort-button');
                const $default = $('#sort-default-button');
                const $tooltip = $dialog.find('[data-bs-toggle="tooltip"]');
                $dialog.on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        $button.trigger('click');
                    }
                }).on('show.bs.modal', function () {
                    $this.disableKeys();
                    $.hideDropDownMenus();
                    const options = $this.getOptions();
                    $sortName.val(options.sortName);
                    $('#sort-order-' + options.sortOrder).setChecked(true);
                }).on('shown.bs.modal', function () {
                    $sortName.trigger('focus');
                }).on('hide.bs.modal', function () {
                    $this.enableKeys();
                }).on('hidden.bs.modal', function () {
                    $tooltip.tooltip('hide');
                });

                const modalOptions = $this.getOptions().draggableModal || false;
                if (modalOptions) {
                    $dialog.draggableModal(modalOptions);
                }
                $tooltip.tooltip();

                $sortName.on('input', function () {
                    // update default order
                    const sortOrder = $sortName.getSelectedOption().data('sort');
                    if (sortOrder) {
                        $('#sort-order-' + sortOrder).setChecked(true);
                    }
                });

                $default.on('click', function () {
                    // select default order
                    const $option = $('#sort-name [data-default="true"]');
                    if ($option.length) {
                        const sortName = $option.val();
                        const sortOrder = $option.data('sort');
                        if (sortName && sortOrder) {
                            $sortName.val(sortName);
                            $(`#sort-order-${sortOrder}`).setChecked(true);
                            $button.trigger('click');
                        }
                    }
                });

                $button.on('click', function () {
                    $dialog.modal('hide');
                    const sortName = $sortName.val();
                    const sortOrder = $('[name="sort-order"]:checked').val();
                    $this.sort(sortName, sortOrder);
                });
            }
        });
    });
}(jQuery));
