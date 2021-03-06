/**! compression tag for ftp-deployment */

/**
 * -------------- Application specific --------------
 */

/**
 * Trigger the click event.
 *
 * @param {Object}
 *            e - the source event.
 * @param {string}
 *            selector - the jQuery selector.
 * @returns {boolean} true if event is handled.
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
 * Clear the table search.
 *
 * @param {jQuery}
 *            $element the search input element.
 * @param {DataTables.Api}
 *            table - the table to update.
 * @param {function}
 *            callback - the callback to invoke.
 * @returns {boolean} true if success
 */
function clearSearch($element, table, callback) {
    'use strict';
    if ($element.val()) {
        $element.val('').focus();
        return callback(table);
    } else {
        $element.focus();
        return false;
    }
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
        return true;
    }
    return false;
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
    $(selector).DataTable().keys.disable(); // eslint-disable-line
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
    $(selector).DataTable().keys.enable(); // eslint-disable-line
}

/**
 * -------------- jQuery Extensions --------------
 */

$.fn.extend({
    /**
     * Edit or show the selected item.
     *
     * @param {Object}
     *            e - the source event.
     * @returns {boolean} true if handle.
     */
    editOrShow: function (e) {
        'use strict';
        if ($(this).attr('edit-action').toBool()) {
            return triggerClick(e, '.btn-table-edit') || triggerClick(e, '.btn-table-show');
        } else {
            return triggerClick(e, '.btn-table-show') || triggerClick(e, '.btn-table-edit');
        }
    }
});

/**
 * -------------- DataTables Extensions --------------
 */

/**
 * Add alert class to processing message
 */
$.extend($.fn.dataTable.ext.classes, {
    sProcessing: 'dataTables_processing alert alert-success'
});

/**
 * Gets the selected row.
 *
 * @returns {DataTables.Api} the selected row, if any; null otherwise.
 */
