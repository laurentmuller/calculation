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
function formatActions(value, row) { // jshint ignore:line
    'use strict';

    const substr = '$1' + value;
    const regex = /(\/|\bid=)(\d+)/;
    const $actions = $('#dropdown-actions').clone().removeClass('d-none');

    $actions.find('.dropdown-item-path').each(function () {
        const $link = $(this);
        const source = $link.attr('href');
        const target = source.replace(regex, substr);
        $link.attr('href', target);
    });
    
    return $actions.html();
}

/**
 * Style for the calculation identifier column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {object} the cell style.
 */
function styleCalculationState(value, row) { // jshint ignore:line
    'use strict';
    return {
        css: {
          'border-left-color': row['state.color'] + ' !important'
        }
    };
}

/**
 * Checks if the overall margin of the calculation is below the minimum margin.
 * 
 * @param {number}
 *            value - the margin value.
 * @param {object}
 *            row - the record data.
 * @returns {boolean} true if below.
 */
function isCalcultionMarginBelow(value, row) {
    'use strict';
    const overallMargin = parseFloat(row.overallMargin) / 100.0;
    const minMargin = parseFloat($('#table-edit').data('min-margin'));
    return !isNaN(overallMargin) && !isNaN(minMargin) && overallMargin < minMargin;
}

/**
 * Style for the calculation margin column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {object} the cell style.
 */
function styleCalculationMargin(value, row) { // jshint ignore:line
    'use strict';
    if (isCalcultionMarginBelow(value, row)) {
        return {
            'classes': 'text-percent text-danger cursor-pointer'
        };
    }
    return {};
}

/**
 * Gets the given element as HTML.
 * 
 * @param {jQuery}
 *            $element - the element to get HTML for.
 * @returns {string} the HTML text.
 */
function toHtml($element) {
    'use strict';
    return $('<div/>').append($element).html();
}

/**
 * Formatter for the calculation margin column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {string} the rendered cell.
 */
function formatCalculationMargin(value, row) { // jshint ignore:line
    'use strict';
    if (isCalcultionMarginBelow(value, row)) {
        const title = $('#table-edit').data('min-margin-text').replace('%margin%', value);
        const $content = $('<span/>', {
            'text': value.replace('&nbsp;', ' '),
           'class': 'has-tooltip',
           'data-title': title,
           'data-html': 'true'
        });
        return toHtml($content);
    }
    return value;
}

/**
 * Style for the log created date column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {object} the cell style.
 */
function styleLogCreatedAt(value, row) { // jshint ignore:line
    'use strict';
    let color;
    switch (row.level.toLowerCase()) {
    case 'debug':
        color = 'secondary';
        break;
    case 'warning':
        color = 'warning';
        break;
    case 'error':
    case 'critical':
    case 'alert':
    case 'emergency':
        color = 'danger';
        break;
    case 'info':
    case 'notice':
    default:
        color = 'info';
        break;
    }
     return {
         css: {
             'border-left-color': 'var(--' + color + ') !important'
         }
     };
}

/**
 * Style for the calculation state column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {object} the cell style.
 */
function styleStateColor(value, row) { // jshint ignore:line
    'use strict';
    return {
        css: {
          'border-left-color': row.color + ' !important'
        }
    };
}

/**
 * Formatter for the calculation state calculations column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {string} the rendered cell.
 */
function formatStateCalculations(value, row) { // jshint ignore:line
    'use strict';
    const count = Number.parseInt(value, 10);
    if (!Number.isNaN(count) && count > 0) {
        const $table = $('#table-edit');
        const title = $table.data('calculationTitle');
        const path = $table.data('calculationPath').replace('0', row.id);
        const $content = $('<a/>', {
            'href': path,
            'title': title,
            'text': value
        });
        return toHtml($content);
    }    
    return value;
}

/**
 * Formatter for the category products column.
 * 
 * @param {number}
 *            value - the field value.
 * @param {object}
 *            row - the record data.
 * 
 * @returns {string} the rendered cell.
 */
function formatCategoryProducts(value, row) { // jshint ignore:line
    'use strict';
    const count = Number.parseInt(value, 10);
    if (!Number.isNaN(count) && count > 0) {
        const $table = $('#table-edit');
        const title = $table.data('productTitle');
        const path = $table.data('productPath').replace('0', row.id);
        const $content = $('<a/>', {
            'href': path,
            'title': title,
            'text': value
        });
        return toHtml($content);
    }    
    return value;
}

