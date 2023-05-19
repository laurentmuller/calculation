/**! compression tag for ftp-deployment */

/**
 * -------------- jQuery extensions --------------
 */
$.fn.extend({

    /**
     * Toggle total cells class.
     *
     * @param {string} oldClass the old class.
     * @param {string} newClass the new class.
     * @return {jQuery} The jQuery element for chaining.
     */
    toggleCell(oldClass, newClass) {
        'use strict';
        return $(this).each(function () {
            const $that = $(this);
            const firstClass = oldClass.split(' ')[0];
            if ($that.hasClass(firstClass)) {
                $that.toggleClass(oldClass + ' ' + newClass);
            }
        });
    }
});

/**
 * Toggle the cell highlight enablement.
 *
 * @param {jQuery} $source - The highlight checkbox.
 * @param {jQuery} $table - The table to update.
 * @param {boolean} save - true to save value to the session.
 * @return {jQuery} The jQuery source element for chaining.
 */
function toggleHighlight($source, $table, save) {
    'use strict';
    const checked = $source.isChecked();
    const highlight = $table.data('cell-highlight');
    if (checked) {
        if (!highlight) {
            $table.cellhighlight({
                rowSelector: 'tr:not(.skip)',
                cellSelector: 'td:not(.not-hover), th:not(.not-hover)',
                highlightHorizontal: 'table-primary',
                highlightVertical: 'table-primary'
            }).on('cellhighlight.mouseenter', function (_e, {horizontal, vertical}) {
                $.each($.merge(horizontal, vertical), function () {
                    $(this).toggleCell('bg-success text-white', 'table-cell');
                });
            }).on('cellhighlight.mouseleave', function (_e, {horizontal, vertical}) {
                $.each($.merge(horizontal, vertical), function () {
                    $(this).toggleCell('table-cell', 'bg-success text-white');
                });
            });
        } else {
            highlight.enable();
        }
    } else {
        if (highlight) {
            highlight.disable();
        }
    }

    // save to session
    if (save) {
        const url = $('#pivot').data('session');
        const data = {
            name: 'highlight',
            value: checked
        };
        $.post(url, data);
    }
    return $source;
}

/**
 * Toggle the popover enablement.
 *
 * @param {jQuery} $source - The popover checkbox.
 * @param {jQuery} $selector - The popover elements.
 * @param {boolean} save - true to save value to the session.
 * @return {jQuery} The jQuery source element for chaining.
 */
function togglePopover($source, $selector, save) {
    'use strict';
    const checked = $source.isChecked();
    const enabled = $source.data('enabled');
    if (checked) {
        if (enabled) {
            $selector.popover('enable');
        } else {
            $selector.popover({
                html: true,
                trigger: 'hover',
                placement: 'top',
                customClass: 'popover-primary popover-w-100',
                fallbackPlacements: ['top', 'bottom', 'right', 'left'],
                content: function (e) {
                    const content = $(e).data('bs-html');
                    return $(content);
                }
            });
            $source.data('enabled', true);
        }
    } else {
        if (enabled) {
            $selector.popover('disable');
        }
    }

    // save to session
    if (save) {
        const url = $('#pivot').data('session');
        const data = {
            name: 'popover',
            value: checked
        };
        $.post(url, data);
    }
    return $source;
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // get elements
    const $table = $('#pivot');
    const $popover = $('#popover');
    const $highlight = $('#highlight');
    const $selector = $('[data-bs-toggle="popover"]');

    // popover
    if ($popover.isChecked()) {
        togglePopover($popover, $selector, false);
    }
    $popover.on('input', function () {
        togglePopover($(this), $selector, true);
    });

    // highlight
    if ($highlight.isChecked()) {
        toggleHighlight($highlight, $table, false);
    }
    $highlight.on('input', function () {
        toggleHighlight($(this), $table, true);
    });

    // hover
    $selector.on('mouseenter', function () {
        $(this).addClass('text-hover');
    }).on('mouseleave', function () {
        $(this).removeClass('text-hover');
    });
}(jQuery));
