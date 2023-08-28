/**! compression tag for ftp-deployment */

/* globals MenuBuilder, Toaster */

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
        const $elements = $(this).parents('.row-item:first').find('.dropdown-menu').children();
        const builder = new MenuBuilder({
            classSelector: 'btn-default'
        });
        return builder.fill($elements).getItems();
    }
});

/**
 * @param {jQuery} $input
 * @return {boolean}
 */
function isDefaultValue($input) {
    'use strict';
    const oldValue = $input.data('value');
    const newValue = $input.isChecked();
    return oldValue === newValue;
}

/**
 * Handle the restriction and custom checkboxes.
 */
function updateView() {
    'use strict';
    const $custom = $('#custom');
    const $restrict = $('#restrict');
    if (isDefaultValue($custom) && isDefaultValue($restrict)) {
        return;
    }

    const params = {
        custom: $custom.isChecked(),
        restrict: $restrict.isChecked(),
    };
    const id = $('.row-item.table-primary').data('id');
    if (id) {
        params.id = id;
    }

    const root = $('.card-footer-content').data('url');
    const url = root + '?' + $.param(params);
    window.location.assign(url);
}

/**
 * Update the selection.
 *
 * @param {jQuery} $oldSelection - the old selection.
 * @param {jQuery} $newSelection - the new selection.
 */
function toggleSelection($oldSelection, $newSelection) {
    'use strict';
    if (!$oldSelection.is($newSelection)) {
        $oldSelection.removeClass('table-primary');
        $newSelection.addClass('table-primary').scrollInViewport();
    }
}

/**
 * Creates the key down handler for the calculation table.
 *
 * @param {jQuery} $parent - the parent to handle.
 * @return {(function(*): void)|*}
 */
function createKeydownHandler($parent) {
    'use strict';
    /** @param {KeyboardEvent} e */
    return function (e) {
        // special key?
        if ((e.keyCode === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
            return;
        }

        // rows?
        const $rows = $parent.find('.row-item');
        if ($rows.length === 0) {
            return;
        }

        const $selection = $parent.find('.row-item.table-primary');
        /*eslint no-lone-blocks: "off"*/
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
            case 'End': // select last row
            {
                const $last = $parent.find('.row-item:last');
                toggleSelection($selection, $last);
                e.preventDefault();
                break;
            }
            case 'Home': // select first row
            {
                const $first = $parent.find('.row-item:first');
                toggleSelection($selection, $first);
                e.preventDefault();
                break;
            }
            case 'ArrowLeft':
            case 'ArrowUp': // select previous row or last if no selection
            {
                const index = $selection.length ? $rows.index($selection) - 1 : $rows.length - 1;
                //const index = $rows.index($selection) - 1;
                const $prev = $rows.eq(index);
                const $last = $rows.eq($rows.length - 1);
                if ($prev.length) {
                    toggleSelection($selection, $prev);
                    e.preventDefault();
                } else if ($last.length) {
                    toggleSelection($selection, $last);
                    e.preventDefault();
                }
                break;
            }
            case 'ArrowRight':
            case 'ArrowDown': // select next row or first if no selection
            {
                const index = $selection.length ? $rows.index($selection) + 1 : 0;
                const $next = $rows.eq(index);
                const $first = $rows.eq(0);
                if ($next.length) {
                    toggleSelection($selection, $next);
                    e.preventDefault();
                } else if ($first.length) {
                    toggleSelection($selection, $first);
                    e.preventDefault();
                }
                break;
            }
            case 'Delete': // delete selected row
            {
                const $link = $selection.find('.btn-delete');
                if ($link.length) {
                    e.preventDefault();
                    $link[0].click();
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
 */
function selectRow($source) {
    'use strict';
    const $oldSelection = $('#calculations .row-item.table-primary');
    const $newSelection = $source.closest('.row-item');
    toggleSelection($oldSelection, $newSelection);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    // handle table
    const $calculations = $('#calculations');
    if ($calculations.length && $calculations.find('.row-item').length) {
        // select
        let $selection = $calculations.find('.row-item.table-primary');
        if ($selection.length === 0) {
            $selection = $calculations.find('.row-item:first').addClass('table-primary');
        }
        $selection.scrollInViewport();

        // handle table events and context menu
        $calculations.on('mousedown', '.row-item', function (e) {
            if (e.button === 0) {
                selectRow($(this));
            } else if (e.button === 2) {
                $.hideDropDownMenus();
            }
        }).on('click', '.row-item [data-bs-toggle="dropdown"]', function () {
            selectRow($(this));
        }).initContextMenu('.row-item td:not(.context-menu-skip),.row-item div:not(.context-menu-skip)', function () {
            selectRow($(this));
        });

        // remove separators
        $('#calculations .dropdown-menu').removeSeparators();

        // handle key down event
        const $body = $('body');
        const handler = createKeydownHandler($calculations);
        const selector = ':input, .btn, .dropdown-item, .rowlink-skip, .modal';
        $body.on('focus', selector, function () {
            $body.off('keydown', handler);
        }).on('blur', selector, function () {
            $body.on('keydown', handler);
        }).on('keydown', handler);
    }

    // enable tooltips
    $('.card-body').tooltip({
        customClass: 'tooltip-danger',
        selector: '.has-tooltip',
        html: true
    });

    // handle checkbox options
    const $options = $('#restrict, #custom');
    if ($options.length) {
        $options.on('input', function () {
            $(this).updateTimer(updateView, 450);
        });
    }

    // handle hide panels
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
