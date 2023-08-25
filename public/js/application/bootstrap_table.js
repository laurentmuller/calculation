/**! compression tag for ftp-deployment */

/* globals Toaster, MenuBuilder, bootstrap */

/**
 * Formatter for the custom view.
 *
 * @param {Array.<Object>} data - the rows to format.
 * @returns {string} the custom view.
 */
function customViewFormatter(data) {
    'use strict';
    const $table = $('#table-edit');
    const regex = /JavaScript:(\w*)/m;
    const $template = $('#custom-view');
    const rowIndex = $table.getSelectionIndex();
    const rowClass = $table.getOptions().rowClass;
    const undefinedText = $table.data('undefined-text') || '&#8203;';
    const content = data.reduce(function (carry, row, index) {
        // update class selection
        $template.find('.custom-item').toggleClass(rowClass, rowIndex === index);

        // fields
        let html = $template.html();
        Object.keys(row).forEach(function (key) {
            html = html.replaceAll(`%${key}%`, row[key] || undefinedText);
        });

        // functions
        let match = regex.exec(html);
        while (match !== null) {
            let value = undefinedText;
            const callback = match[1];
            if (typeof window[callback] !== 'undefined') {
                value = window[callback](row) || undefinedText;
            }
            html = html.replaceAll(match[0], value);
            match = regex.exec(html);
        }

        // add
        return carry + html;
    }, '');

    return `<div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-lg-3 m-0 mx-n1">${content}</div>`;
}

/**
 * Format the product unit in the custom view.
 *
 * @param {object} row - the record data.
 * @returns {string} the formatted product unit.
 */
function formatProductUnit(row) {
    'use strict';
    return row.unit ? ' / ' + row.unit : '';
}

/**
 * Gets the class of the product price in the custom view.
 *
 * @param {object} row - the record data.
 * @returns {string} the class.
 */
function formatProductClass(row) {
    'use strict';
    const price = $.parseFloat(row.price);
    if (price === 0) {
        return ' text-danger';
    }
    return '';
}

/**
 * Cell style for a border column (calculations, status or log).
 *
 * @param {number} _value - the field value.
 * @param {object} row - the record data.
 * @returns {object} the cell style.
 */
function styleBorderColor(_value, row) {
    'use strict';
    if (!$.isUndefined(row.color)) {
        return {
            css: {
                'border-left-color': `${row.color} !important`
            }
        };
    }
    return {};
}

/**
 * Cell class for the product price.
 *
 * @param {string} value - the product price.
 * @returns {object} the cell classes.
 */
function styleProductPrice(value) {
    'use strict';
    if ($.parseFloat(value) === 0) {
        return {
            css: {
                color: 'var(--bs-danger)'
            }
        };
    }
    return {};
}

/**
 * Row classes for the text muted.
 *
 * @param {Object} row - the record data.
 * @param {string} row.textMuted - the text muted value
 * @param {int} index - the row index.
 * @returns {object} the row classes.
 */
function styleTextMuted(row, index) {
    'use strict';
    if ($.parseInt(row.textMuted) === 0) {
        const $row = $('#table-edit tbody tr:eq(' + index + ')');
        const classes = ($row.attr('class') || '') + ' text-body-secondary';
        return {
            classes: classes.trim()
        };
    }
    return {};
}

/**
 * Returns if the current row is rendered for the connected user
 *
 * @param {jQueryTable} $table - the parent table.
 * @param {Object} row - the row data.
 * @returns {boolean} true if connected user
 */
function isConnectedUser($table, row) {
    'use strict';
    const currentId = $.parseInt(row.id);
    const connectedId = $.parseInt($table.data('user-id'));
    return currentId === connectedId;
}

/**
 * Returns if the current row is rendered for the original connected user
 *
 * @param {jQueryTable} $table - the parent table.
 * @param {Object} row - the row data.
 * @returns {boolean} true if connected user
 */
function isOrignalUser($table, row) {
    'use strict';
    const currentId = $.parseInt(row.id);
    const originalId = $.parseInt($table.data('original-user-id'));
    return currentId === originalId;
}

