/**! compression tag for ftp-deployment */

/* globals MenuBuilder */

/**
 * Formatter for the actions column.
 * 
 * @param {number}
 *            value - the field value (id).
 * @param {object}
 *            row - the row record data.
 * 
 * @returns {string} the rendered cell.
 */
function actionsFormatter(value, row) { // jshint ignore:line
    'use strict';

    const substr = '$1' + value;
    const regex = /(\/|\bid=)(\d+)/;
    const $actions = $('#dropdown-actions').clone().removeClass('d-none');

    $actions.find('.btn-path').each(function () {
        const $link = $(this);
        const source = $link.attr('href');
        const target = source.replace(regex, substr);
        $link.attr('href', target);
    });
    
    return $actions.html();
}

/**
 * Formatter for the calculation identifier column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {object} the cell style.
 */
function renderCalculationState(value, row) { // jshint ignore:line
    'use strict';
    return {
        css: {
          'border-left-color': row['state.color'] + ' !important'
        }
    };
}

/**
 * Formatter for the calculation margin column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {object} the cell style.
 */
function renderCalculationMargin(value, row) { // jshint ignore:line
    'use strict';
    const overallMargin = Number.parseFloat(row.overallMargin) / 100;
    const minMargin = Number.parseFloat($('#table-edit').data('min-margin'));
    if (overallMargin < minMargin) {
        return {
            'classes': 'text-percent text-danger cursor-pointer',
        };
    }
    return {};
}

/**
 * Gets the loading template.
 * 
 * @param {string}
 *            message - the loading message.
 * @returns {string} the loading template.
 */
function loadingTemplate(message) { // jshint ignore:line
    'use strict';
    return '<i class="fa fa-spinner fa-spin fa-fw fa-2x"></i>' + message;
}

/**
 * jQuery extension for Bootstrap tables, rows and cells.
 */