/**
 * Formatter for the user image column.
 * 
 * @param {string}
 *            value - the image source.
 * @param {object}
 *            row - the record data.
 *            
 * @returns {string} the rendered cell.
 */
function formatUserImage(value, row) { // jshint ignore:line
    'use strict';
    if (value) {
        const $content = $('<img/>', {            
            'src': value,
            'alt': row.username,
            'title': $('#table-edit').data('image-title')            
        });
        return toHtml($content);
    }
    return value;
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
        const defaults = {
            // select row on click
            onClickRow: function(row, $element) {
                $element.updateRow($this);
                $this.enableKeys();
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
                    // update
                    $this.updateCardView().highlight().updateHref(content);
                    $('.card-footer').stop().fadeIn(250);
                } else {
                    $('.card-footer').stop().fadeOut(250);
                }
                $('.page-link').each(function (index, element) {
                    const $element = $(element);
                    $element.attr('title', $element.attr('aria-label'));
                });
            },
            
            // update UI and save parameters
            onToggle: function (cardView) {
                const options = $this.getOptions();
                const $button = $('.bootstrap-table button[name="toggle"]');
                const text = cardView ? options.formatToggleOff() : options.formatToggleOn();
                $button.attr('aria-label', text).attr('title', text);
                $this.saveParameters();
            }
        }; 
        const settings = $.extend(true, defaults, options);
        
        // initialize
        $this.bootstrapTable(settings).enableKeys().highlight();
        
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
     * Gets the search text.
     * 
     * @return {string} the search text.
     */
    getSearchText: function() {
        'use strict';
        return $(this).getOptions().searchText;
    },
    
    /**
     * Return if a search text is present.
     * 
     * @return {boolean} true if a search text is present.
     */
    isSearchText: function() {
        'use strict';
        const text = $(this).getSearchText();
        return text && text.length > 0;
    },
    
    /**
     * Get the loaded data of table at the moment that this method is called.
     * 
     * @return {array} the loaded data.
     */
    getData: function() {
        'use strict';
        return $(this).bootstrapTable('getData');
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
     * Update the href attribute of the actions.
     * 
     * @param {array}
     *            rows - the rendered data.
     * @return {jQuery} this instance for chaining.
     */
    updateHref: function (rows) {
        'use strict';
        
        const $this = $(this);
        const regex = /\bid=\d+/;
        const params = $this.getParameters();
        const options = $this.getOptions();
        const callback = $.isFunction(options.onRenderAction) ? options.onRenderAction : false;
        
        $this.find('.dropdown-item-path').each(function () {
            const $link = $(this);
            const $row = $link.parents('tr');
            const row = rows[$row.index()];
            const values = $link.attr('href').split('?');
            if (values.length > 1 && values[1].match(regex)) {
                params.id = row.action;
            } else {
                delete params.id;
            }
            const href = values[0] + '?' + $.param(params);
            $link.attr('href', href);
            if (callback) {
                callback($this, row, $row, $link);
            }
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
        
        const callback = $.isFunction(options.onRenderCardView) ? options.onRenderCardView : false;
        const data = callback ? $this.getData() : null;
        const columns = options.columns[0].filter(c => c.visible && c.cardVisible);
        
        $this.find('tbody tr').each(function () {
            const $row = $(this);            
            const $views = $row.find('.card-views:first');
            
            // move actions (if any) to a new column
            const $actions = $views.find('.card-view-value:last:has(button)');
            if ($actions.length) {
                const $td = $('<td/>', {
                    class: 'actions d-print-none rowlink-skip'
                });
                $actions.removeAttr('class').appendTo($td);
                $td.appendTo($row).on('click', function() {
                    $row.updateRow($this);
                });                
                $views.find('.card-view:last').remove();
            }
            
            // update bold style
            $views.find('.card-view-value').each(function(index, element) {
                if (columns[index].cardBold) {
                    $(element).addClass('font-weight-bold');
                }
            });
            
            // callback
            if (callback) {
                const row = data[$row.index()];
                options.onRenderCardView($this, row, $row);                
            }
        });
        $this.find('.undefined').removeClass('undefined');
        $this.find('.card-view-title').addClass('text-muted');
        // $this.find('.card-view-title').remove();
                
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
        if ($this.isSearchText()) {
            const options = {
                element: 'span',
                className: 'text-success',
                ignorePunctuation: ["'", ",", "."]
            };
            const $rows = $this.find('tbody tr');
            $rows.mark($this.getSearchText(), options);
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
