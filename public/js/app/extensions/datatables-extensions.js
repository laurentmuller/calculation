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
 * Update the column titles.
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('updateTitles()', function () {
    'use strict';

    this.columns().every(function () {
        const $header = $(this.header());
        const title = $header.attr('aria-label').split(':');
        if (title[1]) {
            $header.attr('title', title[1].trim());
        } else {
            $header.removeAttr('title');
        }
    });

    return this;
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

        // created cell callback
        if (column.createdCell) {
            column.createdCell = $.fn.dataTable[column.createdCell];
        }

        // cell render callback
        if (column.render) {
            column.render = $.fn.dataTable[column.render];
        }

        columns.push(column);

        // remove
        if (!$that.data('debug')) {
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
            blurable: false,
            clipboard: false,
            className: 'selection',
            keys: [ //
            13, // enter
            33, // page up
            34, // page down
            35, // end
            36, // home
            38, // arrow up
            40, // arrow down
            46 // delete
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
 * Update the column titles.
 * 
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('updateTitles()', function () {
    'use strict';

    this.columns().every(function () {
        const $header = $(this.header());
        const title = $header.attr('aria-label').split(':');
        if (title[1]) {
            $header.attr('title', title[1].trim());
        } else {
            $header.removeAttr('title');
        }
    });

    return this;
});

/**
 * -------------- JQuery extensions --------------
 */

/**
 * Initialise the search input.
 * 
 * @param {function}
 *            callback - the callback to call.
 * @param {DataTables.Api}
 *            table - the table to update.
 * @param {JQuery}
 *            $clearButton - the clear button.
 * 
 * @return {jQuery} The JQuery element for chaining.
 */
$.fn.initSearchInput = function (callback, table, $clearButton) {
    'use strict';

    const $this = $(this);
    if ($clearButton) {
        $clearButton.on('click', function () {
            $this.val('').focus();
            callback(table);
        });
    }

    return $this.on('focus', function () {
        table.keys.disable();
    }).on('blur', function () {
        table.keys.enable();
    }).on('input', function () {
        $this.updateTimer(callback, 250, table);
    });
};

/**
 * Initialize the table length input.
 * 
 * @param {DataTables.Api}
 *            table - the table to update.
 * 
 * @return {jQuery} The JQuery element for chaining.
 */
$.fn.initTableLength = function (table) {
    'use strict';

    return $(this).on('input', function () {
        $(this).updateTimer(tableLengthCallback, 250, table);
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
    if ($element.length && !$element.hasClass('disabled')) {
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