$.fn.extend({

    // -------------------------------
    // Cell extension
    // -------------------------------
    
    /**
     * Gets the context menu items for the selected cell.
     * 
     * @return {object} the context menu items.
     */
    getContextMenuItems: function () {
        'use strict';
        const $row = $(this).parents('tr');
        const $elements = $row.find('.dropdown-menu').children();
        const builder = new MenuBuilder();
        return builder.fill($elements).getItems();
    },
    
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
    updateRow: function ($table) {
        'use strict';
        const $row = $(this);
        const options = $table.getOptions();
        $table.find(options.rowSelector).removeClass(options.rowClass);
        $row.addClass(options.rowClass).scrollInViewport();
        return true;
    },

    // -------------------------------
    // Table extension
    // -------------------------------
    
    /**
     * Initialize the table-boostrap.
     * 
     * @param {object}
     *            options - the optional options to use.
     * 
     * @return {jQuery} this instance for chaining.
     */
    initBootstrapTable: function(options) {
        'use strict';
        const $this = $(this);
        
        // settings
        const settings = {
            // select row on click
            onClickRow: function(row, $element) {
                $element.updateRow($this);  
            },

            // edit row on double-click
            onDblClickRow: function(row, $element, field) {
                $element.updateRow($this); 
                if (field !== 'action') {
                    $this.editRow($element);
                }
            },
            
            // update UI on post page load
            onPostBody: function (content) {
                if (content.length) {
                    // select first row if none
                    const rowSelector = $this.getOptions().rowSelector;
                    if ($this.find(rowSelector).length === 0) {
                        $this.selectFirstRow();
                    }      
                    $this.highlight().updateHref(content);
                    $('.card-footer').stop().fadeIn(250);
                } else {
                    $('.card-footer').stop().fadeOut(250);
                }
                $('.page-link').each(function (index, element) {
                    const $element = $(element);
                    $element.attr('title', $element.attr('aria-label'));
                });
                $this.updateCardView();
            },
            
            // update UI on card view toggle
            onToggle: function (cardView) {
                const options = $this.getOptions();
                const $button = $('.bootstrap-table button[name="toggle"]');
                const text = cardView ? options.formatToggleOff() : options.formatToggleOn();
                $button.attr('aria-label', text).attr('title', text);
                $this.updateCardView().saveParameters();
            }
        }; 
     
        // initialize
        $this.bootstrapTable($.extend(true, settings, options));
        
        // enable keys and update UI
        $this.updateCardView().enableKeys().highlight();
     
        // select row on right click
        $this.find('tbody').on('mousedown', 'tr', function (e) {
            if (e.button === 2) {
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
            'search': options.searchText,
            'sort': options.sortName,
            'order': options.sortOrder,
            'offset': (options.pageNumber - 1) * options.pageSize,
            'limit': options.pageSize,
            'card': options.cardView
        };
        
        // query parameters function?
        if ($.isFunction(options.queryParams)) {
            params = $.extend(params, options.queryParams(params));
        }
        
        // remove empty search
        if (params.search === '') {
            delete params.search;
        }
        
        return params;
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
        if (url) {
            $.post(url, $this.getParameters());
        }
        return $this;
    },
    
    /**
     * Gets the search text.
     * 
     * @return {string} the search text.
     */
    getSearchText: function() {
        'use strict';
        return $(this).getOptions().searchText;
    },
    
    /**
     * Update the href attribute of the actions.
     * 
     * @param {array}
     *            content - the rendered data.
     * @return {jQuery} this instance for chaining.
     */
    updateHref: function (content) {
        'use strict';
        
        const $this = $(this);
        const regex = /\bid=\d+/;
        const params = $this.getParameters();
        
        $this.find('.btn-path').each(function () {
            const $link = $(this);
            const values = $link.attr('href').split('?');
            if (values.length > 1 && values[1].match(regex)) {
                params.id = content[$link.parents('tr').index()].action;
            } else {
                delete params.id;
            }
            const href = values[0] + '?' + $.param(params);
            $link.attr('href', href);
        });
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
        if (!options.cardView) {
            return $this;
        }
        
        const bold = options.cardViewBold;
        $this.find('tr').each(function () {
            const $row = $(this);            
            const $views = $row.find('.card-views:first');
            const $actions = $views.find('.card-view-value:last:has(button)');
            if ($actions.length) {
                const $td = $('<td/>', {
                    class: 'actions d-print-none rowlink-skip align-top'
                });
                $actions.removeAttr('class').appendTo($td);
                $td.appendTo($row).on('click', function() {
                    $row.updateRow($this);
                });                
                $views.find('.card-view:last').remove();
                if (bold) {
                    $views.find('.card-view-value:first').addClass('font-weight-bold'); 
                } 
            }
        });
        $this.find('.card-view-title').addClass('text-muted');
                
        return $this;
    },

    /**
     * Refresh/reload the remote server data.
     * 
     * @param {object}
     *            options - the optional options to use.
     * 
     * @return {jQuery} this instance for chaining.
     */
    refresh: function (options) {
        'use strict';
        const $this = $(this);
        $this.bootstrapTable('refresh', options || {});
        return $this;
    },

    /**
     * Highlight matching text.
     * 
     * @return {jQuery} this instance for chaining.
     */
    highlight: function () {
        'use strict';
        const $this = $(this);
        const searchText = $this.getSearchText();
        if (searchText && searchText.length) {
            const options = {
                element: 'span',
                className: 'text-success',
                ignorePunctuation: ["'", ","]
            };
            const $ctx = $this.find('tbody tr');
            $ctx.mark(searchText, options);
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
    showPreviousPage: function (selectLast) {
        'use strict';
        const $this = $(this);
        const options = $this.getOptions();
        if (options.pageNumber > 1) {
            if (selectLast || false) {
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
        if (options.pageNumber < options.totalPages) {
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
        const options = $this.getOptions();
        const $row = $this.find(options.rowSelector);
        const $first = $this.find('tbody tr:first');
        if ($first.length && $first !== $row) {
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
        const options = $this.getOptions();
        const $row = $this.find(options.rowSelector);
        const $last = $this.find('tbody tr:last');
        if ($last.length && $last !== $row) {
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
        const options = $this.getOptions();
        const $row = $this.find(options.rowSelector);
        const $prev = $row.prev('tr');
        if ($row.length && $prev.length) {
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
        const options = $this.getOptions();
        const $row = $this.find(options.rowSelector);
        const $next = $row.next('tr');
        if ($row.length && $next.length) {
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
        const options = $this.getOptions();
        const $link = $this.find(options.rowSelector + ' a.btn-default');
        if ($link.length) {
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
        const options = $this.getOptions();
        const $link = $this.find(options.rowSelector + ' a.btn-delete');
        if ($link.length) {
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
        
        // already created?
        let handler = $this.data('keys.handler');
        if (!handler) {
            handler = function (e) {
                if ((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                    return;
                }

                switch (e.keyCode) {
                 case 13: // enter (edit action on selected row)
                     if ($this.editRow()) {
                         e.preventDefault();
                     }
                 break;
                case 33: // page up (previous page)
                    if ($this.showPreviousPage()) {
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
                case 38: // up arrow (previous row of the current page)
                    if ($this.selectPreviousRow()) {
                        e.preventDefault();
                    }
                    break;

                case 40: // down arrow (next row of the current page)
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
            $this.data('keys.handler', handler);
        }
        
        // add handler
        $(document).off('keydown.bs.table', handler).on('keydown.bs.table', handler);
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
        const handler = $this.data('keys.handler');
        if (handler) {
            $(document).off('keydown.bs.table', handler);
        }
        return $this;
    }
});