/**
 * Update the user message action.
 *
 * @param {jQueryTable} $table - the parent table.
 * @param {Object} row - the row data.
 * @param {jQuery} _$element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateUserMessageAction($table, row, _$element, $action) {
    'use strict';
    if (isConnectedUser($table, row)) {
        $action.prev('.user-message-divider').remove();
        $action.remove();
    }
}

/**
 * Update the user delete action.
 *
 * @param {jQueryTable} $table - the parent table.
 * @param {Object} row - the row data.
 * @param {jQuery} _$element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateUserDeleteAction($table, row, _$element, $action) {
    'use strict';
    if (isConnectedUser($table, row) || isOrignalUser($table, row)) {
        $action.prev('.delete-divider').remove();
        $action.remove();
    }
}

/**
 * Update the switch user action.
 *
 * @param {jQueryTable} $table - the parent table.
 * @param {Object} row - the row data.
 * @param {jQuery} _$element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateUserSwitchAction($table, row, _$element, $action) {
    'use strict';
    if (isConnectedUser($table, row)) {
        $action.prev('.user-switch-divider').remove();
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
 * Update the reset request password user action.
 *
 * @param {jQueryTable} $table - the parent table.
 * @param {Object} row - the row data.
 * @param {string} row.resetPassword - the reset password value
 * @param {jQuery} _$element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateUserResetAction($table, row, _$element, $action) {
    'use strict';
    if ($.parseInt(row.resetPassword) === 0) {
        $action.prev('.user-reset-divider').remove();
        $action.remove();
    }
}

/**
 * Update the search action.
 *
 * @param {jQueryTable} $table - the parent table.
 * @param {Object} row - the row data.
 * @param {string} row.id - the row identifier.
 * @param {string} row.type - the entity type.
 * @param {boolean} row.allowShow - the show granted.
 * @param {boolean} row.allowEdit - the  edit granted.
 * @param {boolean} row.allowDelete - the deleted granted.
 * @param {jQuery} _$element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateSearchAction($table, row, _$element, $action) {
    'use strict';
    if ($action.is('.btn-show') && !row.allowShow) {
        $action.remove();
    } else if ($action.is('.btn-edit') && !row.allowEdit) {
        $action.remove();
    } else if ($action.is('.btn-delete') && !row.allowDelete) {
        $action.remove();
    } else {
        const id = row.id;
        const type = row.type;
        const href = $action.attr('href').replace('_type_', type).replace('_id_', id);
        $action.attr('href', href);
        const defaultAction = $table.data('defaultAction');
        if ($action.is('.btn-show') && defaultAction === 'show') {
            $action.addClass('btn-default');
        } else if ($action.is('.btn-edit') && defaultAction === 'edit') {
            $action.addClass('btn-default');
        }
    }
}

/**
 * Update the edit calculation action.
 *
 * @param {jQuery} _$table - the parent table.
 * @param {Object} row - the row data.
 * @param {jQuery} $element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateCalculationEditAction(_$table, row, $element, $action) {
    'use strict';
    const value = $.parseInt(row.textMuted);
    if (value === 0) {
        const $state = $element.find('.btn-state');
        if ($state.length) {
            $state.addClass('btn-default');
        } else {
            $element.find('.btn-show').addClass('btn-default');
        }
        $action.removeClass('btn-default');
    }
}

/**
 * Update the export calculation action.
 *
 * @param {jQuery} _$table - the parent table.
 * @param {Object} _row - the row data.
 * @param {jQuery} _$element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateCalculationAction(_$table, _row, _$element, $action) {
    'use strict';
    const href = $action.attr('href').split('?')[0];
    $action.attr('href', href);
}

/**
 * Update the task compute action.
 *
 * @param {jQueryTable} _$table - the parent table.
 * @param {Object} row - the row data.
 * @param {jQuery} _$element - the table row.
 * @param {jQuery} $action - the action to update
 */
function updateTaskComputeAction(_$table, row, _$element, $action) {
    'use strict';
    if ($.parseInt(row.items) === 0) {
        $action.prev('.task-compute-divider').remove();
        $action.remove();
    }
}

