/**! compression tag for ftp-deployment */

/* globals URLSearchParams, triggerClick, MenuBuilder */

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

    // special case for PDF list button
    // const pdfList = $('.btn-pdf-list');
    // if (pdfList.length) {
    // let href = pdfList.attr('href').split('?')[0];
    // const order = this.order();
    // if (order && order.length) {
    // const $params = new URLSearchParams('');
    // $params.set('index', order[0][0]);
    // $params.set('direction', order[0][1]);
    // href += $params.toQuery();
    // }
    // pdfList.attr('href', href);
    // console.log(href);
    // }

    return this;
});

/**
 * Binds events.
 * 
 * @param {integer}
 *            id - the selected row identifier (if any).
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('bindEvents()', function (id) {
    'use strict';

    const table = this;
    let lastPageCalled = false;

    // bind search and page length
    const $button = $('.btn-clear');
    $('#table_search').initSearchInput(searchCallback, table, $button);
    $('#table_length').initTableLength(table);

    // bind table body rows
    $('#data-table tbody').on('dblclick', 'tr', function (e) {
        return editOrShow(e);
    });

    // bind datatable key down
    $(document).on('keydown.keyTable', function (e) {
        if (e.ctrlKey) {
            switch (e.keyCode) {
            case 35: // end => last page
                const endInfo = table.page.info();
                if (endInfo.pages > 0 && endInfo.page < endInfo.pages - 1) {
                    e.stopPropagation();
                    lastPageCalled = true;
                    table.page('last').draw('page');
                }
                break;
            case 36: // home => first page
                const homeInfo = table.page.info();
                if (homeInfo.pages > 0 && homeInfo.page > 0) {
                    e.stopPropagation();
                    table.page('first').draw('page');
                }
                break;
            }
        }
    });

    // bind table events
    table.one('init', function () {
        // selection?
        let found = false;
        if (id !== 0) {
            const row = table.row('[id=' + id + ']');
            if (row && row.length) {
                table.cell(row.index(), '0:visIdx').focus();
                found = true;
            }
        }
        if (!found) {
            $('#table_search').selectFocus();
        }
    }).on('draw', function () {
        // select row
        if (table.rows().length) {
            const selector = lastPageCalled ? ':last' : ':first';
            const row = table.row(selector);
            table.cell(row.index(), '0:visIdx').focus();
        }
        table.updateButtons().updateTitles();
        lastPageCalled = false;
        $(table).focus();

    }).on('key-focus', function (e, datatable, cell) {
        // select row
        const row = datatable.row(cell.index().row);
        $(row.node()).addClass('selection').scrollInViewport(0, 60);
        datatable.updateButtons();

    }).on('key-blur', function (e, datatable, cell) {
        // unselect row
        const row = datatable.row(cell.index().row);
        $(row.node()).removeClass('selection');
        datatable.updateButtons();

    }).on('key', function (e, datatable, key, cell, event) {
        switch (key) {
        case 13: // enter
            return editOrShow(event);
        case 46: // delete
            return triggerClick(event, '.btn-table-delete');
        }
    });

    return table;
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
 * Edit or show the selected item.
 * 
 * @param {Object}
 *            e - the source event.
 * @returns {boolean} true if handle.
 */
function editOrShow(e) {
    'use strict';

    // edit by default?
    if ($('#data-table').attr('edit-action').toBool()) {
        return triggerClick(e, '.btn-table-edit') || triggerClick(e, '.btn-table-show');
    } else {
        return triggerClick(e, '.btn-table-show') || triggerClick(e, '.btn-table-edit');
    }
}

/**
 * Creates the context menu items.
 * 
 * @returns {Object} the menu items.
 */
function getContextMenuItems() {
    'use strict';

    // buttons
    const builder = new MenuBuilder();
    $('.card-header a.btn[data-path]').each(function () {
        const $this = $(this);
        if ($this.isSelectable()) {
            builder.addEntry($this, $this.data('icon'));
        }
    });

    builder.addSeparator();

    // drop-down menu
    $('.card-header .dropdown .dropdown-menu').children().each(function () {
        const $this = $(this);
        if ($this.hasClass('dropdown-divider')) {
            builder.addSeparator();
        } else if ($this.data('path') && $this.isSelectable()) {
            builder.addEntry($this, $this.data('icon'));
        }
    });

    return builder.getEntries();
}

/**
 * Initialize the context menu for the table rows.
 */
function initContextMenu() {
    'use strict';

    const callback = function () { // $triggerElement, e
        return {
            autoHide: true,
            classNames: {
                hover: 'bg-primary text-white',
            },
            callback: function (key, options, e) {
                const item = options.items[key];
                const $link = item.link;
                if ($link) {
                    e.stopPropagation();
                    $link.get(0).click();
                    return true;
                }
            },
            events: {
                show: function () {
                    // disable keys
                    const table = $('#data-table').DataTable();
                    if (table) {
                        table.keys.disable();
                    }

                    // hide drop-down menus
                    $('.dropdown-menu.show').removeClass('show');
                },
                hide: function () {
                    // enable keys
                    const table = $('#data-table').DataTable();
                    if (table) {
                        table.keys.enable();
                    }
                }
            },
            items: getContextMenuItems()
        };
    };

    $.contextMenu({
        selector: '.dataTable .selection',
        build: callback
    });
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

    // remote
    const ajax = $table.data('ajax');
    const language = $table.data('lang');

    // loaded?
    let deferLoading = null;
    const total = $table.data('total');
    const filtered = $table.data('filtered');
    if (total !== 0) {
        deferLoading = [filtered, total];
    }

    // remove
    if (!$table.data('debug')) {
        $table.removeDataAttributes();
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
        ajax: ajax,
        deferLoading: deferLoading,

        paging: paging,
        pageLength: pagelength === -1 ? filtered : pagelength,
        displayStart: pagelength === -1 ? 0 : page * pagelength,

        order: order,
        columns: columns,

        language: {
            url: language
        },

        rowId: function (data) {
            return parseInt(data[0], 10);
        },

        search: {
            search: query
        }
    };

    // debug
    if ($table.data('debug')) {
        console.log(JSON.stringify(options, '', '    '));
    }

    // initialize
    $table.initDataTable(options).bindEvents(id);

    // update
    $('#table_search').val(query);
    $('#table_length').val(pagelength);

    // context menu
    initContextMenu();
});