$.fn.dataTable.Api.register('getSelectedRow()', function () {
    'use strict';

    const row = this.row('.table-primary');
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
        this.cell(':first-child', '0:visIdx').focus();
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
        this.cell(':last-child', '0:visIdx').focus();
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
 * Remove duplicate classes in all cells (header and body).
 *
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('removeDuplicateClasses()', function () {
    'use strict';

    this.cells().nodes().to$().removeDuplicateClasses();
    return this;
});

/**
 * Show or hide the pagination.
 *
 * @param {Object}
 *            settings - the table settings object.
 *
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('conditionalPaging()', function (settings) {
    'use strict';

    const pages = this.page.info().pages;
    const $pagination = $('.dataTables_paginate');
    const duration = settings.iDraw === 1 ? 0 : 500;
    if (pages <= 1) {
        $pagination.stop().fadeOut(duration);
    } else {
        $pagination.stop().fadeIn(duration);
    }
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
$.fn.dataTable.Api.register('initEvents()', function (id) {
    'use strict';

    const table = this;
    const $table = $(table.table().node());
    let lastPageCalled = false;

    // bind search and page length
    $('#table_search').initSearchInput(table);
    $('#table_length').initTableLength(table);

    // bind table body rows
    $table.on('dblclick', 'tbody > tr', function (e) {
        return $table.editOrShow(e);
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
            $('.dataTable .table-primary').first().trigger('contextmenu');
        }
    });

    // bind table events
    table.one('init', function () {
        // move to footer
        const $footer = $('.card-footer.footer-place-holder');
        if ($footer.length) {
            $('.table-footer').appendTo($footer);
            $footer.removeClass('footer-place-holder');
        }

        // select row (if any)
        if (id) {
            const row = table.row('#' + id);
            if (row && row.length) {
                table.cell(row.index(), '0:visIdx').focus();
            }
        }

        // remove hiden search text (aria)
        $(":text[tabindex='0']").parent().remove();

    }).on('preDraw', function () {
        $('.has-tooltip').tooltip('hide');

    }).on('draw', function (e, settings) {
        // select
        if (lastPageCalled) {
            table.selectLastRow();
            lastPageCalled = false;
        } else {
            table.selectFirstRow();
        }

        // update
        table.updateButtons().updateTitles().conditionalPaging(settings);

    }).on('search.dt', function () {
        enableKeys();

    }).on('length.dt', function () {
        enableKeys();

    }).on('key-focus', function (e, datatable, cell) {
        // select row
        const row = datatable.row(cell.index().row);
        $(row.node()).addClass('table-primary').scrollInViewport(0, 60);
        datatable.updateButtons();

    }).on('key-blur', function (e, datatable, cell) {
        // unselect row
        const row = datatable.row(cell.index().row);
        $(row.node()).removeClass('table-primary');
        datatable.updateButtons();

    }).on('key', function (e, datatable, key, cell, event) {
        switch (key) {
        case 13: // enter
            return $table.editOrShow(event);
        case 46: // delete
            return triggerClick(event, '.btn-table-delete');
        }
    });

    return table;
});

/**
 * Binds a column search.
 *
 * @param {jQuery}
 *            $source - the search input.
 * @param {integer}
 *            columnIndex - the column index to bind with.
 * @param {jQuery}
 *            $targetFocus - the input to set focus after draw or null to use
 *            the source.
 * @returns {DataTables.Api} this instance.
 */
$.fn.dataTable.Api.register('initSearchColumn()', function ($source, columnIndex, $targetFocus) {
    'use strict';

    // check column
    if (columnIndex < 0 || columnIndex >= this.columns().count()) {
        return this;
    }

    $targetFocus = $targetFocus || $source;
    const column = this.column(columnIndex);
    const display = $targetFocus.is(':not(:hidden)');
    const callback = function () {
        const value = $source.val().trim();
        if (column.search() !== value) {
            column.search(value).draw();
            $source.updateTimer(function () {
                $targetFocus.focus();
            }, 500);
        }
    };

    // copy value
    $source.val(column.search());

    // handle event
    if (display) {
        $source.on('input', function () {
            $source.updateTimer(callback, 250);
        }).handleKeys();
    } else {
        $source.on('input', callback);
    }

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

    const $that = $(this);
    const debug = $that.data('debug');
    return $that.find('th').map(function () {
        const $header = $(this);
        const column = $header.data();

        // data
        if (useName) {
            column.data = column.name;
        }

        // order sequence
        if (column.visible && column.orderable && column.direction === 'desc') {
            column.orderSequence = ['desc', 'asc'];
        }

        // created callback
        if (column.callback) {
            column.createdCell = $.fn.dataTable[column.callback];
        }

        // render callback
        if (column.render) {
            column.render = $.fn.dataTable[column.render];
        }

        // remove attributes
        if (!debug) {
            $header.removeDataAttributes();
        }

        return column;
    }).get();
};

/**
 * Gets the default table order.
 *
 * @param {array}
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
    for (let i = 0, len = columns.length; i < len; i++) {
        if (columns[i].default && columns[i].orderable) {
            return [[i, columns[i].direction]];
        }
    }

    // find first visible and sortable colmun
    for (let i = 0, len = columns.length; i < len; i++) {
        if (columns[i].visible && columns[i].orderable) {
            return [[i, columns[i].direction]];
        }
    }

    // none
    return [];
};

/**
 * Merge the default options within the given options and initialize the data
 * table.
 *
 * @param {Object}
 *            options - the options to merge with default values.
 *
 * @return {jQuery} The jQuery element for chaining.
 */
$.fn.initDataTable = function (options) {
    'use strict';

    const $table = $(this);

    // remote
    const ajax = $table.data('ajax');
    const language = $table.data('lang');

    const defaultSettings = {
        // ajax
        serverSide: true,
        processing: true,
        ajax: ajax,
        language: {
            url: language
        },

        // paging
        pagingType: 'full_numbers',

        // order
        orderMulti: false,

        // class
        stripeClasses: [],
        removeDuplicateClasses: true,

        // mark
        mark: {
            element: 'span',
            className: 'highlight',
            separateWordSearch: false,
            ignorePunctuation: ["'", ",", "."]
        },

        // keys
        keys: {
            focus: ':eq(0:visIdx)',
            blurable: false,
            clipboard: false,
            className: 'table-primary',
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

        // layout
        // row 0 : table in card body
        // row 1 : information + paging moved in card footer
        // Note: drop-down length and search text are hidden
        // dom: "<'row'<'col-sm-12'tr>>" + //
        // "<'row card-footer px-0 d-print-none '<'col-sm-12
        // col-md-5'i><'col-sm-12 col-md-7'p>>",

        dom: "<'row'<'col-sm-12'tr>>" + //
             "<'row table-footer '<'col-lg-auto py-2'i><'col-lg-auto ml-auto'p>>",
    };

    // merge
    const settings = $.extend(true, defaultSettings, options);

    // debug?
    if ($table.data('debug') || false) {
        console.log(JSON.stringify(settings, '', '    '));
    } else {
        $table.removeDataAttributes();
    }

    // initialize
    const table = $table.DataTable(settings); // eslint-disable-line

    // duplicate classes?
    if (settings.removeDuplicateClasses) {
        table.removeDuplicateClasses();
    }

    return table;
};

/**
 * -------------- jQuery extensions --------------
 */
$.fn.extend({
    /**
     * Initialise the search input.
     *
     * @param {DataTables.Api}
     *            table - the table to update.
     *
     * @return {jQuery} The jQuery element for chaining.
     */
    initSearchInput: function (table) {
        'use strict';

        const $this = $(this);
        const $clearButton = $('.btn-clear');
        if ($clearButton.length) {
            $clearButton.on('click', function () {
                clearSearch($this, table, searchCallback);
            });
        }

        return $this.handleKeys().on('input', function () {
            $this.updateTimer(searchCallback, 250, table);
        });
    },

    /**
     * Initialize the table length input.
     *
     * @param {DataTables.Api}
     *            table - the table to update.
     * @return {jQuery} The jQuery element for chaining.
     */
    initTableLength: function (table) {
        'use strict';

        return $(this).handleKeys().on('input', function () {
            $(this).updateTimer(tableLengthCallback, 250, table);
        });
    },

    /**
     * Enabled/Disabled datatables keys.
     *
     * @param {string}
     *            disableEvent - the event name to disable keys (default is
     *            'focus').
     * @param {string}
     *            enableEvent - the event name to enable keys (default is
     *            'blur').
     * @param {string}
     *            selector - the data table selector (default is '#data-table').
     *
     * @return {jQuery} The jQuery element for chaining.
     */
    handleKeys: function (disableEvent, enableEvent, selector) {
        'use strict';

        disableEvent = disableEvent || 'focus';
        enableEvent = enableEvent || 'blur';

        return $(this).on(disableEvent, function () {
            disableKeys(selector);
        }).on(enableEvent, function () {
            enableKeys(selector);
        });
    }
});
