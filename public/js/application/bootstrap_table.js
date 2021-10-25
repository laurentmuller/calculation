/**! compression tag for ftp-deployment */

/* globals Toaster, MenuBuilder */

/**
 * Formatter for the custom view.
 *
 * @param data
 *            the data (rows) to format.
 * @returns the custom view
 */
function customViewFormatter(data) { // jshint ignore:line
    'use strict';
    let view = '';
    const $table = $('#table-edit');
    const regex = /JavaScript:(\w*)/m;
    const $template = $('#custom-view');
    const rowIndex = $table.getSelectionIndex();
    const rowClass = $table.getOptions().rowClass;

    $.each(data, function (index, row) {
        // update class selection
        $template.find('.custom-item').toggleClass(rowClass, rowIndex === index);

        // fields
        let html = $template.html();
        Object.keys(row).forEach(function (key) {
            html = html.replaceAll('%' + key + '%', row[key] || '&#160;');
        });

        // JS functions
        let match;
        while ((match = regex.exec(html)) !== null) {
            let value = '';
            const callback = match[1];
            if(typeof window[callback] !== 'undefined' ) {
                value = window[callback](row) || '&#160;';
            }
            html = html.replaceAll(match[0], value);
        }

        // append
        view += html;
    });

    return '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-lg-3 m-0 mx-n1">' + view + '</div>';
}

/**
 * Format the product unit in the custom view.
 *
 * @param {object}
 *            row - the record data.
 * @returns {string} the formatted product unit.
 */
function formatProductUnit(row) { // jshint ignore:line
    'use strict';
    if (row.unit) {
        return ' / ' + row.unit;
    }
    return '';
}

/**
 * Gets the class of the product price in the custom view.
 *
 * @param {object}
 *            row - the record data.
 * @returns {string} the class.
 */
function formatProductClass(row) { // jshint ignore:line
    'use strict';
    const price = Number.parseFloat(row.price, 10);
    if (!Number.isNaN(price) && price === 0) {
        return ' text-danger';
    }
    return '';
}

/**
 * Cell style for a border column (calculations, status or log).
 *
 * @param {number}
 *            _value - the field value.
 * @param {object}
 *            row - the record data.
 * @returns {object} the cell style.
 */
function styleBorderColor(_value, row) { // jshint ignore:line
    'use strict';
    if (typeof row.color !== 'undefined') {
        return {
            css: {
                'border-left-color': row.color + ' !important'
            }
        };
    }
    return {};
}

/**
 * Cell class for the product price.
 *
 * @param {float}
 *            value - the product price.
 * @returns {object} the cell classes.
 */
function styleProductPrice(value) { // jshint ignore:line
    'use strict';
    const price = Number.parseFloat(value, 10);
    if (!Number.isNaN(price) && price === 0) {
        return {
            css: {
                color: 'var(--danger)'
            }
        };
    }
    return {};
}

/**
 * Row classes for the text muted.
 *
 * @param {object}
 *            row - the record data.
 * @param {int}
 *            index - the row index.
 * @returns {object} the row classes.
 */
