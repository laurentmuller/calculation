/**! compression tag for ftp-deployment */

/**
 * @typedef {Object} Options - the table options
 * @property {number} id - the selected object identifier.
 * @property {string} searchText - the search text.
 * @property {number} pageNumber - the current page number.
 * @property {number} totalPages - the total number of pages.
 * @property {string} rowClass - the row class.
 * @property {string} rowSelector - the row selector.
 * @property {string} customSelector - the custom view selector.
 * @property {string} saveUrl - the URL to save state.
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
 * @typedef {JQuery} JQueryTable - the bootstrap table.
 * @property {Options} getOptions - the options.
 * @property {boolean} isCustomView - true if custom view is displayed.
 * @property {JQuery} getCustomView - get the custom view.
 * @property {JQueryTable} enableKeys - enable the key handler.
 * @property {JQueryTable} disableKeys - disable the key handler.
 */

/**
 * Gets the loading template.
 *
 * @param {string} message - the loading message.
 * @returns {string} the loading template.
 */
function loadingTemplate(message) {
    'use strict';
    return `<div class="alert alert-info text-center loading-message" role="alert"><i class="fa-solid fa-spinner fa-spin mr-2"></i>${message}</div>`;
}

/**
 * JQuery's extension for Bootstrap tables, rows and cells.
 */
