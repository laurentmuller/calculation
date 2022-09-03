/**! compression tag for ftp-deployment */

/* globals MenuBuilder  */

/**
 * -------------- JQuery extensions --------------
 */
$.fn.extend({
    /**
     * Creates the context menu items.
     *
     * @returns {Object} the context menu items.
     */
    getContextMenuItems: function () {
        'use strict';
        const $elements = $(this).parents('tr:first').find('.dropdown-menu').children();
        const builder = new MenuBuilder({
            classSelector: 'btn-default'
        });
        return builder.fill($elements).getItems();
    }
});

/**
 * Handle the restriction checkbox.
 *
 * @param {JQuery} $restrict - the checkbox to handle.
 */
function onRestrictInput($restrict) {
    'use strict';
    const newValue = $restrict.isChecked();
    const oldValue = $restrict.data('value');
    if (newValue !== oldValue) {
        const param = {
            restrict: +newValue
        };
        const id = $('#calculations tr.table-primary').data('id');
        if (id) {
            param.id = id;
        }
        const url = $restrict.data('url') + '?' + $.param(param);
        window.location.assign(url);
    }
}

/**
 * Creates the key down handler for the calculation table.
 *
 * @param {JQuery} $table - the table to handle.
 * @return {(function(*): void)|*}
 */
function createKeydownHandler($table) {
    'use strict';
    return function (e) {
        // special key?
        if ((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
            return;
        }

        // rows?
        const rows = $table.find('tr').length;
        if (rows === 0) {
            return;
        }

        const $selection = $table.find('tr.table-primary');
        switch (e.which) {
            case 13:  // enter (edit selected row)
            {
                const $link = $selection.find('.btn-default');
                if ($link.length) {
                    $link[0].click();
                    e.preventDefault();
                }
                break;
            }
            case 35:// end (select last row)
            {
                const $last = $table.find('tr:last');
                if (!$selection.is($last)) {
                    $selection.removeClass('table-primary');
                    $last.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                }
                break;
            }
            case 36: // home (select first row)
            {
                const $first = $table.find('tr:first');
                if (!$selection.is($first)) {
                    $selection.removeClass('table-primary');
                    $first.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                }
                break;
            }
            case 37: // left arrow (select previous row or first if no selection)
            case 38: // up arrow
            {
                const $prev = $selection.prev();
                const $last = $table.find('tr:last');
                if ($selection.length === 0) {
                    $table.find('tr:first').addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                } else if ($prev.length) {
                    $selection.removeClass('table-primary');
                    $prev.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                } else if ($last.length) {
                    $selection.removeClass('table-primary');
                    $last.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                }
                break;
            }
            case 39: // right arrow (select next row or first if no selection)
            case 40: // down arrow
            {
                const $next = $selection.next();
                const $first = $table.find('tr:first');
                if ($selection.length === 0) {
                    $table.find('tr:first').addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                } else if ($next.length) {
                    $selection.removeClass('table-primary');
                    $next.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                } else if ($first.length) {
                    $selection.removeClass('table-primary');
                    $first.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                }
                break;
            }
            case 46: // delete (delete selected row)
            {
                const $link = $selection.find('.btn-delete');
                if ($link.length) {
                    $link[0].click();
                    e.preventDefault();
                }
                break;
            }
        }
    };
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // handle table
    const $table = $('#calculations');
    if ($table.length && $table.find('tr').length) {
        // select
        let $selection = $table.find('tr.table-primary');
        if ($selection.length === 0) {
            $selection = $table.find('tr:first').addClass('table-primary');
        }
        $selection.scrollInViewport();

        // enable tooltips
        $table.tooltip({
            selector: '.has-tooltip',
            customClass: 'tooltip-danger'
        });

        // function to select a row from children
        const selectRow = function () {
            $.hideDropDownMenus();
            $table.find('tr.table-primary').removeClass('table-primary');
            $(this).parents('tr').addClass('table-primary');
        };

        // select row when drop-down menu is displayed
        $table.on('click', '[data-toggle="dropdown"]', selectRow);

        // initialize context menu
        $table.initContextMenu('#calculations tbody tr td:not(.d-print-none)', selectRow);

        // remove separators
        $('#calculations .dropdown-menu').removeSeparators();

        // handle key down event
        const handler = createKeydownHandler($table);
        $(document).on('keydown', handler);
        $(':input').on('focus', function () {
            $(document).off('keydown', handler);
        }).on('blur', function () {
            $(document).on('keydown', handler);
        });
    }

    // enable tooltips for calculations by state or by month
    $('.card-body-tooltip').tooltip({
        selector: '.has-tooltip',
        customClass: 'tooltip-danger'
    });

    // handle user restrict checkbox
    const $restrict = $('#restrict');
    if ($restrict.length) {
        $restrict.on('input', function () {
            $restrict.updateTimer(onRestrictInput, 450, $restrict);
        });
    }
}(jQuery));