function styleTextMuted(row, index) {
    'use strict';
    const value = Number.parseInt(row.textMuted, 10);
    if (!Number.isNaN(value) && value === 0) {
        const $row = $('#table-edit tbody tr:eq(' + index + ')');
        const classes = $row.attr('class') + ' text-muted';
        return {
            classes: classes.trim()
        };
    }
    return {};
}

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
 * @param _$element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateUserAction($table, row, _$element, $action) {
    'use strict';
    if(isConnectedUser($table, row)) {
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
 * @param _$element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateUserSwitchAction($table, row, _$element, $action) {
    'use strict';
    if(isConnectedUser($table, row)) {
        $action.prev('.dropdown-divider').remove();
        $action.remove();
    } else {
        const source = $action.attr('href').split('?')[0];
        const params = {
            '_switch_user': row.username
        };
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
 * @param _$element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateSearchAction($table, row, _$element, $action) {
    'use strict';
    if($action.is('.btn-show') && !row.showGranted) {
        $action.remove();
    } else if($action.is('.btn-edit') && !row.editGranted) {
        $action.remove();
    } else if($action.is('.btn-delete') && !row.deleteGranted) {
        $action.remove();
    } else {
        const id = row.id;
        const type = row.type;
        const href = $action.attr('href').replace('_type_', type).replace('_id_', id);
        $action.attr('href', href);
        const defaultAction = $table.data('defaultAction');
        if($action.is('.btn-show') && defaultAction === 'show') {
            $action.addClass('btn-default');
        } else if($action.is('.btn-edit') && defaultAction === 'edit') {
            $action.addClass('btn-default');
        }
    }
}

/**
 * Update the edit calculation action.
 *
 * @param _$table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @param $element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateCalculationEditAction(_$table, row, $element, $action) {
    'use strict';
    const textMuted = Number.parseInt(row.textMuted, 10);
    if(!Number.isNaN(textMuted) && textMuted === 0) {
        const $state = $element.find('.btn-state');
        if ($state.length) {
            $state.addClass('btn-default');
        } else {
            $element.find('.btn-show').addClass('btn-default');
        }
        $action.remove();
    }
}

/**
 * Update the export calculation action.
 *
 * @param _$table
 *            {jQuery} the parent table.
 * @param _row
 *            {object} the row data.
 * @param _$element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateCalculationAction(_$table, _row, _$element, $action) {
    'use strict';
    const href = $action.attr('href').split('?')[0];
    $action.attr('href', href);
}

/**
 * Update the task compute action.
 *
 * @param _$table
 *            {jQuery} the parent table.
 * @param row
 *            {object} the row data.
 * @param _$element
 *            {jQuery} the table row.
 * @param $action
 *            {jQuery} the action to update
 */
function updateTaskComputeAction(_$table, row, _$element, $action) {
    'use strict';
    const items = Number.parseInt(row.items, 10);
    if(Number.isNaN(items) || items === 0) {
        $action.prev('.dropdown-divider').remove();
        $action.remove();
    }
}

/**
 * Formatter for the actions column.
 *
 * @param {number}
 *            value - the field value (id).
 * @param {object}
 *            _row - the row record data.
 * @returns {string} the rendered cell.
 */
function formatActions(value, _row) { // jshint ignore:line
    'use strict';
    const substr = '$1' + value;
    const regex = /(\/|\bid=)(\d+)/;
    const $actions = $('#dropdown-actions').clone().removeClass('d-none');
    $actions.find('.dropdown-item-path').each(function () {
        const $link = $(this);
        const source = $link.attr('href');
        const target = source.replace(regex, substr);
        $link.attr('href', target);
    });
    return $actions.html();
}

/**
 * jQuery extensions.
 */
$.fn.extend({

    getDataValue: function () {
        'use strict';
        return $(this).data('value') || null;
    },

    setDataValue(value, $selection, copyText, copyIcon) {
        'use strict';
        const $this = $(this);
        const $items = $this.next('.dropdown-menu').find('.dropdown-item').removeClass('active');
        if ($.isUndefined(copyText) || copyText === null) {
            copyText = true;
        }
        if ($.isUndefined(copyIcon) || copyIcon === null) {
            copyIcon = false;
        }

        let $icon = $this.find('i');
        let text = $this.text().trim();
        if (value) {
            $selection.addClass('active');
            if (copyIcon) {
                $icon = $selection.find('i') || $icon;
            }
            if (copyText) {
                text = $selection.text().trim() || text;
            }
        } else {
            $items.first().addClass('active');
            if (copyIcon) {
                $icon = $this.data('icon') || $icon;
            }
            if (copyText) {
                text = $this.data('default') || text;
            }
        }
        if ($icon.length) {
            $this.text(' ' + text);
            $this.prepend($icon.clone());
        } else {
            $this.text(text);
        }
        return $this.data('value', value);
    },

    initDropdown: function (copyText, copyIcon) {
        'use strict';
        const $this = $(this);
        const $menu = $this.next('.dropdown-menu');
        if ($.isUndefined(copyText) || copyText === null) {
            copyText = true;
        }
        if ($.isUndefined(copyIcon) || copyIcon === null) {
            copyIcon = false;
        }
        $menu.on('click', '.dropdown-item', function () {
            const $item = $(this);
            const newValue = $item.getDataValue();
            const oldValue = $this.getDataValue();
            if(newValue !== oldValue) {
                $this.setDataValue(newValue || '', $item, copyText, copyIcon).trigger('input');
            }
            $this.focus();
        });
        $this.parent().on('shown.bs.dropdown', function () {
            $menu.find('.active').focus();
        });
        return $this;
    },

    /**
     * Gets the context menu items for the selected cell.
     *
     * @return {object} the context menu items.
     */
    getContextMenuItems: function () {
        'use strict';
        let $parent;
        const $this = $(this);
        if ($this.is('div')) {
            $parent = $this.parents('.custom-item');
        } else {
            $parent = $this.parents('tr');
        }
        const $elements = $parent.find('.dropdown-menu').children();
        const builder = new MenuBuilder();
        return builder.fill($elements).getItems();
    }
});

/**
 * Ready function
 */
(function ($) {
    'use strict';

    const $table = $('#table-edit');
    const $sortButton = $('#button_sort');
    const $viewButton = $('#button_view');
    const $pageButton = $('#button_page');
    const $clearButton = $('#clear_search');
    const $searchMinimum = $('#search_minimum');
    const $inputs = $('.dropdown-toggle.dropdown-input');

    // initialize table
    const options = {
        queryParams: function (params) {
            $inputs.each(function () {
                const $this = $(this);
                const value = $this.getDataValue();
                if(value) {
                    params[$this.attr('id')] = value;
                }
            });
            return params;
        },

        onPreBody: function (data) {
            // options
            const options = $table.getOptions();

            // update pages list and page button
            if ($pageButton.length) {
                let pageList = options.pageList;
                for(let i = 0; i < pageList.length; i++) {
                    if(pageList[i] >= options.totalRows) {
                        pageList = pageList.splice(0, i + 1);
                        break;
                    }
                }
                if(pageList.length <= 1) {
                    $pageButton.toggleDisabled(true);
                } else {
                    const pageSize = Number.parseInt(options.pageSize, 10);
                    const $links = pageList.map(function (page) {
                        const $link = $('<button/>', {
                            'class': 'dropdown-page dropdown-item',
                            'data-value': page,
                            'text': page
                        });
                        if(page === pageSize) {
                            $link.addClass('active');
                        }
                        return $link;
                    });
                    $('.dropdown-page').remove();
                    $('.dropdown-menu-page').append($links);
                    $pageButton.toggleDisabled(false);
                }
            }

            // update clear search button
            if ($clearButton.length) {
                let enabled = $table.isSearchText();
                if (!enabled && $inputs.length) {
                    $inputs.each(function () {
                        if ($(this).getDataValue()) {
                            enabled = true;
                            return false;
                        }
                    });
                }
                $clearButton.toggleDisabled(!enabled);
            }

            // update UI
            if (data.length === 0) {
                $('.card-footer').hide();
                $viewButton.toggleDisabled(true);
                $sortButton.toggleDisabled(true);
            } else {
                $('.card-footer').show();
                $viewButton.toggleDisabled(false);
                $sortButton.toggleDisabled(false);
            }

            // update search minimum
            if ($searchMinimum.length) {
                $searchMinimum.toggleClass('d-none', $table.getSearchText().length > 1);
            }

            // update sort
            $('.dropdown-menu-sort.active').removeClass('active');
            $('.dropdown-menu-sort[data-sort="' + options.sortName + '"][data-order="' + options.sortOrder + '"]').addClass('active');
        },

        // for debug purpose
        // onAll: function (name) {
        // console.log(name, Array.from(arguments).slice(1));
        // },

        onPageChange: function () {
            // hide
            if ($table.isCustomView()) {
                $('.bootstrap-table .fixed-table-custom-view .custom-item').animate({'opacity': '0'}, 200);
                $table.hideCustomViewMessage();
            }
        },

        onRenderCustomView: function (_$table, row, $item) {
            // update border color
            if (typeof row.color !== 'undefined') {
                const style = 'border-left-color: ' + row.color + ' !important';
                $item.attr('style', style);
            }

            // text-muted
            if (typeof row.textMuted !== 'undefined') {
                const value = Number.parseInt(row.textMuted, 10);
                if (!Number.isNaN(value) && value === 0) {
                    $item.addClass('text-muted');
                }
            }

            // update link
            const $link = $item.find('a.item-link');
            const $button = $item.find('a.btn-default');
            if ($link.length && $button.length) {
                 $link.attr('href', $button.attr('href'));
                 $link.attr('title', $button.text());
            }
        },

        onRenderCardView: function (_$table, row, $item) {
            // border color
            if (typeof row.color !== 'undefined') {
                const $cell = $item.find('td:first');
                const style = 'border-left-color: ' + row.color + ' !important';
                $cell.addClass('text-border').attr('style', style);
            }

            // text-muted
            if (typeof row.textMuted !== 'undefined') {
                const value = Number.parseInt(row.textMuted, 10);
                if (!Number.isNaN(value) && value === 0) {
                    $item.find('.card-view-value.font-weight-bold').addClass('text-body');
                }
            }
        },

        onRenderAction: function ($table, row, $element, $action) {
            if($action.is('.btn-user-switch')) {
                updateUserSwitchAction($table, row, $element, $action);
            } else if($action.is('.btn-user-message, .btn-user-delete')) {
                updateUserAction($table, row, $element, $action);
            } else if($action.is('.btn-calculation-edit')) {
                updateCalculationEditAction($table, row, $element, $action);
            } else if($action.is('.btn-calculation-pdf')) {
                updateCalculationAction($table, row, $element, $action);
            } else if($action.is('.btn-calculation-excel')) {
                updateCalculationAction($table, row, $element, $action);
            } else if($action.is('.btn-search')) {
                updateSearchAction($table, row, $element, $action);
            } else if($action.is('.btn-task-compute')) {
                updateTaskComputeAction($table, row, $element, $action);
            }
        },

        onUpdateHref: function (_$table, $actions) {
            if($actions.length === 1) {
                $actions.addClass('btn-default');
            }
        },

        // show message
        onLoadError: function (_status, jqXHR) {
            if ('abort' !== jqXHR.statusText) {
                const title = $('.card-title').text();
                const message = $table.data('errorMessage');
                Toaster.danger(message, title, $('#flashbags').data());
            }
        }
    };
    $table.initBootstrapTable(options);

    // update add button
    const $addButton = $('.add-link');
    if ($addButton.length) {
        $table.on('update-row.bs.table', function () {
            const $source =  $table.findAction('.btn-add');
            if ($source) {
                $addButton.attr('href', $source.attr('href'));
            }
        });
    }

    // handle drop-down input buttons
    $inputs.each(function () {
        $(this).initDropdown().on('input', function () {
            $table.refresh();
        });
    });

    // handle clear search button
    if ($clearButton.length) {
        $clearButton.on('click', function () {
            const isSearchText = $table.isSearchText();
            const isQueryParams = !$.isEmptyObject(options.queryParams({}));
            // clear drop-down
            $inputs.each(function () {
                $(this).setDataValue(null);
            });
            if(isSearchText) {
                $table.resetSearch();
            } else if(isQueryParams) {
                $table.refresh();
            }
            $('input.search-input').focus();
        });
    }

    // handle the page button
    if ($pageButton.length) {
        $pageButton.initDropdown().on('input', function () {
            const pageSize = $pageButton.getDataValue();
            $table.refresh({
                pageSize: pageSize
            });
        });
    }

    // handle view buttons
    $('#button_view').initDropdown(false, true).on('input', function () {
        const view = $(this).getDataValue();
        $table.setDisplayMode(view);
    });

    // handle sort buttons
    $('.dropdown-menu-sort').on('click', function () {
        const $this = $(this);
        const sortName = $this.data('sort');
        const sortOrder = $this.data('order');
        const data = $table.getBootstrapTable();
        if (data && data.options.sortName !== sortName || data.options.sortOrder !== sortOrder) {
             data.options.sortName = sortName;
             data.options.sortOrder = sortOrder;
             $table.refresh();
         }
    });
    $('.btn-group-sort').on('shown.bs.dropdown', function () {
        $(this).find('.dropdown-menu-sort.active').focus();
    });

    // handle keys enablement
    const keysSelector = 'a, input, .btn, .dropdown-item, .rowlink-skip';
    $('body').on('focus', keysSelector, function () {
        $table.disableKeys();
    }).on('blur', keysSelector, function () {
        $table.enableKeys();
    });

    // initialize context menu
    const ctxSelector =  'tr.table-primary td:not(.rowlink-skip), .custom-item.table-primary div:not(.rowlink-skip)';
    const show = function () {
        $('.dropdown-menu.show').removeClass('show');
        return true;
    };
    $table.parents('.bootstrap-table').initContextMenu(ctxSelector, show);

    // initialize danger tooltips
    if($table.data('danger-tooltip-selector')) {
        $table.parents('.bootstrap-table').tooltip({
            customClass: 'tooltip-danger',
            selector: $table.data('danger-tooltip-selector')
        });
    }

    // update UI
    $('.fixed-table-pagination').appendTo('.card-footer');
    $('.fixed-table-toolbar').appendTo('.col-search');
    $('.fixed-table-toolbar input.search-input').attr('type', 'text').addClass('form-control-sm').prependTo('.input-group-search');
    $('.fixed-table-toolbar .search').remove();
    $('.btn-group-search').appendTo('.fixed-table-toolbar');
    if ($searchMinimum.length) {
        $searchMinimum.toggleClass('d-none', $table.getSearchText().length > 1);
    }

    // focus
    if($table.isEmpty()) {
        $('input.search-input').focus();
    }
}(jQuery));