(function ($) {
    'use strict';

    $.fn.extend({

        // -------------------------------
        // Row extension
        // -------------------------------

        /**
         * Update the href attribute of the action.
         *
         * @param {Object} row - the row data.
         * @param {Object} params - the query parameters.
         */
        updateLink: function (row, params) {
            const $link = $(this);
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
        },

        /**
         * Update the selected row.
         *
         * @param {JQueryTable} $table - the bootstrap table.
         * @return {boolean} this function returns always true.
         */
        updateRow: function ($table) {
            const $row = $(this);
            const options = $table.getOptions();
            const rowClass = options.rowClass;

            // no data?
            if ($row.hasClass('no-records-found')) {
                return true;
            }

            // already selected?
            if ($row.hasClass(rowClass)) {
                return true;
            }

            // remove old selection
            $table.find(options.rowSelector).removeClass(rowClass);

            // add selection
            $row.addClass(rowClass);

            // custom view?
            if ($table.isCustomView()) {
                const $view = $table.getCustomView();
                $view.find('.custom-item.' + rowClass).removeClass(rowClass);
                $view.find('.custom-item:eq(' + $row.index() + ')').addClass(rowClass);
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
         * @param {object} options - the options to merge with default.
         * @return {JQueryTable} this instance for chaining.
         */
        initBootstrapTable: function (options) {
            /** @var JQueryTable $this */
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

                // update UI on post page load
                onPostBody: function (content) {
                    const isData = content.length !== 0;
                    if (isData) {
                        // select first row if none
                        if (!$this.getSelection()) {
                            $this.selectFirstRow();
                        }
                        // update
                        $this.highlight().updateHref(content);
                    }
                    $this.updateHistory().toggleClass('table-hover', isData);

                    // update pagination links
                    let foundPages = false;
                    const $modalPage = $('#modal-page');
                    const modalTitle = $('#modal-page .modal-title').text();
                    $('.fixed-table-pagination .page-item').each(function (_index, element) {
                        const $item = $(element);
                        const $link = $item.children('.page-link:first');
                        const isModal = $item.hasClass('page-first-separator') || $item.hasClass('page-last-separator');
                        const title = isModal ? modalTitle : $link.attr('aria-label');
                        $link.attr({
                            'href': '#',
                            'title': title,
                            'aria-label': title
                        });
                        if (isModal) {
                            foundPages = true;
                            $item.removeClass('disabled');
                            $link.on('click', function () {
                                $modalPage.data('source', $link).modal('show');
                            });
                        }
                    });

                    // update action buttons
                    const title = $this.data('no-action-title');
                    $this.find('td.actions button[data-toggle="dropdown"]').each(function () {
                        const $button = $(this);
                        if ($button.siblings('.dropdown-menu').children().length === 0) {
                            $button.attr('title', title).toggleDisabled(true);
                        }
                    });

                    // initialize dialogs
                    if (foundPages) {
                        $this.initPageDialog();
                    }
                    if ($this.data('sortable')) {
                        $this.initSortDialog();
                    }
                },

                onCustomViewPostBody: function (data) {
                    const $view = $this.getCustomView();

                    // data?
                    if (data.length !== 0) {
                        // hide empty data message
                        $this.hideCustomViewMessage();

                        const params = $this.getParameters();
                        const selector = '.custom-view-actions:eq(%index%)';
                        const callback = typeof options.onRenderCustomView === 'function' ? options.onRenderCustomView : false;
                        $this.find('tbody tr .actions').each(function (index, element) {
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
                        const $selection = $view.find(options.customSelector);
                        if ($selection.length) {
                            $selection.scrollInViewport();
                        }
                        $this.highlight();
                    } else {
                        // show empty data message
                        $this.showCustomViewMessage();
                    }
                    $this.saveParameters();
                },


                onSearch: function (searchText) {
                    // update data
                    $this.data('search-text', searchText);
                }
            };
            const settings = $.extend(true, defaults, options);

            // initialize
            $this.bootstrapTable(settings);
            $this.enableKeys().highlight();

            // select row on right click
            $this.find('tbody').on('mousedown', 'tr', function (e) {
                if (e.button === 2) {
                    $(this).updateRow($this);
                }
            });

            // handle items in custom view
            $this.parents('.bootstrap-table').on('mousedown', '.custom-item', function () {
                const index = $(this).parent().index();
                const $row = $this.find('tbody tr:eq(' + index + ')');
                if ($row.length) {
                    $row.updateRow($this);
                }
            }).on('dblclick', '.custom-item.table-primary div:not(.rowlink-skip)', function (e) {
                if (e.button === 0) {
                    $this.editRow();
                }
            });

            // handle page item click
            $('.fixed-table-pagination').on('keydown mousedown', '.page-link', function (e) {
                const $that = $(this);
                const isKeyEnter = e.type === 'keydown' && e.which === 13;
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
            $('input.search-input').on('focus', function () {
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
         * Gets the boostrap-table parameters.
         *
         * @return {Parameters} the parameters.
         */
        getParameters: function () {
            const $this = $(this);
            const options = $this.getOptions();
            const params = {
                'caller': options.caller,
                'sort': options.sortName,
                'order': options.sortOrder,
                'offset': (options.pageNumber - 1) * options.pageSize,
                'limit': options.pageSize,
                'view': $this.getDisplayMode()
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
         * @return {string} the search text.
         */
        getSearchText: function () {
            return String($(this).data('search-text'));
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
         * Get the loaded data (rows) of table at the moment that this method is called.
         *
         * @return {array} the loaded data.
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
         * @return {JQuery} the selected row, if any; null otherwise.
         */
        getSelection: function () {
            const $this = $(this);
            const $row = $this.find($this.getOptions().rowSelector);
            return $row.length ? $row : null;
        },

        /**
         * Scroll the selected row, if any, into the visible area of the browser window.
         *
         * @return {JQuery} this instance for chaining.
         */
        showSelection: function () {
            const $this = $(this);
            let $row = $this.getSelection();
            if ($row) {
                if ($this.isCustomView()) {
                    $row = $this.getCustomView().find('.custom-item:eq(' + $row.index() + ')');
                }
                $row.scrollInViewport();
            }
            return $this;
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
         * Gets the custom view container.
         *
         * @return {JQuery} the custom view container, if displayed, null otherwise.
         */
        getCustomView: function () {
            const $this = $(this);
            if ($this.isCustomView()) {
                const $parent = $this.parents('.bootstrap-table');
                return $parent.find('.fixed-table-custom-view');
            }
            return null;
        },

        /**
         * Save parameters to the session.
         *
         * @return {JQuery} this instance for chaining.
         */
        saveParameters: function () {
            const $this = $(this);
            const url = $this.getOptions().saveUrl;
            if (url) {
                $.post(url, $this.getParameters());
            }
            return $this;
        },

        /**
         * Update the href attribute of the actions.
         *
         * @param {array} rows - the rendered data.
         * @return {JQuery} this instance for chaining.
         */
        updateHref: function (rows) {
            const $this = $(this);
            const options = $this.getOptions();
            const params = $this.getParameters();
            const onUpdateHref = typeof options.onUpdateHref === 'function' ? options.onUpdateHref : false;
            const onRenderAction = typeof options.onRenderAction === 'function' ? options.onRenderAction : false;

            // run over rows
            $this.find('tbody tr').each(function () {
                const $row = $(this);
                const row = rows[$row.index()];
                const $paths = $(this).find('.dropdown-item-path');
                if ($paths.length) {
                    $paths.each(function () {
                        // update link
                        const $link = $(this);
                        $link.updateLink(row, params);
                        if (onRenderAction) {
                            onRenderAction($this, row, $row, $link);
                        }
                    });
                    // update row
                    if (onUpdateHref) {
                        onUpdateHref($this, $paths);
                    }
                }
            });

            return $this;
        },

        /**
         * Refresh/reload the remote server data.
         *
         * @param {object} [options] - the refresh options.
         * @return {JQuery} this instance for chaining.
         */
        refresh: function (options) {
            return $(this).bootstrapTable('refresh', options || {});
        },

        /**
         * Reset the search text.
         *
         * @param {string} [text] - the optional search text.
         * @return {JQuery} this instance for chaining.
         */
        resetSearch: function (text) {
            return $(this).bootstrapTable('resetSearch', text || '');
        },

        /**
         * Refresh the table options.
         *
         * @param {Object} [options] - the options to refresh.
         * @return {JQuery} this instance for chaining.
         */
        refreshOptions: function (options) {
            return $(this).bootstrapTable('refreshOptions', options || {});
        },

        /**
         * Toggles the view between the table and the custom view.
         *
         * @return {JQuery} this instance for chaining.
         */
        toggleCustomView: function () {
            return $(this).bootstrapTable('toggleCustomView');
        },

        /**
         * Toggles the display mode.
         *
         * @param {string} mode - the display mode to set ('table' or 'custom').
         * @return {JQuery} this instance for chaining.
         */
        setDisplayMode: function (mode) {
            const $this = $(this);
            if ($this.getDisplayMode() === mode) {
                return $this;
            }
            switch (mode) {
                case 'custom':
                    if (!$this.isCustomView()) {
                        $this.toggleCustomView();
                    }
                    break;
                default: // table
                    if ($this.isCustomView()) {
                        $this.toggleCustomView();
                    }
                    break;
            }
            return $this.saveParameters();
        },

        /**
         * Gets the display mode.
         *
         * @return {string} the display mode ('table' or 'custom').
         */
        getDisplayMode: function () {
            const $this = $(this);
            return $this.isCustomView() ? 'custom' : 'table';
        },

        /**
         * Highlight matching text.
         *
         * @return {JQuery} this instance for chaining.
         */
        highlight: function () {
            const $this = $(this);
            const text = $this.getSearchText();
            if (text.length > 0) {
                const options = {
                    element: 'span',
                    className: 'text-success',
                    separateWordSearch: false,
                    ignorePunctuation: ["'", ","]
                };
                if ($this.isCustomView()) {
                    $this.getCustomView().find('.custom-item').mark(text, options);
                } else {
                    $this.find('tbody td:not(.rowlink-skip)').mark(text, options);
                }
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
         * Select the given page. Do nothing if the page is out of allowed values.
         *
         * @param {number} page - the page to select.
         * @param {boolean} [force] - true to load the page, even if it is the page currently displayed
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
         * @param {string} sortName - the sort field.
         * @param {string} sortOrder - the sort order ('asc' or 'desc').
         *
         * @return {JQuery} this instance for chaining.
         */
        sort: function (sortName, sortOrder) {
            const data = this.getBootstrapTable();
            if (data && data.options.sortName !== sortName || data.options.sortOrder !== sortOrder) {
                data.options.sortName = sortName;
                data.options.sortOrder = sortOrder;
                this.refresh();
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
            const $first = $this.find('tbody tr:first');
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
            const $last = $this.find('tbody tr:last');
            if ($last.length && $last !== $row) {
                return $last.updateRow($this);
            }
            return false;
        },

        /**
         * Select the previous row. If no row is available, the previous page is displayed.
         *
         * @return {boolean} true if the previous row is selected.
         */
        selectPreviousRow: function () {
            const $this = $(this);
            const $row = $this.getSelection();
            const $prev = $row.prev('tr');
            if ($row.length && $prev.length) {
                return $prev.updateRow($this);
            }
            // previous page
            return $this.showPreviousPage(true);
        },

        /**
         * Select the next row. If no row is available, the next page is displayed.
         *
         * @return {boolean} true if the next row is selected.
         */
        selectNextRow: function () {
            const $this = $(this);
            const $row = $this.getSelection();
            const $next = $row.next('tr');
            if ($row.length && $next.length) {
                return $next.updateRow($this);
            }
            // next page
            return $this.showNextPage();
        },

        /**
         * Finds an action for the given selector
         *
         * @param {string} actionSelector - the action selector.
         * @return {JQuery} the action, if found; null otherwise.
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
            $link = $parent.find(selector + ' ' + actionSelector);
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
         * @return {JQuery} this instance for chaining.
         */
        enableKeys: function () {
            const $this = $(this);

            // get or create the key handler
            let keyHandler = $this.data('keys.handler');
            if (!keyHandler) {
                keyHandler = function (e) {
                    if ((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                        return;
                    }
                    switch (e.keyCode) {
                        case 13:  // enter (edit action on selected row)
                            if ($this.editRow()) {
                                e.preventDefault();
                            }
                            break;
                        case 33: // page up (previous page)
                            if ($this.showPreviousPage(false)) {
                                e.preventDefault();
                            }
                            break;
                        case 34: // page down (next page)
                            if ($this.showNextPage()) {
                                e.preventDefault();
                            }
                            break;
                        case 35: // end (last row of the current page)
                            if ($this.selectLastRow()) {
                                e.preventDefault();
                            }
                            break;
                        case 36: // home (first row of the current page)
                            if ($this.selectFirstRow()) {
                                e.preventDefault();
                            }
                            break;
                        case 37: // left arrow (previous row of the current page)
                        case 38: // up arrow
                            if ($this.selectPreviousRow()) {
                                e.preventDefault();
                            }
                            break;
                        case 39: // right arrow (next row of the current page)
                        case 40: // down arrow
                            if ($this.selectNextRow()) {
                                e.preventDefault();
                            }
                            break;
                        case 46: // delete (delete action of the selected row)
                            if ($this.deleteRow()) {
                                e.preventDefault();
                            }
                            break;
                    }
                };
                $this.data('keys.handler', keyHandler);
            }

            // add handlers
            $(document).off('keydown.bs.table', keyHandler).on('keydown.bs.table', keyHandler);
            return $this;
        },

        /**
         * Disable the key handler.
         *
         * @return {JQuery} this instance for chaining.
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
                    class: 'no-records-found text-center border-top p-2 mb-1',
                    text: $this.getOptions().formatNoMatches()
                }).appendTo($view);
            }
        },

        /**
         * Format the pages range.
         *
         * @param {Options} [options] the options to get pages from.
         * @param {number} [pageNumber] the optional current page.
         * @return {string} the formatted pages.
         */
        formatPages: function (options, pageNumber) {
            const $this = $(this);
            options = options ? options : $this.getOptions();
            pageNumber = pageNumber || options.pageNumber;
            const text = $('#modal-page').data('page');
            return text.replace('%page%', pageNumber)
                .replace('%pages%', options.totalPages);
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
            const $label = $('#page-label');
            const $button = $('#page-button');

            $dialog.on('keydown', function (e) {
                if (e.which === 13) { // enter
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
                const title = $this.formatPages($range.data('options'), $range.intVal());
                $range.attr('title', title);
                $label.text(title);
            });

            $button.on('click', function () {
                $dialog.modal('hide');
                $this.selectPage($range.intVal(), true);
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

            $dialog.on('keydown', function (e) {
                if (e.which === 13) { // enter
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
            });

            const modalOptions = $this.getOptions().draggableModal || false;
            if (modalOptions) {
                $dialog.draggableModal(modalOptions);
            }

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
                        $('#sort-order-' + sortOrder).setChecked(true);
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
}(jQuery));