/**
 * Update the show entity action.
 *
 * @param {Object} row - the row data.
 * @param {jQuery} $action - the action to update
 * @param {string} propertyName -  the property name to get from row.
 */
function updateShowEntityAction(row, $action, propertyName) {
    'use strict';
    if (row.hasOwnProperty(propertyName)) {
        const value = row[propertyName];
        const href = $(value).attr('href');
        if (href) {
            $action.attr('href', href);
            return;
        }
    }
    $action.remove();
}

/**
 * Formatter for the actions' column.
 *
 * @param {number} value - the field value (id).
 * @param {object} _row - the row record data.
 * @returns {string} the rendered cell.
 */
function formatActions(value, _row) {
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
 * Initialize keys enablement.
 *
 * @param {jQueryTable} $table the parent table.
 */
function initializeKeyHandler($table) {
    'use strict';
    const selector = 'a, input:not(.form-control-search), select, .btn, .dropdown-item, .rowlink-skip';
    $('body').on('focus', selector, function () {
        $table.disableKeys();
    }).on('blur', selector, function () {
        $table.enableKeys();
    });
}

/**
 * Initialize context menus.
 *
 * @param {jQueryTable} $table the parent table.
 */
function initializeContextMenus($table) {
    'use strict';
    const selector = 'tr.table-primary td:not(.rowlink-skip), .custom-item.table-primary div:not(.rowlink-skip)';
    const hideMenus = function () {
        $.hideDropDownMenus();
        return true;
    };
    $table.parents('.bootstrap-table').initContextMenu(selector, hideMenus);
}

/**
 * Initialize danger tooltips.
 *
 * @param {jQueryTable} $table the parent table.
 */
function initializeDangerTooltips($table) {
    'use strict';
    const selector = $table.data('danger-tooltip-selector');
    if (selector) {
        $table.parents('.bootstrap-table').tooltip({
            customClass: 'tooltip-danger',
            selector: selector,
            html: true
        });
    }
}

/**
 * Show the page selection dialog.
 *
 * @param {jQueryTable} $table - the data table.
 * @param {jQuery} $button - the caller button.
 * @param {jQuery} [$source] - the source page link.
 */
function showPageDialog($table, $button, $source) {
    'use strict';
    const $dialog = $('#modal-page');
    if ($dialog.length === 0) {
        const url = $button.data('url');
        $.get(url, function (data) {
            $(data).appendTo('.page-content');
            $table.initPageDialog();
            $('#modal-page').data('source', $source).modal('show');
        });
    } else {
        $dialog.data('source', $source).modal('show');
    }
}

/**
 * Show the sort field dialog.
 *
 * @param {jQueryTable} $table - the data table.
 * @param {jQuery} $button - the caller button.
 */
function showSortDialog($table, $button) {
    'use strict';
    const $dialog = $('#modal-sort');
    if ($dialog.length === 0) {
        const url = $button.data('url');
        const columns = $table.getSortableColumns();
        $.post(url, JSON.stringify(columns), function (data) {
            $(data).appendTo('.page-content');
            $table.initSortDialog();
            $('#modal-sort').modal('show');
        });
    } else {
        $dialog.modal('show');
    }
}

/**
 * jQuery extensions.
 */

(function ($) {
    'use strict';
    $.fn.extend({
        /**
         * Gets the context menu items for the selected cell.
         * @return {object} the context menu items.
         */
        getContextMenuItems: function () {
            let $parent;
            const $this = $(this);
            if ($this.is('div')) {
                $parent = $this.parents('.custom-item');
            } else {
                $parent = $this.parents('tr');
            }
            const $elements = $parent.find('.dropdown-menu').children();
            const builder = new MenuBuilder({
                classSelector: 'btn-default'
            });
            return builder.fill($elements).getItems();
        }
    });

    /**
     * Ready function
     */
    const $table = $('#table-edit');
    const $showPage = $('.btn-show-page');
    const $showSort = $('.btn-sort-data');
    const $pageButton = $('#button_page');
    const $clearButton = $('#clear_search');
    const $viewButtons = $('.dropdown-menu-view');
    const $searchMinimum = $('#search_minimum');

    // handle drop-down input buttons
    const inputs = $('.dropdown-toggle.dropdown-input').dropdown().on('input', function () {
        $table.refresh({
            pageNumber: 1
        });
    }).map(function () {
        return $(this).data($.DropDown.NAME);
    });

    // initialize table
    const options = {
        draggableModal: {
            marginBottom: $('footer:visible').length ? $('footer').outerHeight() : 0, focusOnShow: true
        },

        queryParams: function (params) {
            inputs.each(function () {
                const id = this.getId();
                const value = this.getValue();
                if (id && value) {
                    params[id] = value;
                }
            });
            return params;
        },

        onPreBody: function (data) {
            // update pages list and page button
            if ($pageButton.length) {
                // filter pages
                const options = $table.getOptions();
                let pageList = options.pageList;
                for (let i = 0; i < pageList.length; i++) {
                    if (pageList[i] >= options.totalRows) {
                        pageList = pageList.splice(0, i + 1);
                        break;
                    }
                }
                if (pageList.length <= 1) {
                    $pageButton.toggleDisabled(true);
                } else {
                    // build items
                    const $pages = pageList.map(function (page) {
                        const $page = $('<button/>', {
                            'class': 'dropdown-page dropdown-item',
                            'data-value': page,
                            'text': page
                        });
                        if (page === options.pageSize) {
                            $page.addClass('active');
                        }
                        return $page;
                    });
                    $('.dropdown-page').remove();
                    $('.dropdown-menu-page').append($pages);
                    $pageButton.toggleDisabled(false);
                }
            }

            // update page selection button
            if ($showPage.length) {
                const $separators = $('.fixed-table-pagination .page-first-separator,.fixed-table-pagination .page-last-separator');
                $showPage.toggleClass('d-none', $separators.length === 0);
            }

            // update clear button
            if ($clearButton.length) {
                let enabled = $table.isSearchText();
                if (!enabled && inputs.length) {
                    inputs.each(function () {
                        if (this.getValue()) {
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
                $viewButtons.toggleDisabled(true);
                $showSort.toggleDisabled(true);
            } else {
                $('.card-footer').show();
                $viewButtons.toggleDisabled(false);
                $showSort.toggleDisabled(false);
            }

            // update search minimum
            if ($searchMinimum.length) {
                $searchMinimum.toggleClass('d-none', $table.getSearchText().length > 1);
            }
        },

        onPageChange: function () {
            // hide
            $('.card').trigger('click');
            if ($table.isCustomView()) {
                $('.bootstrap-table .fixed-table-custom-view .custom-item').animate({'opacity': '0'}, 200);
                $table.hideCustomViewMessage();
            }
        },

        onRenderCustomView: function (_$table, row, $item) {
            // update border color
            if (typeof row.color !== 'undefined') {
                const style = `border-left-color: ${row.color} !important`;
                $item.attr('style', style);
            }

            // text-muted
            if (typeof row.textMuted !== 'undefined') {
                const value = $.parseInt(row.textMuted);
                if (value === 0) {
                    $item.addClass('text-body-secondary');
                }
            }

            // update link
            const $link = $item.find('a.item-link');
            const $button = $item.find('a.btn-default');
            if ($link.length && $button.length) {
                $link.attr({
                    'href': $button.attr('href'), 'title': $button.text()
                });
            }
        },

        onRenderAction: function ($table, row, $element, $action) {
            if ($action.is('.btn-user-switch')) {
                updateUserSwitchAction($table, row, $element, $action);
            } else if ($action.is('.btn-user-message')) {
                updateUserMessageAction($table, row, $element, $action);
            } else if ($action.is('.btn-user-delete')) {
                updateUserDeleteAction($table, row, $element, $action);
            } else if ($action.is('.btn-user-reset')) {
                updateUserResetAction($table, row, $element, $action);
            } else if ($action.is('.btn-calculation-edit')) {
                updateCalculationEditAction($table, row, $element, $action);
            } else if ($action.is('.btn-calculation-pdf')) {
                updateCalculationAction($table, row, $element, $action);
            } else if ($action.is('.btn-calculation-excel')) {
                updateCalculationAction($table, row, $element, $action);
            } else if ($action.is('.btn-search')) {
                updateSearchAction($table, row, $element, $action);
            } else if ($action.is('.btn-task-compute')) {
                updateTaskComputeAction($table, row, $element, $action);
            } else if ($action.is('.btn-show-category')) {
                updateShowEntityAction(row, $action, 'categories');
            } else if ($action.is('.btn-show-product')) {
                updateShowEntityAction(row, $action, 'products');
            } else if ($action.is('.btn-show-task')) {
                updateShowEntityAction(row, $action, 'tasks');
            } else if ($action.is('.btn-show-calculation')) {
                updateShowEntityAction(row, $action, 'calculations');
            }
        },

        onUpdateHref: function (_$table, $actions) {
            if ($actions.length === 1) {
                $actions.addClass('btn-default');
            }
            $actions.parents('.dropdown-menu').removeSeparators();
        },

        // show message
        onLoadError: function (_status, jqXHR) {
            if ('abort' !== jqXHR.statusText) {
                const title = $('.card-title').text();
                const message = $table.data('errorMessage');
                Toaster.danger(message, title);
            }
        },

        // for debug purpose
        // onAll: function (name) {
        //     window.console.log(name, Array.from(arguments).slice(1));
        // },
    };

    $table.initBootstrapTable(options);

    // update add button
    const $addButton = $('.add-link');
    if ($addButton.length) {
        $table.on('update-row.bs.table', function () {
            const $source = $table.findAction('.btn-add');
            if ($source) {
                $addButton.attr('href', $source.attr('href'));
            }
        });
    }

    // handle clear search button
    if ($clearButton.length) {
        $clearButton.on('click', function () {
            const isSearchText = $table.isSearchText();
            const isQueryParams = !$.isEmptyObject(options.queryParams({}));
            // clear drop-downs
            inputs.each(function () {
                this.setValue(null);
            });
            if (isSearchText) {
                $table.resetSearch();
            } else if (isQueryParams) {
                $table.refresh();
            }
            $('input.form-control-search').trigger('focus');
        });
    }

    // handle the page button
    if ($pageButton.length) {
        $pageButton.dropdown().on('input', function (e, value) {
            $table.refresh({
                pageSize: value
            });
        });
    }

    // focus on dropdown
    const $dropdowns = $('.card-body .dropdown');
    if ($dropdowns.length) {
        $dropdowns.on('shown.bs.dropdown', function () {
            $(this).find('.active').trigger('focus');
        });
    }

    // handle view buttons
    $viewButtons.on('click', function () {
        $viewButtons.removeClass('dropdown-item-checked');
        const view = $(this).addClass('dropdown-item-checked').data('value') || 'table';
        $('#button_other_actions').trigger('focus');
        $table.setDisplayMode(view);
    });

    // handle sort buttons
    if ($showSort.length) {
        $showSort.on('click', function () {
            showSortDialog($table, $showSort);
        });
        $table.on('contextmenu', 'th', function (e) {
            e.preventDefault();
            showSortDialog($table, $showSort);
        });
    }

    // handle page selection button
    const $pagination = $('.fixed-table-pagination');
    if ($showPage.length) {
        $showPage.on('click', function (e, source) {
            showPageDialog($table, $showPage);
        });
        $pagination.on('click', '.page-first-separator .page-link,.page-last-separator .page-link', function () {
            showPageDialog($table, $showPage, $(this));
        });
    }

    // handle keys enablement
    initializeKeyHandler($table);

    // initialize context menu
    initializeContextMenus($table);

    // initialize danger tooltips
    initializeDangerTooltips($table);

    // update UI
    $('.card .dropdown-menu').removeSeparators();
    $pagination.addClass('small').appendTo('.card-footer');
    if ($searchMinimum.length) {
        $searchMinimum.toggleClass('d-none', $table.isSearchText());
    }

    if ($table.isEmpty()) {
        $('input.form-control-search').trigger('focus');
    } else {
        $table.showSelection();
    }
}(jQuery));
