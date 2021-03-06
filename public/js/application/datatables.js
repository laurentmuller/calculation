/**! compression tag for ftp-deployment */

/* globals URLSearchParams, MenuBuilder, enableKeys, disableKeys */

/**
 * -------------- jQuery extensions --------------
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
 * Creates the context menu items.
 *
 * @returns {Object} the context menu items.
 */
$.fn.getContextMenuItems = function () {
    'use strict';

    const builder = new MenuBuilder();

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

    const margin = parseFloat($('#data-table').attr('min_margin'));
    const value = parseFloat(cellData.replace(/[^\d\.\-]/g, '')) / 100.0;
    if (!isNaN(margin) && !isNaN(value) && value < margin) {
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

    const color = rowData.find((value) => value.match(/^#([0-9a-f]{6}|[0-9a-f]{3})$/i));
    if (!$.isUndefined(color)) {
        $(td).attr('style', 'border-left-color: ' + color + ' !important;');
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
 * User send message button callback.
 *
 * @param {DataTables.Api}
 *            row - the selected row.
 */
$.fn.userSendMessage = function (row) {
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
$.fn.userSwitch= function (row) {
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
        _switch_user: row.data()[2] // eslint-disable-line
    };
    const newHref = $this.data('path') + '?' + $.param(params);
    $this.prev().removeClass('d-none');
    return $this.attr('href', newHref).removeClass('d-none');
};

/**
 * Gets the parameters for the given identifier.
 *
 * @param {int}
 *            id - the row identifier or 0 if none.
 * @returns {Object} the parameters.
 */
$.fn.dataTable.Api.register('getParameters()', function (id) {
    'use strict';

    // row?
    if (id === 0) {
        return {};
    }

    // parameters
    const info = this.page.info();
    const params = {
        page: info.page,
        pagelength: info.length,
        caller: window.location.href.split('?')[0],
        id: id
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

    const searches = this.columns().search();
    searches.each(function (value, index) {
        if (value && value.length) {
            if (!params.search) {
                params.search = [];
            }
            params.search.push({
                index: index,
                value: value
            });
        }
    });

    return params;
});

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
    const id = disabled ? 0 : row.id;

    // get parameters
    const params = this.getParameters(id);

    // update buttons
    $('a[data-path]').each(function () {
        // update
        const $this = $(this);
        $this.toggleDisabled(disabled).updateHref(params);

        // callback?
        if (!disabled) {
            const callback = $this.data('callback');
            if (callback) {
                $this[callback](row);
            }
        }
    });

    // special case for the add button
    $('.btn-table-add').toggleDisabled(false);

    return this;
});

/**
 * -------------- Application specific --------------
 */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // table
    const $table = $('#data-table');

    // tooltip
    $table.tooltip({
        selector: '.has-tooltip',
        customClass: 'tooltip-danger overall-datatable'
    });

    // columns
    const columns = $table.getColumns();

    // loaded?
    let deferLoading = null;
    const total = parseInt($table.data('total'), 10);
    const filtered = parseInt($table.data('filtered'), 10);
    if (total !== 0) {
        deferLoading = [filtered, total];
    }

    // parameters
    const defaultLength = parseInt($table.data('pagelength') || 15, 10);
    const params = new URLSearchParams(window.location.search);
    const paging = total > 10;
    const id = params.getIntOrDefault('id', 0);
    const page = params.getIntOrDefault('page', 0);
    const pagelength = params.getIntOrDefault('pagelength', defaultLength);
    const query = params.getOrDefault('query', null);

    // order
    let order = $table.getDefaultOrder(columns);
    const ordercolumn = params.getOrDefault('ordercolumn', null);
    const orderdir = params.getOrDefault('orderdir', null);
    if (ordercolumn !== null && orderdir !== null) {
        order = [[ordercolumn, orderdir]];
    }

    // columns search
    let searchCols = (new Array(columns.length)).fill(null);
    for (var i = 0, len = columns.length; i < len; i++) {
        const indexKey = 'search[' + i + '][index]';
        const valueKey = 'search[' + i + '][value]';
        const index = params.getOrDefault(indexKey, null);
        const value = params.getOrDefault(valueKey, null);
        if (index !== null && value !== null) {
            searchCols[index] = {
                "search": value
            };
        }
    }

    // options
    const options = {
        deferLoading: deferLoading,

        paging: paging,
        pageLength: pagelength === -1 ? filtered : pagelength,
        displayStart: pagelength === -1 ? 0 : page * pagelength,

        order: order,
        columns: columns,
        searchCols: searchCols,

        rowId: function (data) {
            return parseInt(data[0], 10);
        },

        search: {
            search: query
        },
    };

    // initialize
    const table = $table.initDataTable(options).initEvents(id);

    // update
    $('#table_search').val(query);
    $('#table_length').val(pagelength);

    // select on right click
    $('#data-table tbody').on('mousedown', 'tr', function (e) {
        if (e.button === 2) {
            const index = table.row(this).index();
            table.cell(index, '0:visIdx').focus();
        }
    });

    // select on drop-down actions
    $('#data-table tbody').on('mousedown', '.dropdown', function () {
        const $row = $(this).closest('tr');
        const index = table.row($row).index();
        table.cell(index, '0:visIdx').focus();
    });

    // context menu
    const selector = '.dataTable .table-primary td:not(.d-print-none)';
    const show = function () {
        $('.dropdown-menu.show').removeClass('show');
        disableKeys();
    };
    const hide = function () {
        enableKeys();
    };
    $table.initContextMenu(selector, show, hide);

    // drop-down menu
    $('#other_actions_button').handleKeys();
    $('#other_actions').handleKeys('show.bs.dropdown', 'hide.bs.dropdown');
    $('#data-table tbody').on('show.bs.dropdown', 'td.actions .dropdown', function () {
        const $this = $(this);
        const items = $this.closest('tr').getContextMenuItems();

        // convert
        const $menus = [];
        for (const [key, value] of Object.entries(items)) {
            if (key.startsWith('separator_')) {
                const $separator = $('<div></div>', {
                    'class': 'dropdown-divider'
                });
                $menus.push($separator);

            } else if (key.startsWith('title_')) {
                const $title = $('<h6></h6>', {
                    'class': 'dropdown-header',
                    'text': value.text
                });
                $menus.push($title);

            } else if (value.link){
                const $action = $('<a></a>', {
                    'class': 'dropdown-item',
                    'text': value.name,
                    'href': '#'
                });
                $action.on('click', function (e) {
                    e.stopPropagation();
                    value.link.get(0).click();
                });

                if (value.icon) {
                    const $icon= $('<i></i>', {
                        'class': value.icon + ' mr-1',
                        'aria-hidden': 'true'
                    });
                    $icon.prependTo($action);

                }
                $menus.push($action);
            }
        }

        // replace
        const $menu = $this.find('.dropdown-menu');
        $menu.empty().append($menus);
    });

    // update row link if applicable
    if ($table.attr('row-link').toBool()) {
        // get path
        let path = $('.btn-table-show').data('path');
        if ($table.attr('edit-action').toBool()) {
            path = $('.btn-table-edit').data('path') || path;
        }
        if (path) {
            table.on('draw', function () {
                // run over rows
                table.rows().every(function () {
                    // create href attribute
                    const id = this.id();
                    const $row =  $(this.node());
                    const $cell = $row.find('td:first-child');
                    const text = $cell.text().trim();
                    const $image = $cell.find('img');
                    const params = table.getParameters(id);
                    const href = path.replace('0', id) + '?' + $.param(params);

                    // create link and replace content
                    const $a = $('<a/>', {
                        'href': href,
                        'text': text
                    });
                    if ($image.length) {
                        $image.prependTo($a);
                    }
                    $cell.html($a);
                });
            });
        }
    }
}(jQuery));
