/**! compression tag for ftp-deployment */

/* globals URLSearchParams, MenuBuilder, initContextMenu */

/**
 * -------------- JQuery extensions --------------
 */

/**
 * Update the link href.
 * 
 * @param {Object}
 *            params - the parameters
 */
$.fn.updateHref = function (params) {
    'use strict';

    let href = '#';
    const $this = $(this);
    if (!$this.hasClass('disabled')) {
        const path = $this.data('path').replace('0', params.id);
        href = path + '?' + $.param(params);
    }

    // replace
    return $this.attr('href', href);
};

/**
 * -------------- DataTables Extensions --------------
 */

/**
 * Handler to create margin tooltip for calculation.
 * 
 * @param {node}
 *            td - The TD node that has been created.
 * @param {any}
 *            cellData - the cell data.
 */
$.fn.dataTable.renderTooltip = function (td, cellData) {
    'use strict';

    var margin = parseFloat($('#data-table').attr('min_margin'));
    var value = parseFloat(cellData) / 100.0;
    if (margin && value && value < margin) {
        const title = $('#data-table').attr('min_margin_text').replace('%margin%', cellData);
        $(td).addClass('text-danger has-tooltip').attr('data-title', title).attr('data-html', true);
    }
};

/**
 * Handler to render the state color.
 * 
 * @param {node}
 *            td - The TD node that has been created.
 * @param {any}
 *            cellData - the cell data.
 * @param {any}
 *            rowData - the data source object / array for the whole row.
 */
$.fn.dataTable.renderStateColor = function (td, cellData, rowData) {
    'use strict';

    const color = rowData[rowData.length - 1];
    if (color) {
        const style = 'inset 5px 0 ' + color;
        $(td).css('box-shadow', style);
    }
};

/**
 * Handler to create left border for log entry.
 * 
 * @param {node}
 *            td - The TD node that has been created.
 * @param {any}
 *            cellData - the cell data.
 * @param {any}
 *            rowData - the data source object / array for the whole row.
 */
$.fn.dataTable.renderLog = function (td, cellData, rowData) {
    'use strict';

    const level = rowData[3].toLowerCase();
    $(td).addClass(level);
};

/**
 * User message button callback.
 * 
 * @param {DataTables.Api}
 *            row - the selected row.
 */
$.fn.user_message = function (row) {
    'use strict';

    // check if selection are equals.
    const $this = $(this);
    const userId = parseInt($this.data('id'), 10);
    const selectionId = parseInt(row.id(), 10);
    if (userId === selectionId) {
        return $this.attr('href', '#').addClass('d-none');
    }

    return $this.removeClass('d-none');
};

/**
 * User switch button callback.
 * 
 * @param {DataTables.Api}
 *            row - the selected row.
 */
$.fn.user_switch = function (row) {
    'use strict';

    // check if selection are equals.
    const $this = $(this);
    const userId = parseInt($this.data('id'), 10);
    const selectionId = parseInt(row.id(), 10);
    if (userId === selectionId) {
        $this.prev().addClass('d-none');
        return $this.attr('href', '#').addClass('d-none');
    }

    // update link
    const params = {
        _switch_user: row.data()[2]
    };
    const newHref = $this.data('path') + '?' + $.param(params);
    $this.prev().removeClass('d-none');
    return $this.attr('href', newHref).removeClass('d-none');
};

/**
 * Update buttons link and enablement.
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('updateButtons()', function () {
    'use strict';

    // get selection
    const row = this.getSelectedRow();
    const disabled = row === null;

    // parameters
    let params = {};
    if (!disabled) {
        // parameters
        const info = this.page.info();
        params = {
            page: info.page,
            pagelength: info.length,
            caller: window.location.href.split('?')[0],
            id: row.id()
        };
        const order = this.order();
        if (order.length) {
            params.ordercolumn = order[0][0];
            params.orderdir = order[0][1];
        }
        const query = this.search();
        if (query && query.length) {
            params.query = query;
        }
    }

    // update buttons
    $('a[data-path]').each(function () {
        // update
        const $this = $(this);
        $this.updateClass('disabled', disabled).updateHref(params);

        // callback?
        if (!disabled) {
            const callback = $this.data('callback');
            if (callback) {
                $this[callback](row);
            }
        }
    });

    // special case for the add button
    $('.btn-table-add').removeClass('disabled');

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

    const oldSearch = table.search() || '';
    const newSearch = $('#table_search').val().trim();
    if (oldSearch !== newSearch) {
        table.search(newSearch).draw();
    }
}

/**
 * Creates the context menu items.
 * 
 * @returns {Object} the context menu items.
 */
function getContextMenuItems() { // jshint ignore:line
    'use strict';

    const builder = new MenuBuilder();

    // buttons
    $('.card-header a.btn[data-path]').each(function () {
        const $this = $(this);
        if ($this.isSelectable()) {
            builder.addItem($this);
        }
        if ($this.data('separator')) {
            builder.addSeparator();
        }
    });

    builder.addSeparator();

    // drop-down menu
    $('.card-header .dropdown-menu').children().each(function () {
        const $this = $(this);
        if ($this.hasClass('dropdown-divider')) {
            builder.addSeparator();
        } else if ($this.data('path') && $this.isSelectable()) {
            builder.addItem($this);
        }
    });

    return builder.getItems();
}


/**
 * Ready function
 */
$(function () {
    'use strict';

    // table
    const $table = $('#data-table');

    // tooltip
    $table.customTooltip({
        trigger: 'hover',
        selector: '.has-tooltip',
        className: 'tooltip-danger overall-datatable'
    });

    // columns
    const columns = $table.getColumns();

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
    const paging = total > 15;
    const id = params.getOrDefault('id', 0);
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

        paging: paging,
        pageLength: pagelength === -1 ? filtered : pagelength,
        displayStart: pagelength === -1 ? 0 : page * pagelength,

        order: order,
        columns: columns,

        rowId: function (data) {
            return parseInt(data[0], 10);
        },

        search: {
            search: query
        }
    };

    // initialize
    $table.initDataTable(options).initEvents(id, searchCallback);

    // update
    $('#table_search').val(query);
    $('#table_length').val(pagelength);

    // context menu
    initContextMenu();

    // drop-down menu
    $('#other_actions_button').handleKeys();
    $('#other_actions').handleKeys('show.bs.dropdown', 'hide.bs.dropdown');
});