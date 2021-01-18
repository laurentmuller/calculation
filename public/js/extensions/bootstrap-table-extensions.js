/**! compression tag for ftp-deployment */

/* globals MenuBuilder */

/**
 * Formatter for actions column.
 * 
 * @param {number}
 *            value - the field value (id).
 * 
 * @returns {string} the rendered cell.
 */
function actionsFormatter(value) { // jshint ignore:line
    'use strict';
    const $actions = $('#actions').clone().removeClass('d-none');
    $actions.find('.btn-path').each(function () {
        const $link = $(this);
        const href = $link.attr('href').replace('0', value);
        $link.attr('href', href);
    });
    return $actions.html();
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
    initialize: function(options) {
        'use strict';
        const $this = $(this);
        
        // merge options
        const selector = $this.data('row-selector') || 'table-primary';
        const rowSelector = 'tr.' + selector.replaceAll(' ', '.');
        const settings = {
            selector: selector,
            rowSelector: rowSelector
        }; 
        
        // initialize
        $this.bootstrapTable($.extend(true, settings, options));
        
        // select row on click
        $this.on('click-row.bs.table', function (e, row, $element) {
            $element.updateRow();    
        });
        
        // edit row on cell double-click
        $this.on('dbl-click-cell.bs.table', function (e, field, value, row, $element) {
            if (!$element.hasClass('rowlink-skip')) {
                if ($this.editRow($element.closest('tr'))) {
                    e.preventDefault();
                }
            }
        });

        // update UI on post page load
        $this.on('post-body.bs.table', function (e, content) {
            if (content.length) {
                $this.highlight().selectFirstRow();
                $('.card-footer').stop().fadeIn(250);
            } else {
                $('.card-footer').stop().fadeOut(250);
            }
            $('.page-link').each(function (index, element) {
                const $this = $(element);
                $this.attr('title', $this.attr('aria-label'));
            });
            $this.updateCardView().updateHref();
        });
        
        // update UI on card view toggle
        $this.on('toggle.bs.table', function (e, cardView) {
            const options = $this.getOptions();
            const $button = $('.bootstrap-table button[name="toggle"]');
            const text = cardView ? options.formatToggleOff() : options.formatToggleOn();
            $button.attr('aria-label', text).attr('title', text);
            $this.updateCardView().saveParameters();
        });
        
        // select row on right click
        $this.find('tbody').on('mousedown', 'tr', function (e) {
            if (e.button === 2) {
                $(this).updateRow();
            }
        });
        
        // enable keys and update UI
        $this.updateCardView().enableKeys().highlight();
        
        // select first row if none
        if ($this.find(rowSelector).length === 0) {
            $this.selectFirstRow();
        }        
        
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
            'caller': $this.data('caller'),
            'search': options.searchText,
            'sort': options.sortName,
            'order': options.sortOrder,
            'offset': (options.pageNumber - 1) * options.pageSize,
            'limit': options.pageSize,
            'card': options.cardView
        };

        // function?
        if ($.isFunction(options.queryParams)) {
            params = options.queryParams(params);
        }
        
        return params;
    },
    
    /**
     * Save parameters to the session.
     */
    saveParameters: function() {
        'use strict';
        const $this = $(this);
        const url = $this.data('save-url');
        if (url) {
            $.post(url, $this.getParameters());
        }
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
     * Returs if the card view is displayed.
     * 
     * @return {boolean} true if card view is displayed; false if tabular view.
     */
    isCardView: function() {
        'use strict';
        return $(this).getOptions().cardView;
    },
    
    /**
     * Update the href attribute of the actions.
     * 
     * @return {jQuery} this instance for chaining.
     */
    updateHref: function () {
        'use strict';
        const $this = $(this);
        const params = $this.getParameters();
        $this.find('.btn-path').each(function () {
            const $link = $(this);
            const origin = $link.attr('href').split('?')[0];
            const href = origin + '?' + $.param(params);
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
        if (!$this.isCardView()) {
            return $this;
        }
        
        const bold = $this.data('card-view-bold');
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
                    $row.updateRow();
                });                
                $views.find('.card-view:last').remove();
                if (bold) {
                    $views.find('.card-view-value:first').addClass('font-weight-bold'); 
                } 
            }
            // $row.find('td:first').removeAttr('colspan');
            
        });
        // $this.find('.card-view-title').remove();
        // $this.find('.card-view-title').toggleClass('undefined text-muted');
        // $this.find('.card-view-value').toggleClass('undefined');
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
                //className: 'text-success font-weight-bold',
                className: 'bg-warning',
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
            return $first.updateRow();
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
            return $last.updateRow();
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
            return $prev.updateRow();
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
            return $next.updateRow();
        }

        // next page
        return $this.showNextPage();
    },

    /**
     * Edit the selected row by calling the edit action.
     * 
     * @return {boolean} true if action is called.
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
     * Delete the selected row by calling the delete action.
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
        
        // already registered?
        let handler = this.data('keys.handler');
        if (!handler) {
            handler = function (e) {
                if ((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                    return;
                }

                switch (e.keyCode) {
                 case 13: // enter (edit)
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

                case 35: // end (end of current page)
                    if ($this.selectLastRow()) {
                        e.preventDefault();
                    }
                    break;

                case 36: // home (start of current page)
                    if ($this.selectFirstRow()) {
                        e.preventDefault();
                    }
                    break;
                case 38: // up arrow (previous row or page)
                    if ($this.selectPreviousRow()) {
                        e.preventDefault();
                    }
                    break;

                case 40: // down arrow (next row or page)
                    if ($this.selectNextRow()) {
                        e.preventDefault();
                    }
                    break;
                    
                case 46: // delete (remove row)
                    if ($this.deleteRow()) {
                        e.preventDefault();
                    }
                    break;    
                }
            };
            this.data('keys.handler', handler);
        }
        
        // register
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
        const handler = this.data('keys.handler');
        if (handler) {
            $(document).off('keydown.bs.table', handler);
        }
        return $this;
    },

    // -------------------------------
    // Row extension
    // -------------------------------
    
    /**
     * Update the selected row.
     * 
     * @return {boolean} this function returns always true.
     */
    updateRow: function () {
        'use strict';
        const $row = $(this);
        const $table = $row.parents('table');
        const options = $table.getOptions();
        $table.find(options.rowSelector).removeClass(options.selector);
        $row.addClass(options.selector).scrollInViewport();
        return true;
    },

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
});
