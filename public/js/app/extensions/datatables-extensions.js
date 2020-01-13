/** ! compression tag for ftp-deployment */

/**
 * -------------- DataTables Extensions --------------
 */

/**
 * Add alert class to processing message
 */
$.extend($.fn.dataTable.ext.classes, {
    sProcessing: "dataTables_processing alert alert-primary"
});

/**
 * Gets the selected row.
 * 
 * @returns {DataTables.Api} the selected row, if any; null otherwise.
 */
$.fn.dataTable.Api.register('getSelectedRow()', function () {
    'use strict';

    const row = this.row('.selection');
    return row.length ? row : null;
});

/**
 * Select the first row (if any).
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('selectFirstRow()', function () {
    'use strict';

    if (this.rows().count()) {
        this.cell(0, '0:visIdx').focus();
    }
    return this;
});

/**
 * Select the last row (if any).
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('selectLastRow()', function () {
    'use strict';

    if (this.rows().count()) {
        this.cell(this.rows().count() - 1, '0:visIdx').focus();
    }
    return this;
});

/**
 * Update the column titles.
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('updateTitles()', function () {
    'use strict';

    this.columns().every(function () {
        if (this.visible()) {
            const $header = $(this.header());
            const title = $header.attr('aria-label').split(':');
            if (title.length === 2) {
                const text = $header.text().trim();
                $header.attr('title', title[1].trim().replace('{0}', text));
            } else {
                $header.removeAttr('title');
            }
        }
    });

    return this;
});

/**
 * Binds events.
 * 
 * @param {integer}
 *            id - the selected row identifier (if any).
 * @param {function}
 *            callback - the search callback to call.
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('initEvents()', function (id, searchCallback) {
    'use strict';

    const table = this;
    const $table = $(table.table().node());
    let lastPageCalled = false;

    // bind search and page length
    $('#table_search').initSearchInput(table, searchCallback, $('.btn-clear'));
    $('#table_length').initTableLength(table);

    // bind table body rows
    $table.on('dblclick', 'tbody > tr', function (e) {
        return editOrShow($table, e);
    });

    // bind datatable key down
    $(document).on('keydown.keyTable', function (e) {
        if (e.ctrlKey) {
            switch (e.keyCode) {
            case 35: // end => last page and last record
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
                    lastPageCalled = false;
                    table.page('first').draw('page');
                }
                break;
            }
        } else if (e.keyCode === 93) { // context-menu
            e.stopPropagation();
            $('.dropdown-menu.show').removeClass('show');
            $('.dataTable .selection').first().trigger("contextmenu");
        }
    });

    // bind table events
    table.one('init', function () {
        // select row (if any)
        if (id) {
            const row = table.row('[id=' + id + ']');
            if (row && row.length) {
                table.cell(row.index(), '0:visIdx').focus();
            }
        }

        // remove hiden search text
        $(":text[tabindex='0']").parent().remove();

    }).on('draw', function () {
        if (lastPageCalled) {
            table.selectLastRow();
            lastPageCalled = false;
        } else {
            table.selectFirstRow();
        }
        table.updateButtons().updateTitles();

    }).on('search.dt', function () {
        enableKeys();

    }).on('length.dt', function () {
        enableKeys();

    }).on('key-focus', function (e, datatable, cell) {
        // select row
        const row = datatable.row(cell.index().row);
        $(row.node()).addClass('selection').scrollInViewport(0, 60);
        table.updateButtons();

    }).on('key-blur', function (e, datatable, cell) {
        // unselect row
        const row = datatable.row(cell.index().row);
        $(row.node()).removeClass('selection');
        table.updateButtons();

    }).on('key', function (e, datatable, key, cell, event) {
        switch (key) {
        case 13: // enter
            return editOrShow($table, event);
        case 46: // delete
            return triggerClick(event, '.btn-table-delete');
        }
    });

    return table;
});

/**
 * Creates the table column definitions.
 * 
 * @param {boolean}
 *            useName - true to use the column name as column data.
 * 
 * @returns {Object} the column definitions.
 */
$.fn.getColumns = function (useName) {
    'use strict';

    let columns = [];
    const $that = $(this);
    const debug = $that.data('debug');

    $that.find('th').each(function () {
        const $element = $(this);
        const column = $element.data();

        // data
        if (useName) {
            column.data = column.name;
        }

        // order sequence
        if (column.visible && column.orderable && column.direction === 'desc') {
            column.orderSequence = ["desc", "asc"];
        }

        // created callback
        if (column.createdCell) {
            column.createdCell = $.fn.dataTable[column.createdCell];
        }

        // render callback
        if (column.render) {
            column.render = $.fn.dataTable[column.render];
        }

        columns.push(column);

        // remove
        if (!debug) {
            $element.removeDataAttributes();
        }
    });

    return columns;
};

/**
 * Gets the default table order.
 * 
 * @param {Object}
 *            columns - the column definitions.
 * @returns {array} the default table order, if any; an empty array otherwise.
 */
