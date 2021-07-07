/**! compression tag for ftp-deployment */

/**
 * Gets the loading template.
 *
 * @param {string}
 *            message - the loading message.
 * @returns {string} the loading template.
 */
function loadingTemplate(message) { // jshint ignore:line
    'use strict';
    return '<i class="fa fa-spinner fa-spin fa-fw"></i>' + message;
}

/**
 * jQuery extension for Bootstrap tables, rows and cells.
 */
$.fn.extend({

    // -------------------------------
    // Row extension
    // -------------------------------

    /**
     * Update the selected row.
     *
     * @param {jQuery}
     *            $table - the parent table.
     * @return {boolean} this function returns always true.
     */
    updateRow: function ($table) {
        'use strict';
        const $row = $(this);
        const options = $table.getOptions();

        // already selected?
        if ($row.hasClass(options.rowClass)) {
            return true;
        }

        // no data?
        if($row.hasClass('no-records-found')) {
            return true;
        }

        // remove old selection
        $table.find(options.rowSelector).removeClass(options.rowClass);

        // add selection
        $row.addClass(options.rowClass);

        // custom view?
        if ($table.isCustomView()) {
            const $view = $table.getCustomView();
            $view.find('.custom-item').removeClass(options.rowClass);
            const $selection = $view.find('.custom-item:eq(' + $row.index() + ')');
            if ($selection.length) {
                $selection.addClass(options.rowClass).scrollInViewport();
            }
        } else {
            $row.scrollInViewport();
        }
        $table.trigger('update-row.bs.table', this);

        return true;
    },

    /**
     * Replace the tag name.
     *
     * @param {string}
     *            newTag - the new tag name.
     * @param {array} -
     *            excludeAttributes - the attributes to exclude.
     * @return {jQuery} the elements for chaining.
     */
    tagName: function (newTag, excludeAttributes){
        'use strict';
        newTag = "<" + newTag + ">";
        excludeAttributes = excludeAttributes || [];

        return $(this).each(function (index, element){
            const $element = $(element);
            const $newTag = $(newTag, {
                html: $element.html()
            });
            $.each(element.attributes, function (i, attribute) {
                if (!excludeAttributes.includes(attribute.name)) {
                    $newTag.attr(attribute.name, attribute.value);
                }
            });
            $element.replaceWith($newTag);
        });
    },

    /**
     * Update the href attribute of the action.
     *
     * @param {Object}
     *            row - the row data.
     * @param {Object}
     *            params - the query parameters.
     */
    updateLink: function (row, params) {
        'use strict';
        const $link = $(this);
        const regex = /\bid=\d+/;
        const values = $link.attr('href').split('?');

        values[0] = values[0].replace(/\/\d+/, '/' + row.action);
        if(values.length > 1 && values[1].match(regex)) {
            params.id = row.action;
        } else {
            delete params.id;
        }
        const href = values[0] + '?' + $.param(params);
        $link.attr('href', href);
    },

    // -------------------------------
    // Table extension
    // -------------------------------

    /**
     * Initialize the table-boostrap.
     *
     * @param {object}
     *            options - the options to merge with default.
     * @return {jQuery} this instance for chaining.
     */
    initBootstrapTable: function (options) {
        'use strict';
        const $this = $(this);

        // settings
        const defaults = {
            // select row on click
            onClickRow: function (row, $element) {
                $element.updateRow($this);
                $this.enableKeys();
            },

            // edit row on double-click
            onDblClickRow: function (row, $element, field) {
                $element.updateRow($this);
                if(field !== 'action') {
                    $this.editRow($element);
                }
            },

            // update UI on post page load
            onPostBody: function (content) {
                const isData = content.length !== 0;
                if(isData) {
                    // select first row if none
                    if (!$this.getSelection()) {
                        $this.selectFirstRow();
                    }
                    // update
                    $this.updateCardView().highlight().updateHref(content);
                }
                $this.updateHistory().toggleClass('table-hover', isData);

                // update pagination
                $('.fixed-table-pagination .page-link').each(function (index, element) {
                    const $element = $(element);
                    $element.attr('title', $element.attr('aria-label'));
                });
                $('.fixed-table-pagination .page-item.disabled .page-link').tagName('span', ['href']);
                $('.fixed-table-pagination .page-item.active .page-link').tagName('span', ['href']);
            },

            onCustomViewPostBody: function (data) {
                const $view = $this.getCustomView();

                // data?
                if (data.length !== 0) {
                    // hide empty data message
                    $this.hideCustomViewMessage();

                    const params = $this.getParameters();
                    const selector = '.custom-view-actions:eq(%index%)';
                    const callback = $.isFunction (options.onRenderCustomView) ? options.onRenderCustomView: false;

                    $this.find('tbody tr .actions').each(function (index, element) {
                        // copy actions
                        const $rowActions = $(element).children();
                        const $cardActions = $view.find(selector.replace('%index%', index));
                        $rowActions.appendTo($cardActions);

                        if (callback) {
                            const row = data[index];
                            const $item = $cardActions.parents('.custom-item');
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

            // save parameters
            onToggle: function () {
                $this.saveParameters();
            }
        };
        const settings = $.extend(true, defaults, options);

        // initialize
        $this.bootstrapTable(settings);
        $this.enableKeys().highlight();

        // select row on right click
        $this.find('tbody').on('mousedown', 'tr', function (e) {
            if(e.button === 2) {
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
            if(e.button === 0) {
                $this.editRow();
            }
        });

        return $this;
    },

    /**
     * Gets the boostrap-table option.
     *
     * @return {Object} the options.
     */
    getOptions: function () {
        'use strict';
        return $(this).bootstrapTable('getOptions');
    },

    /**
     * Gets the boostrap-table parameters.
     *
     * @return {Object} the parameters.
     */
    getParameters: function () {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        let params = {
            'caller': options.caller,
            'sort': options.sortName,
            'order': options.sortOrder,
            'offset': (options.pageNumber - 1) * options.pageSize,
            'limit': options.pageSize,
            'view': $this.getDisplayMode()
        };

        // add search
        if(('' + options.searchText).length) {
            params.search = options.searchText;
        }

        // query parameters function?
        if($.isFunction (options.queryParams)) {
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
        'use strict';
        return '' + $(this).getOptions().searchText;
    },

    /**
     * Return if a search text is present.
     *
     * @return {boolean} true if a search text is present.
     */
    isSearchText: function () {
        'use strict';
        return $(this).getSearchText().length > 0;
    },

    /**
     * Return if the card view mode is displayed.
     *
     * @return {boolean} true if the card view mode is displayed.
     */
    isCardView: function () {
        'use strict';
        return $(this).getOptions().cardView;
    },

    /**
     * Return if the custom view mode is displayed.
     *
     * @return {boolean} true if the custom view mode is displayed.
     */
    isCustomView: function () {
        'use strict';
        const data = $(this).getBootstrapTable();
        return data && data.showCustomView;
    },

    /**
     * Returns if no loaded data (rows) is displayed.
     *
     * @return {boolean} true if not data is displayed.
     */
    isEmpty: function () {
        'use strict';
        return $(this).getData().length === 0;
    },

    /**
     * Get the loaded data (rows) of table at the moment that this method is
     * called.
     *
     * @return {array} the loaded data.
     */
    getData: function () {
        'use strict';
        return $(this).bootstrapTable('getData');
    },

    /**
     * Gets the bootstrap table.
     *
     * @return {object} the bootstrap table.
     */
    getBootstrapTable: function () {
        'use strict';
        return $(this).data('bootstrap.table');
    },

    /**
     * Gets the selected row.
     *
     * @return {jQuery} the selected row, if any; null otherwise.
     */
    getSelection: function () {
        'use strict';
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
        'use strict';
        const $row  = $(this).getSelection();
        return $row ? $row.index() : -1;
    },

    /**
     * Gets the custom view container.
     *
     * @return {JQuery} the custom view container, if displayed, null otherwise.
     */
    getCustomView: function () {
        'use strict';
        const $this = $(this);
        if ($this.isCustomView()) {
            return $this.parents('.bootstrap-table').find('.fixed-table-custom-view');
        }
        return null;
    },

    /**
     * Save parameters to the session.
     *
     * @return {jQuery} this instance for chaining.
     */
    saveParameters: function () {
        'use strict';
        const $this = $(this);
        const url = $this.getOptions().saveUrl;
        if(url) {
            $.post(url, $this.getParameters());
        }
        return $this;
    },

    /**
     * Update the href attribute of the actions.
     *
     * @param {array}
     *            rows - the rendered data.
     * @return {jQuery} this instance for chaining.
     */
    updateHref: function (rows) {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        const params = $this.getParameters();
        const callback = $.isFunction (options.onRenderAction) ? options.onRenderAction : false;

        $this.find('tbody tr .dropdown-item-path').each(function () {
            const $link = $(this);
            const $row = $link.parents('tr');
            const row = rows[$row.index()];
            $link.updateLink(row, params);
            if(callback) {
                callback($this, row, $row, $link);
            }

        });

        // actions row callback
        if($.isFunction (options.onUpdateHref)) {
            $this.find('tbody tr').each(function () {
                const $paths = $(this).find('.dropdown-item-path');
                if($paths.length) {
                    options.onUpdateHref($this, $paths);
                }
            });
        }
        return $this;
    },

    /**
     * Update this card view UI.
     *
     * @return {jQuery} this instance for chaining.
     */
    updateCardView: function () {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        if(!options.cardView) {
            return $this;
        }

        const $body = $this.find('tbody');
        const callback = $.isFunction (options.onRenderCardView) ? options.onRenderCardView : false;
        const data = callback ? $this.getData() : null;
        const columns = options.columns[0].filter((c) => c.visible && c.cardVisible);
        $body.find('tr').each(function () {
            const $row = $(this);
            const $views = $row.find('.card-views:first');

            // move actions (if any) to a new column
            const $actions = $views.find('.card-view-value:last:has(button)');
            if($actions.length) {
                const $td = $('<td/>', {
                    class: 'actions d-print-none rowlink-skip'
                });
                $actions.removeAttr('class').appendTo($td);
                $td.appendTo($row).on('click', function () {
                    $row.updateRow($this);
                });
                $views.find('.card-view:last').remove();
            }

            // update class
            $views.find('.card-view-value').each(function (index, element) {
                if(columns[index].cardClass) {
                    $(element).addClass(columns[index].cardClass);
                }
            });

            // callback
            if(callback) {
                const row = data[$row.index()];
                callback($this, row, $row);
            }
        });

        // update classes
        $body.find('.undefined').removeClass('undefined');
        $body.find('.card-view-title, .card-view-value').addClass('user-select-none');
        $body.find('.card-view-title').addClass('text-muted');

        return $this;
    },

    /**
     * Refresh/reload the remote server data.
     *
     * @param {object}
     *            options - the optional options.
     * @return {jQuery} this instance for chaining.
     */
    refresh: function (options) {
        'use strict';
        return $(this).bootstrapTable('refresh', options || {});
    },

    /**
     * Reset the search text.
     *
     * @param {string}
     *            text - the optional search text.
     * @return {jQuery} this instance for chaining.
     */
    resetSearch: function (text) {
        'use strict';
        return $(this).bootstrapTable('resetSearch', text || '');
    },

    /**
     * Refresh the table options.
     *
     * @param {Object}
     *            options - the options to refresh.
     * @return {jQuery} this instance for chaining.
     */
    refreshOptions: function (options) {
        'use strict';
        return $(this).bootstrapTable('refreshOptions', options || {});
    },

    /**
     * Toggle the card/table view.
     *
     * @return {jQuery} this instance for chaining.
     */
    toggleView: function () {
        'use strict';
        return $(this).bootstrapTable('toggleView');
    },

    /**
     * Toggles the view between the table and the custom view.
     *
     * @return {jQuery} this instance for chaining.
     */
    toggleCustomView: function () {
        'use strict';
        return $(this).bootstrapTable('toggleCustomView');
    },

    /**
     * Toggles the display mode.
     *
     * @param {string}
     *            mode the display mode to set ('table', 'card' or 'custom').
     * @return {jQuery} this instance for chaining.
     */
    setDisplayMode: function (mode) {
        'use strict';
        const $this = $(this);
        switch (mode) {
        case 'custom':
            if (!$this.isCustomView()) {
                $this.toggleCustomView();
            }
            break;
        case 'card':
            if (!$this.isCardView()) {
                $this.toggleView();
            }
            if ($this.isCustomView()) {
                $this.toggleCustomView();
            }
            break;
        default: // table
            if ($this.isCardView()) {
                $this.toggleView();
            }
            if ($this.isCustomView()) {
                $this.toggleCustomView();
            }
            break;
        }
        return $this;
    },

    /**
     * Gets the display mode.
     *
     * @return {string} the display mode ('table', 'card' or 'custom').
     */
    getDisplayMode : function () {
        'use strict';
        const $this = $(this);
        if ($this.isCustomView()) {
            return 'custom';
        } else if ($this.isCardView()) {
            return 'card';
        } else {
            return 'table';
        }
    },

    /**
     * Highlight matching text.
     *
     * @return {jQuery} this instance for chaining.
     */
    highlight: function () {
        'use strict';
        const $this = $(this);
        const text = $this.getSearchText();
        if(text.length > 0) {
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
     * @param {boolean}
     *            selectLast - true to select the last row.
     * @return {boolean} true if the previous page is displayed.
     */
    showPreviousPage: function (selectLast) {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        if(options.pageNumber > 1) {
            if(selectLast || false) {
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
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        if(options.pageNumber < options.totalPages) {
            $this.bootstrapTable('nextPage');
            return true;
        }
        return false;
    },

    /**
     * Select the first row.
     *
     * @return {boolean} true if the first row is selected.
     */
    selectFirstRow: function () {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelection();
        const $first = $this.find('tbody tr:first');
        if($first.length && $first !== $row) {
            return $first.updateRow($this);
        }
        return false;
    },

    /**
     * Select the last row.
     *
     * @return {boolean} true if the first last is selected.
     */
    selectLastRow: function () {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelection();
        const $last = $this.find('tbody tr:last');
        if($last.length && $last !== $row) {
            return $last.updateRow($this);
        }
        return false;
    },

    /**
     * Select the previous row.
     *
     * @return {boolean} true if the previous row is selected.
     */
    selectPreviousRow: function () {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelection();
        const $prev = $row.prev('tr');
        if($row.length && $prev.length) {
            return $prev.updateRow($this);
        }
        // previous page
        return $this.showPreviousPage(true);
    },

    /**
     * Select the next row.
     *
     * @return {boolean} true if the next row is selected.
     */
    selectNextRow: function () {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelection();
        const $next = $row.next('tr');
        if($row.length && $next.length) {
            return $next.updateRow($this);
        }
        // next page
        return $this.showNextPage();
    },

    /**
     * Finds an action for the given selector
     *
     * @param {string}
     *            actionSelector - the action selector.
     * @return{JQuery} the action, if found; null otherwise.
     */
    findAction: function (actionSelector) {
        'use strict';
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
        'use strict';
        const $link = $(this).findAction('a.btn-default');
        if($link) {
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
        'use strict';
        const $link = $(this).findAction('a.btn-delete');
        if($link) {
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
        'use strict';
        const $this = $(this);

        // get or create the key handler
        let keyHandler = $this.data('keys.handler');
        if(!keyHandler) {
            keyHandler = function (e) {
                if((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                    return;
                }

                switch(e.keyCode) {
                    case 13:  // enter (edit action on selected row)
                        if($this.editRow()) {
                            e.preventDefault();
                        }
                        break;
                    case 33: // page up (previous page)
                        if($this.showPreviousPage(false)) {
                            e.preventDefault();
                        }
                        break;
                    case 34: // page down (next page)
                        if($this.showNextPage()) {
                            e.preventDefault();
                        }
                        break;
                    case 35: // end (last row of the current page)
                        if($this.selectLastRow()) {
                            e.preventDefault();
                        }
                        break;
                    case 36: // home (first row of the current page)
                        if($this.selectFirstRow()) {
                            e.preventDefault();
                        }
                        break;
                    case 37: // left arrow (previous row of the current page)
                    case 38: // up arrow
                        if($this.selectPreviousRow()) {
                            e.preventDefault();
                        }
                        break;
                    case 39: // right arrow (next row of the current page)
                    case 40: // down arrow
                        if($this.selectNextRow()) {
                            e.preventDefault();
                        }
                        break;
                    case 46: // delete (delete action of the selected row)
                        if($this.deleteRow()) {
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
     * @return {jQuery} this instance for chaining.
     */
    disableKeys: function () {
        'use strict';
        const $this = $(this);
        const keyHandler = $this.data('keys.handler');
        if(keyHandler) {
            $(document).off('keydown.bs.table', keyHandler);
        }
        return $this;
    },

    /**
     * Update the history state.
     */
    updateHistory: function () {
        'use strict';
        const $this = $(this);
        const params = $this.getParameters();
        delete params.caller;

        let url = '';
        for (const [key, value] of Object.entries(params)) {
            url += url.match('[?]') ? '&' : '?';
            url += key + '=' + encodeURIComponent(value);
        }
        window.history.pushState({}, '', url);
        return $this;
    },

    /**
     * Hide the empty data message in custom view.
     */
    hideCustomViewMessage: function () {
        'use strict';
        const $view = $(this).getCustomView();
        if ($view) {
            $view.find('.no-records-found').remove();
        }
    },

    /**
     * Show the empty data message in custom view.
     */
    showCustomViewMessage: function () {
        'use strict';
        const $this = $(this);
        const $view = $this.getCustomView();
        if ($view) {
            $('<p/>', {
                class:'no-records-found text-center border-top p-2 mb-1',
                text: $this.getOptions().formatNoMatches()
            }).appendTo($view);
        }
    }
});
