/**! compression tag for ftp-deployment */

/* globals URLSearchParams, MenuBuilder, enableKeys, disableKeys, clearSearch */

/**
 * -------------- jQuery extensions --------------
 */
$.fn.extend({
    /**
     * Update the entity selection.
     * 
     * @return {jQuery} The jQuery element for chaining.
     */
    updateEntity: function () {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            $('#entity').val($this.data('id'));
            $('#button-entity').text($this.text());
            $('.dropdown-entity').removeClass('active');
            $this.addClass('active');
        }
        return $this;
    }
});

/**
 * Override clear search
 */
const noConflictSearch = clearSearch;
clearSearch = function ($element, table, callback) { // jshint ignore:line
    'use strict';

    const $entity = $('#entity');
    if ($entity.val() !== '') {
        table.search('');
        table.column(1).search('');
        $('#table_search').addClass('is-invalid');
        $('#minimum').removeClass('d-none');
        $('.dropdown-entity:first').updateEntity();
        if (!noConflictSearch($element, table, callback)) {
            table.draw();
            return false;
        }
        return true;
    } else {
        return noConflictSearch($element, table, callback);
    }
};

/**
 * Update a button link.
 * 
 * @param {boolean}
 *            granted - true if the action is granted (autorization).
 * @param {object}
 *            params - the parameters.
 * 
 * @returns {jQuery} the button for chaining.
 */
$.fn.updateHref = function (granted, params) {
    'use strict';

    const $that = $(this);

    // type and granted?
    if (params === null || params.type === null || !granted) {
        return $that.attr('href', '#').toggleDisabled(true);
    }

    // build URL
    const path = $that.data('path').replace('_type_', params.type).replace('_id_', params.id);
    const href = path + '?' + $.param(params);

    // update
    return $that.attr('href', href).toggleDisabled(false);
};

/**
 * Creates the context menu items.
 * 
 * @returns {Object} the context menu items.
 */
$.fn.getContextMenuItems = function () {
    'use strict';

    const $elements = $('.card-header a.btn[data-path]');
    return (new MenuBuilder()).fill($elements).getItems();
};

/**
 * -------------- DataTables Extensions --------------
 */

/**
 * Render the entity name cell.
 * 
 * @param {any}
 *            data - the cell data.
 * @param {string}
 *            type - the type call data requested.
 * @param {any}
 *            row - the full data source for the row.
 */
$.fn.dataTable.renderEntityName = function (data, type, row) {
    'use strict';

    let icon;
    switch (row.type) {
    case 'Calculation':
        icon = 'calculator fas';
        break;
    case 'CalculationState':
        icon = 'flag far';
        break;
    case 'Task':
        icon = 'tasks fas';
        break;
    case 'Category':
        icon = 'folder far';
        break;
    case 'Group':
        icon = 'code-branch fas';
        break;
    case 'Product':
        icon = 'file-alt far';
        break;
    case 'Customer':
        icon = 'address-card far';
        break;
    default:
        icon = 'file far';
        break;
    }

    return '<i class="fa-fw fa-' + icon + '" aria-hidden="true"></i>&nbsp;' + data;
};

/**
 * Gets the parameters for the given row.
 * 
 * @param {row}
 *            row - the selected row.
 * @returns {Object} the parameters.
 */
$.fn.dataTable.Api.register('getParameters()', function (row) {
    'use strict';

    // row?
    if (row === null) {
        return {};
    }

    // build parameters
    const data = row.data();
    const info = this.page.info();
    const type = data.type.toLowerCase();
    const params = {
        id: data.id,
        type: type,
        page: info.page,
        pagelength: info.length,
        query: this.search(),
        caller: window.location.href.split('?')[0]
    };
    if ($('#entity').val()) {
        params.search = [{
            index: 1,
            value: $('#entity').val()
        }];
    }
    return params;
});

/**
 * Update buttons link and enablement.
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('updateButtons()', function () {
    'use strict';

    // get rows and parameters
    const row = this.getSelectedRow();
    const params = this.getParameters(row);

    // get rights
    let showGranted = false;
    let editGranted = false;
    let deleteGranted = false;
    if (row !== null) {
        const data = row.data();
        showGranted = data.show_granted;
        editGranted = data.edit_granted;
        deleteGranted = data.delete_granted;
    }

    // update buttons
    $('.btn-table-show').updateHref(showGranted, params);
    $('.btn-table-edit').updateHref(editGranted, params);
    $('.btn-table-delete').updateHref(deleteGranted, params);

    return this;
});

/**
 * -------------- Application specific --------------
 */

/**
 * Search callback.
 * 
 * @param {DataTables.Api}
 *            table - the table to update.
 */
