/**! compression tag for ftp-deployment */

/* globals getStateColor */

/**
 * Returns if the current row is rendered for the connected user
 * 
 * @param $table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @returns {boolean} true if connected user
 */
function isConnectedUser($table, row) {
    'use strict';
    const currentId = Number.parseInt(row.id, 10);
    const connectedId = Number.parseInt($table.data('user-id'), 10);
    return Number.isNaN(currentId) || Number.isNaN(connectedId) || currentId === connectedId;
}

/**
 * Update the user action.
 * 
 * @param $table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @param $element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateUserAction($table, row, $element, $action) {
    'use strict';
    if (isConnectedUser($table, row)) {
        $action.remove();
    }
}

/**
 * Update the switch user action.
 * 
 * @param $table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @param $element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateUserSwitchAction($table, row, $element, $action) {
    'use strict';
    if (isConnectedUser($table, row)) {
        $action.prev('.dropdown-divider').remove();
        $action.remove();
    } else {
        const source = $action.attr('href').split('?')[0];
        const params = {'_switch_user': row.username};
        const href = source + '?' + $.param(params);
        $action.attr('href', href);
    }    
}

/**
 * Update the search action.
 * 
 * @param $table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @param $element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateSearchAction($table, row, $element, $action) {
    'use strict';
    if ($action.is('.btn-show') && !row.show_granted) {
        $action.remove();
    } else if ($action.is('.btn-edit') && !row.edit_granted) {
        $action.remove();
    } else if ($action.is('.btn-delete') && !row.delete_granted) {
        $action.remove();
    } else {
        const id =  row.id;
        const type = row.type.toLowerCase();
        const href = $action.attr('href').replace('_type_', type).replace('_id_', id);
        $action.attr('href', href);    
    }
}

/**
 * Update the export calculation action.
 * 
 * @param $table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @param $element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateCalculationPdfAction($table, row, $element, $action) {
    'use strict';
    const href = $action.attr('href').split('?')[0];
    $action.attr('href', href);
}

/**
 * Update the task compute action.
 * 
 * @param $table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @param $element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateTaskComputeAction($table, row, $element, $action) {
    'use strict';
    const items = Number.parseInt(row.items, 10);
    if (Number.isNaN(items) || items === 0) {
        $action.prev('.dropdown-divider').remove();
        $action.remove();
    }
}

/**
 * jQuery extensions.
 */
$.fn.extend({
    
    getDataId: function() {
        'use strict';
        const $this = $(this);
        return $this.data('value') || null;
    },
    
    setDataId(id, $selection) {
        'use strict';
        const $this = $(this);
        const $items = $this.next('.dropdown-menu').find('.dropdown-item').removeClass('active');
        $this.data('value', id);
        if (id) {
            $selection.addClass('active');
            return $this.text($selection.text());
        }
        $items.first().addClass('active');
        return $this.text($this.data('default'));
    },
    
    initDropdown: function() {
        'use strict';
        const $this = $(this);
        const $menu = $this.next('.dropdown-menu');
        const $items = $menu.find('.dropdown-item');
        if ($items.length) {
            $items.on('click', function() {
                const $item = $(this);
                const newValue = $item.getDataId();
                const oldValue = $this.getDataId();
                if (newValue !== oldValue) {
                    $this.setDataId(newValue || '', $item).trigger('input');
                }
                $this.focus();
            });
            $this.parent().on('shown.bs.dropdown', function () {
                $menu.find('.active').focus();
            });
        }
        return $this;
    }
});

/**
 * Ready function
 */
(function ($) {
    'use strict';
    
    const $table = $('#table-edit');
    const $inputs = $('.dropdown-toggle.dropdown-input');
    
    // initialize table
    const options = {
        queryParams: function (params) {
            $inputs.each(function () {
                const $this = $(this);
                const value = $this.getDataId();
                if (value) {
                    params[$this.attr('id')] = value;    
                }                
            }); 
            return params;
        },

        onRenderCardView: function($table, row, $element) {
            const color =  getStateColor(row);
            if (color) {
                const $cell = $element.find('td:first');
                const style = 'border-left-color: ' + color;
                $cell.addClass('text-border').attr('style', style);
            }
        },
        
        onRenderAction: function($table, row, $element, $action) {
            if ($action.is('.btn-user-switch')) {
                updateUserSwitchAction($table, row, $element, $action);
            } else if ($action.is('.btn-user-message, .btn-user-delete')) {
                updateUserAction($table, row, $element, $action);
            } else if ($action.is('.btn-calculation-pdf')) {
                updateCalculationPdfAction($table, row, $element, $action);
            } else if ($action.is('.btn-search')) {
                updateSearchAction($table, row, $element, $action);
            } else if ($action.is('.btn-task-compute')) {
                updateTaskComputeAction($table, row, $element, $action);
            }
        },
    };
    $table.initBootstrapTable(options);
    
    // handle drop-down input buttons
    $inputs.each(function () {
        $(this).initDropdown().on('input', function() {
             $table.refresh();
        });
     });
    
    // handle toggle button
    $('#toggle').on('click', function () {
        $table.toggleView();
    });
    
    // handle clear search button
    $('#clear_search').on('click', function () {
        const isSearchText = $table.isSearchText();
        const isQueryParams = !$.isEmptyObject(options.queryParams({}));
        
        // clear drop-down
        $inputs.each(function () {
            $(this).setDataId(null);
        });
        if (isSearchText) {
            $table.resetSearch();
        } else if (isQueryParams)  {
            $table.refresh();
        }
        $('input.search-input').focus();
    });

    // handle keys enablement
    $('.search-input, .btn, .dropdown-item, .page-link, .rowlink-skip').on('focus', function () {
        $table.disableKeys();
    }).on('blur', function () {
        $table.enableKeys();
    });

    // initialize context menu
    const rowSelector = $table.getOptions().rowSelector;    
    const ctxSelector =  rowSelector + ' td:not(.d-print-none)';
    const show = function () {
        $('.dropdown-menu.show').removeClass('show');
    };
    $table.initContextMenu(ctxSelector, show);

    // initialize tooltip for calculations
    if ($table.data('min-margin-text')) {
        $table.customTooltip({
            type: 'danger',
            trigger: 'hover',
            selector: '.has-tooltip',
        });
    }

    // update UI
    $('.fixed-table-pagination').appendTo('.card-footer');
    $('.fixed-table-toolbar').appendTo('.col-search');
    $('.fixed-table-toolbar input.search-input').attr('type', 'text').addClass('form-control-sm').prependTo('.input-group-search');
    $('.fixed-table-toolbar .search').remove();
    $('.btn-group-search').appendTo('.fixed-table-toolbar');

    // focus
    if ($table.getData().length === 0) {
        $('input.search-input').focus();
    }
}(jQuery));
