/**! compression tag for ftp-deployment */

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
    return currentId === connectedId;
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
        return $this.text($this.data('default') || $this.attr('title'));
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
            const color = $element.find('td:first .card-view-title:first').css('border-left-color');
            if (color) {
                const $cell = $element.find('td:first');
                const style = 'border-left-color: ' + color + ' !important;';
                $cell.addClass('text-id').attr('style', style);
            }
        },
        
        onRenderAction: function($table, row, $element, $action) {
            if ($action.is('.btn-user-switch')) {
                updateUserSwitchAction($table, row, $element, $action);
            } else if ($action.is('.btn-user-message, .btn-user-delete')) {
                updateUserAction($table, row, $element, $action);
            } else if ($action.is('.btn-calculation-pdf')) {
                updateCalculationPdfAction($table, row, $element, $action);
            }
        }
    };
    $table.initBootstrapTable(options);
    
    // handle drop-down input buttons
    $inputs.each(function () {
        $(this).initDropdown().on('input', function() {
            const settings = {
              query: options.queryParams({})
            };
            $table.refresh(settings);
        });  
    });
    
    // handle clear search
    $('[name ="clearSearch"]').on('click', function () {
        const isSearch = $table.isSearchText();
        const params = options.queryParams({});
        if (Object.keys(params).length && !isSearch) {
            // reset
            const options = {query: {}};
            for (let key in params) {
                if (Object.prototype.hasOwnProperty.call(params, key)) {
                    options.query[key] = '';
                    $('#' + key).setDataId('');    
                }                
            }
            $table.refresh(options);
        }
        $('.search-input').focus();
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
    $('input.search-input').attr('type', 'text');
    $('.fixed-table-toolbar').addClass('d-print-none');
    $('.fixed-table-pagination').appendTo('.card-footer');
    $('button[name ="toggle"]').insertBefore('#button_other_actions');
    $('[name ="clearSearch"]').toggleClass('btn-secondary btn-outline-secondary');
    $('[name ="clearSearch"] .fa.fa-trash').toggleClass('fa fa-trash fas fa-eraser');
    $('#toolbar .btn-dropdown').insertAfter('.search-input').removeClass('btn-dropdown');

}(jQuery));