function searchCallback(table) {
    'use strict';

    const $input = $('#table_search');
    const oldSearch = table.search() || '';
    const newSearch = $input.val().trim();
    if (oldSearch !== newSearch) {
        $input.toggleClass('is-invalid', newSearch.length < 2);
        $('#minimum').toggleClass('d-none', newSearch.length > 1);
        if (newSearch.length > 1) {
            table.search(newSearch).draw();
        }
    }
}

/**
 * Document ready function
 */
(function ($) {
    'use strict';

    // table
    const $table = $('#data-table');

    // columns
    const columns = $table.getColumns(true);

    // loaded?
    let deferLoading = null;
    const total = $table.data('total');
    const filtered = $table.data('filtered');
    if (total !== 0) {
        deferLoading = [filtered, total];
    }

    // parameters
    const defaultLength = $table.data('pagelength') || 15;
    const params = new URLSearchParams(window.location.search);
    const id = params.getOrDefault('id', 0);
    const type = params.getOrDefault('type', null);
    const page = params.getOrDefault('page', 0);
    const pagelength = params.getOrDefault('pagelength', defaultLength);
    const query = params.getOrDefault('query', null);

    // order
    let order = $table.getDefaultOrder(columns);
    const ordercolumn = params.getOrDefault('ordercolumn', null);
    const orderdir = params.getOrDefault('orderdir', null);
    if (ordercolumn !== null && orderdir !== null) {
        order = [[ordercolumn, orderdir]];
    }

    // options
    const options = {
        deferLoading: deferLoading,

        pageLength: pagelength,
        displayStart: page * pagelength,

        order: order,
        ordering: order.length > 0,
        columns: columns,

        rowId: function (data) {
            return data.type.toLowerCase() + '.' + data.id;
        },

        search: {
            search: query
        }
    };

    // initialize
    const key = type && id ? type + '.' + id : false;
    const table = $table.initDataTable(options).initEvents(key, searchCallback);

    // update
    $('#table_search').val(query);
    $('#table_length').val(pagelength);
    if (query === null || query.length < 2) {
        $('#table_search').addClass('is-invalid');
        $('#minimum').removeClass('d-none');
    }

    // select on right click
    $('#data-table tbody').on('mousedown', 'tr', function (e) {
        if (e.button === 2) {
            const table = $('#data-table').DataTable(); // eslint-disable-line
            const index = table.row(this).index();
            table.cell(index, '0:visIdx').focus();
        }
    });

    // add row link if applicable
    if ($table.attr('row-link').toBool()) {
        // path
        const editAction = $table.attr('edit-action').toBool();
        const showPath = $('.btn-table-show').data('path');
        const editPath = $('.btn-table-edit').data('path');

        table.on('draw', function () {
            // run over rows
            table.rows().every(function () {
                // get parameters
                let path = false;
                const row = this;
                const data = row.data();
                const params = table.getParameters(row);

                // replace path
                if (data.edit_granted && editAction) {
                    path = editPath.replace('_type_', params.type).replace('_id_', params.id);
                } else if (data.show_granted) {
                    path = showPath.replace('_type_', params.type).replace('_id_', params.id);
                }

                // create link and replace content
                if (path) {
                    const href = path + '?' + $.param(params);
                    const $row = $(this.node());
                    const $cell = $row.find('td:first-child');
                    const text = $cell.text().trim();
                    const $image = $cell.find('i');
                    const $a = $('<a/>', {
                        'href': href,
                        'text': text
                    });
                    if ($image.length) {
                        $image.prependTo($a);
                    }
                    $cell.html($a);
                }
            });
        });
    }

    // context menu
    const selector = '.dataTable .table-primary';
    const show = function () {
        $('.dropdown-menu.show').removeClass('show');
        disableKeys();
    };
    const hide = function () {
        enableKeys();
    };
    $table.initContextMenu(selector, show, hide);

    // focus
    if (!$('#table_search').val().length) {
        $('#table_search').focus();
    }

    // initialize entity search column
    const $entity = $('#entity');
    const $button = $('#button-entity');
    table.initSearchColumn($entity, 1, $button);

    // handle drop-down entity
    $('.dropdown-entity').on('click', function () {
        $(this).updateEntity();
        // force update
        if (table.column(1).search() === $entity.val()) {
            table.column(1).search(' ');
        }
        $entity.trigger('input');
    }).handleKeys();

    // focus entity menu
    $('#dropdown-menu-entity').on('shown.bs.dropdown', function () {
        $('.dropdown-entity.active').focus();
    });

    // select entity
    const entity = $entity.val() || params.get('search[0][value]');
    if (entity) {
        const selector = '.dropdown-entity[data-id="' + type + '"]';
        $(selector).updateEntity();
    }

}(jQuery));
