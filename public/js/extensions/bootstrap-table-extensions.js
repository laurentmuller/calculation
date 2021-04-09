/**! compression tag for ftp-deployment */

/* globals Toaster */

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
     * 
     * @return {boolean} this function returns always true.
     */
    updateRow: function($table) {
        'use strict';
        const $row = $(this);
        const options = $table.getOptions();
        if ($row.hasClass(options.rowClass)) {
            return true;
        }
        $table.find(options.rowSelector).removeClass(options.rowClass);
        if(!$row.hasClass('no-records-found')) {
            $row.addClass(options.rowClass).scrollInViewport();
            $table.trigger('update-row.bs.table', this);
        }
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
    tagName: function(newTag, excludeAttributes){
        'use strict';
        newTag = "<" + newTag + ">";
        excludeAttributes = excludeAttributes || [];
        
        return $(this).each(function(index, element){
            const $element = $(element);
            const $newTag = $(newTag, {
                html: $element.html()
            });
            $.each(element.attributes, function(i, attribute) {
                if (!excludeAttributes.includes(attribute.name)) {
                    $newTag.attr(attribute.name, attribute.value);    
                }
            });
            $element.replaceWith($newTag);
        });
    },

    // -------------------------------
    // Table extension
    // -------------------------------

    /**
     * Initialize the table-boostrap.
     * 
     * @param {object}
     *            options - the options to merge with default.
     * 
     * @return {jQuery} this instance for chaining.
     */
    initBootstrapTable: function(options) {
        'use strict';
        const $this = $(this);

        // settings
        const defaults = {
            // select row on click
            onClickRow: function(row, $element) {
                $element.updateRow($this);
                $this.enableKeys();
            },

            // edit row on double-click
            onDblClickRow: function(row, $element, field) {
                $element.updateRow($this);
                if(field !== 'action') {
                    $this.editRow($element);
                }
            },

            // update UI on post page load
            onPostBody: function(content) {
                if(content.length !== 0) {
                    // select first row if none
                    if (!$this.getSelectRow()) {
                        $this.selectFirstRow();
                    }
                    // update
                    $this.updateCardView().highlight().updateHref(content);
                }
                $this.toggleClass('table-hover', content.length !== 0);
                
                // update pagination
                $('.fixed-table-pagination .page-link').each(function(index, element) {
                    const $element = $(element);
                    $element.attr('title', $element.attr('aria-label'));
                });
                $('.fixed-table-pagination .page-item.disabled .page-link').tagName('span', ['href']);
                $('.fixed-table-pagination .page-item.active .page-link').tagName('span', ['href']);
            },

            // update UI and save parameters
            onToggle: function(cardView) {
                $this.updateCardViewButton(cardView);
                $this.saveParameters();
            },

            // show message
            onLoadError: function(status, jqXHR) {
                const title = $('.card-title').text();
                const message = jqXHR.responseJSON.message || $this.data('errorMessage');
                Toaster.danger(message, title, $("#flashbags").data());
            },
        };
        const settings = $.extend(true, defaults, options);

        // initialize
        $this.bootstrapTable(settings);
        $this.updateCardViewButton($this.isCardView());
        $this.enableKeys().highlight();

        // select row on right click
        $this.find('tbody').on('mousedown', 'tr', function(e) {
            if(e.button === 2) {
                $(this).updateRow($this);
            }
        });
        return $this;
    },

    /**
     * Gets the boostrap-table option.
     * 
     * @return {Object} the options.
     */
    getOptions: function() {
        'use strict';
        return $(this).bootstrapTable('getOptions');
    },

    /**
     * Gets the boostrap-table parameters.
     * 
     * @return {Object} the parameters.
     */
    getParameters: function() {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        let params = {
            'caller': options.caller,
            'sort': options.sortName,
            'order': options.sortOrder,
            'offset': (options.pageNumber - 1) * options.pageSize,
            'limit': options.pageSize,
            'card': options.cardView
        };
        // add search
        if(('' + options.searchText).length) {
            params.search = options.searchText;
        }
        // query parameters function?
        if($.isFunction(options.queryParams)) {
            params = $.extend(params, options.queryParams(params));
        }
        return params;
    },

    /**
     * Gets the search text.
     * 
     * @return {string} the search text.
     */
    getSearchText: function() {
        'use strict';
        return '' + $(this).getOptions().searchText;
    },

    /**
     * Return if a search text is present.
     * 
     * @return {boolean} true if a search text is present.
     */
    isSearchText: function() {
        'use strict';
        return $(this).getSearchText().length > 0;
    },

    /**
     * Return if the card view mode is displayed.
     * 
     * @return {boolean} true if the card view mode is displayed.
     */
    isCardView: function() {
        'use strict';
        return $(this).getOptions().cardView;
    },

    /**
     * Returns if no loaded data (rows) is displayed.
     * 
     * @return {boolean} true if not data is displayed.
     */
    isEmpty: function() {
        'use strict';
        return $(this).getData().length === 0;
    },
    
    /**
     * Get the loaded data (rows) of table at the moment that this method is
     * called.
     * 
     * @return {array} the loaded data.
     */
    getData: function() {
        'use strict';
        return $(this).bootstrapTable('getData');
    },
    
    /**
     * Gets the select row.
     * 
     * @return {jQuery} the selected row, if any; null otherwise.
     */
    getSelectRow: function() {
        'use strict';
        const $this = $(this);
        const $row = $this.find($this.getOptions().rowSelector);
        return $row.length ? $row : null;
    },

    /**
     * Save parameters to the session.
     * 
     * @return {jQuery} this instance for chaining.
     */
    saveParameters: function() {
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
    updateHref: function(rows) {
        'use strict';
        const $this = $(this);
        const regex = /\bid=\d+/;
        const params = $this.getParameters();
        
        // action callback
        const options = $this.getOptions();
        const callback = $.isFunction(options.onRenderAction) ? options.onRenderAction : false;
        $this.find('tbody tr .dropdown-item-path').each(function() {
            const $link = $(this);
            const $row = $link.parents('tr');
            const row = rows[$row.index()];
            const values = $link.attr('href').split('?');
            if(values.length > 1 && values[1].match(regex)) {
                params.id = row.action;
            } else {
                delete params.id;
            }
            const href = values[0] + '?' + $.param(params);
            $link.attr('href', href);
            if(callback) {
                callback($this, row, $row, $link);
            }
        });
        
        // actions row callback
        if($.isFunction(options.onUpdateHref)) {
            $this.find('tbody tr').each(function() {
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
    updateCardView: function() {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        if(!options.cardView) {
            return $this;
        }
        
        const $body = $this.find('tbody');
        const callback = $.isFunction(options.onRenderCardView) ? options.onRenderCardView : false;
        const data = callback ? $this.getData() : null;
        const columns = options.columns[0].filter((c) => c.visible && c.cardVisible);
        $body.find('tr').each(function() {
            const $row = $(this);
            const $views = $row.find('.card-views:first');

            // move actions (if any) to a new column
            const $actions = $views.find('.card-view-value:last:has(button)');
            if($actions.length) {
                const $td = $('<td/>', {
                    class: 'actions d-print-none rowlink-skip'
                });
                $actions.removeAttr('class').appendTo($td);
                $td.appendTo($row).on('click', function() {
                    $row.updateRow($this);
                });
                $views.find('.card-view:last').remove();
            }

            // update class
            $views.find('.card-view-value').each(function(index, element) {
                if(columns[index].cardClass) {
                    $(element).addClass(columns[index].cardClass);
                }
            });

            // callback
            if(callback) {
                const row = data[$row.index()];
                options.onRenderCardView($this, row, $row);
            }
        });
        
        // update classes
        $body.find('.card-view-title').toggleClass('text-muted user-select-none undefined');
        $body.find('.card-view-value').toggleClass('user-select-none undefined');
        
        return $this;
    },

    /**
     * Update the card view toggle button
     * 
     * @param {boolean}
     *            cardView - the card view state of the table.
     * @return {jQuery} this instance for chaining.
     */
    updateCardViewButton: function(cardView) {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        const $button = $(options.toggleSelector);
        const text = cardView ? options.formatToggleOff() : options.formatToggleOn();
        const icon = cardView ? 'fa-fw fas fa-toggle-on' : 'fa-fw fas fa-toggle-off';
        $button.attr('aria-label', text).attr('title', text).find('i').attr('class', icon);
        return $this;
    },

    /**
     * Refresh/reload the remote server data.
     * 
     * @param {object}
     *            options - the optional options.
     * 
     * @return {jQuery} this instance for chaining.
     */
    refresh: function(options) {
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
    resetSearch: function(text) {
        'use strict';
        return $(this).bootstrapTable('resetSearch', text || '');
    },

    /**
     * Toggle the card/table view.
     * 
     * @return {jQuery} this instance for chaining.
     */
    toggleView: function() {
        'use strict';
        return $(this).bootstrapTable('toggleView');
    },

    /**
     * Highlight matching text.
     * 
     * @return {jQuery} this instance for chaining.
     */
    highlight: function() {
        'use strict';
        const $this = $(this);
        const text = $this.getSearchText();
        if(text.length > 0) {
            const options = {
                element: 'span',
                className: 'text-success',
                separateWordSearch: false,
                ignorePunctuation: ["'", ",", "."] // ":;.,-–—‒_(){}[]!'\"+=".split("")
            };
            const $rows = $this.find('tbody td:not(.rowlink-skip)');
            $rows.mark(text, options);
        }
        return $this;
    },

    /**
     * Shows the previous page.
     * 
     * @param {boolean}
     *            selectLast - true to select the last row.
     * 
     * @return {boolean} true if the previous page is displayed.
     */
    showPreviousPage: function(selectLast) {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        if(options.pageNumber > 1) {
            if(selectLast || false) {
                $this.one('post-body.bs.table', function() {
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
    showNextPage: function() {
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
    selectFirstRow: function() {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelectRow();
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
    selectLastRow: function() {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelectRow();
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
    selectPreviousRow: function() {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelectRow();
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
    selectNextRow: function() {
        'use strict';
        const $this = $(this);
        const $row = $this.getSelectRow();
        const $next = $row.next('tr');
        if($row.length && $next.length) {
            return $next.updateRow($this);
        }
        // next page
        return $this.showNextPage();
    },

    /**
     * Call the edit action for the selected row (if any).
     * 
     * @return {boolean} true if the action is called.
     */
    editRow: function() {
        'use strict';
        const $this = $(this);
        const rowSelector = $this.getOptions().rowSelector;
        const $link = $this.find(rowSelector + ' a.btn-default');
        if($link.length) {
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
    deleteRow: function() {
        'use strict';
        const $this = $(this);
        const rowSelector = $this.getOptions().rowSelector;
        const $link = $this.find(rowSelector + ' a.btn-delete');
        if($link.length) {
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
    enableKeys: function() {
        'use strict';
        const $this = $(this);
        
        // get or create the key handler
        let keyHandler = $this.data('keys.handler');
        if(!keyHandler) {
            keyHandler = function(e) {                
                if((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                    return;
                }
                switch(e.keyCode) {
                    case 13: // enter (edit action on selected row)
                        if($this.editRow()) {
                            e.preventDefault();
                        }
                        break;
                    case 33: // page up (previous page)
                        if($this.showPreviousPage()) {
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
                    case 38: // up arrow (previous row of the current page)
                        if($this.selectPreviousRow()) {
                            e.preventDefault();
                        }
                        break;
                    case 40: // down arrow (next row of the current page)
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
    disableKeys: function() {
        'use strict';
        const $this = $(this);
        const keyHandler = $this.data('keys.handler');
        if(keyHandler) {
            $(document).off('keydown.bs.table', keyHandler);
        }
        return $this;
    }
});
