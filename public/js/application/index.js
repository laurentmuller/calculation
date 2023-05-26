/**! compression tag for ftp-deployment */

/* globals MenuBuilder, Toaster  */

/**
 * -------------- jQuery extensions --------------
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
 * @param {jQuery} $restrict - the checkbox to handle.
 */
function onRestrictInput($restrict) {
    'use strict';
    const newValue = $restrict.isChecked();
    const oldValue = $restrict.data('value');
    if (newValue.toString() !== oldValue.toString()) {
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
 * @param {jQuery} $table - the table to handle.
 * @return {(function(*): void)|*}
 */
function createKeydownHandler($table) {
    'use strict';
    /** @param {KeyboardEvent} e */
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
        switch (e.key) {
            case 'Enter':  // edit selected row
            {
                const $link = $selection.find('.btn-default');
                if ($link.length) {
                    $link[0].click();
                    e.preventDefault();
                }
                break;
            }
            case 'End':// select last row
            {
                const $last = $table.find('tr:last');
                if (!$selection.is($last)) {
                    $selection.removeClass('table-primary');
                    $last.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                }
                break;
            }
            case 'Home': // select first row
            {
                const $first = $table.find('tr:first');
                if (!$selection.is($first)) {
                    $selection.removeClass('table-primary');
                    $first.addClass('table-primary').scrollInViewport();
                    e.preventDefault();
                }
                break;
            }
            case 'ArrowLeft':
            case 'ArrowUp': // select previous row or first if no selection
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
            case 'ArrowRight':
            case 'ArrowDown': // select next row or first if no selection
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
            case 'Delete': // delete selected row
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
 * Hide a panel.
 *
 * @param {Event} e - the source event.
 */
function hidePanel(e) {
    'use strict';
    e.preventDefault();
    const $this = $(e.currentTarget);
    const $card = $this.parents('.card');
    const title = $card.find('.card-title').text();
    const url = $card.data('path');
    $.post(url, function (message) {
        $card.fadeOut(200, function () {
            $card.remove();
            Toaster.info(message, title);
        });
    });
}

/**
 * Update the displayed calculations.
 *
 * @param {Event} e - the source event.
 */
function updateCounter(e) {
    'use strict';
    const $this = $(e.currentTarget);
    if ($this.hasClass('active')) {
        return;
    }

    const count = $this.data('value');
    const $parent = $this.parents('.dropdown');
    const $card = $this.parents('.card');
    const title = $card.find('.card-title').text();
    $parent.find('.dropdown-toggle').text(count);

    const url = $parent.data('path');
    $.post(url, {'count': count}, function (message) {
        window.location.reload();
        Toaster.info(message, title);
    });
}

/**
 * Select a row.
 *
 * @param {jQuery} $source - the source to find row to select.
 * @param {boolean} hideMenus - true to hide displayed drop-down menus.
 */
function selectRow($source, hideMenus) {
    'use strict';
    $('#calculations tr.table-primary').removeClass('table-primary');
    $source.closest('tr').addClass('table-primary');
    if (hideMenus) {
        $.hideDropDownMenus();
    }
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
            customClass: 'tooltip-danger',
            selector: '.has-tooltip',
            html: true
        });

        // function to select the row
        const contextMenuShow = function () {
            selectRow($(this), true);
        };
        $table.on('click', '[data-bs-toggle="dropdown"]', function () {
            selectRow($(this), false);
        });

        // initialize context menu
        $table.initContextMenu('#calculations tbody tr td:not(.d-print-none)', contextMenuShow);

        // remove separators
        $('#calculations .dropdown-menu').removeSeparators();

        // handle key down event
        const $body = $('body');
        const handler = createKeydownHandler($table);
        const selector = ':input, .btn, .dropdown-item, .rowlink-skip';
        $body.on('focus', selector, function () {
            $body.off('keydown', handler);
        }).on('blur', selector, function () {
            $body.on('keydown', handler);
        }).on('keydown', handler);
    }

    // enable tooltips for calculations by state or by month
    $('.body-tooltip').tooltip({
        customClass: 'tooltip-danger',
        selector: '.has-tooltip',
        html: true
    });

    // handle user restrict checkbox
    const $restrict = $('#restrict');
    if ($restrict.length) {
        $restrict.on('input', function () {
            $restrict.updateTimer(onRestrictInput, 450, $restrict);
        });
    }

    // hide panels
    const $panels = $('.hide-panel');
    if ($panels.length) {
        $panels.on('click', function (e) {
            hidePanel(e);
        });
    }

    // update displayed calculations
    const $counters = $('.dropdown-item-counter');
    if ($counters.length) {
        $counters.on('click', function (e) {
            updateCounter(e);
        });
        const $parent = $counters.parents('.dropdown');
        $parent.on('shown.bs.dropdown', function () {
            $parent.find('.active').trigger('focus');
        });
    }
}(jQuery));