$.fn.getDefaultOrder = function (columns) {
    'use strict';

    // find first in table attributes
    const $table = $(this);
    const index = $table.data('ordercolumn');
    const direction = $table.data('orderdir');

    // check values
    if (!$.isUndefined(index) && !$.isUndefined(direction) && index !== '' && direction !== '') {
        return [[index, direction]];
    }

    // find default colmun
    for (let i = 0; i < columns.length; i++) {
        if (columns[i].isDefault && columns[i].orderable) {
            return [[i, columns[i].direction]];
        }
    }

    // find first sortable colmun
    for (let i = 0; i < columns.length; i++) {
        if (columns[i].visible && columns[i].orderable) {
            return [[i, columns[i].direction]];
        }
    }

    // none
    return [];
};

/**
 * Merge the default options within the given options and initialize the data table.
 * 
 * @param {Object}
 *            options - the options to merge with default values.
 * 
 * @return {jQuery} The JQuery element for chaining.
 */
$.fn.initDataTable = function (options) {
    'use strict';

    const defaultSettings = {
        // ajax
        serverSide: true,
        processing: true,

        // paging
        pagingType: 'full_numbers',
        conditionalPaging: {
            style: 'fade'
        },

        // order
        orderMulti: false,

        // class
        "stripeClasses": [],

        // keys
        keys: {
            // focus: ':eq(0)',
            focus: ':eq(0:visIdx)',
            blurable: false,
            clipboard: false,
            className: 'selection',
            keys: [//
            13, // enter
            33, // page up
            34, // page down
            35, // end
            36, // home
            38, // arrow up
            40, // arrow down
            46, // delete
            93 // context-menu
            ]
        },

        // row 0 : table in card body
        // row 1 : information + paging in card footer
        // hide the drop-down length and search text because already
        // created within the template
        dom: "<'row'<'col-sm-12'tr>>" + //
        "<'row card-footer px-0 d-print-none '<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    };

    // merge
    const settings = $.extend(true, defaultSettings, options);

    // init
    return $(this).DataTable(settings);
};

/**
 * -------------- JQuery extensions --------------
 */

/**
 * Initialise the search input.
 * 
 * @param {DataTables.Api}
 *            table - the table to update.
 * @param {function}
 *            callback - the callback to call.
 * @param {JQuery}
 *            $clearButton - the clear button (optional).
 * 
 * @return {jQuery} The JQuery element for chaining.
 */
$.fn.initSearchInput = function (table, callback, $clearButton) {
    'use strict';

    const $this = $(this);
    if ($clearButton && $clearButton.length) {
        $clearButton.on('click', function () {
            $this.val('').focus();
            callback(table);
        });
    }

    return $this.handleKeys().on('input', function () {
        $this.updateTimer(callback, 250, table);
    });
};

/**
 * Initialize the table length input.
 * 
 * @param {DataTables.Api}
 *            table - the table to update.
 * @return {jQuery} The JQuery element for chaining.
 */
$.fn.initTableLength = function (table) {
    'use strict';

    return $(this).handleKeys().on('input', function () {
        $(this).updateTimer(tableLengthCallback, 250, table);
    });
};

/**
 * Enabled/Disabled datatables keys.
 * 
 * @param {string}
 *            disableEvent - the event name to disable keys (default is 'focus').
 * @param {string}
 *            enableEvent - the event name to enable keys (default is 'blur').
 * @param {string}
 *            selector - the data table selector (default is '#data-table').
 * 
 * @return {jQuery} The JQuery element for chaining.
 */
$.fn.handleKeys = function (disableEvent, enableEvent, selector) {
    'use strict';

    disableEvent = disableEvent || 'focus';
    enableEvent = enableEvent || 'blur';

    return $(this).on(disableEvent, function () {
        disableKeys(selector);
    }).on(enableEvent, function () {
        enableKeys(selector);
    });
};

/**
 * -------------- Application specific --------------
 */

/**
 * Trigger the click event.
 * 
 * @param {Object}
 *            e - the source event.
 * @param {string}
 *            selector - the JQuery selector.
 * @returns true if event is handled.
 */
function triggerClick(e, selector) { // jshint ignore:line
    'use strict';

    const $element = $(selector);
    if ($element.length && $element.isSelectable()) {
        e.stopPropagation();
        $element.get(0).click();
        return true;
    }

    return false;
}

/**
 * Table length callback.
 * 
 * @param {DataTables.Api}
 *            table - the table to update.
 */
function tableLengthCallback(table) {
    'use strict';

    const length = $('#table_length').intVal();
    if (table.page.len() !== length) {
        table.page.len(length).draw();
    }
}

/**
 * Disable table keys plugin.
 * 
 * @param {string}
 *            selector - the data table selector (default is '#data-table').
 */
function disableKeys(selector) {
    'use strict';
    selector = selector || '#data-table';
    $(selector).DataTable().keys.disable();
}

/**
 * Enable table keys plugin.
 * 
 * @param {string}
 *            selector - the data table selector (default is '#data-table').
 */
function enableKeys(selector) {
    'use strict';
    selector = selector || '#data-table';
    $(selector).DataTable().keys.enable();
}

/**
 * Edit or show the selected item.
 * 
 * @param {Object}
 *            e - the source event.
 * @returns {boolean} true if handle.
 */
function editOrShow($table, e) {
    'use strict';

    // edit by default?
    if ($table.attr('edit-action').toBool()) {
        return triggerClick(e, '.btn-table-edit') || triggerClick(e, '.btn-table-show');
    } else {
        return triggerClick(e, '.btn-table-show') || triggerClick(e, '.btn-table-edit');
    }
}