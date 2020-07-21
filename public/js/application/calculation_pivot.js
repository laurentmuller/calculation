/**! compression tag for ftp-deployment */

/**
 * JQuery extensions
 */
$.fn.extend({
    
    /**
     * Toogle total cells class.
     */
    toggleCell(oldClass, newClass) {
        'use strict';
                
        const $that = $(this);
        const firstClass = oldClass.split(' ')[0];
        if ($that.hasClass(firstClass)) {
            $that.toggleClass(oldClass + ' ' + newClass);
        }
        return $that;
    }
});
    
/**
 * Toogle the cell highlight enablement.
 * 
 * @param {JQuery}
 *            $source - The highlight checkbox.
 * @param {JQuery}
 *            $table - The table to update.
 * @param {boolean}
 *            save - true to save value to the session.
 */
function toggleHighlight($source, $table, save) {
    'use strict';

    const checked = $source.isChecked();
    const highlight = $table.data('cellhighlight');
    if (checked) {
        if (!highlight) {
            $table.cellhighlight({
                rowSelector: 'tr:not(.skip)',
                cellSelector: 'td:not(.not-hover), th:not(.not-hover)',
                highlightHorizontal: 'table-primary',
                highlightVertical: 'table-primary'
                    
            }).on('cellhighlight.mouseenter', function (e, { horizontal, vertical}) {
                $.each($.merge(horizontal, vertical), function() {
                    $(this).toggleCell('bg-success text-white', 'table-cell');
                });
            }).on('cellhighlight.mouseleave', function (e, { horizontal, vertical}) {
                $.each($.merge(horizontal, vertical), function() {
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
        const data =  { 
            name: 'highlight', 
            value: checked
        };
        $.post(url, data);
    }
}

/**
 * Toogle the popover enablement.
 * 
 * @param {JQuery}
 *            $source - The popover checkbox.
 * @param {JQuery}
 *            $selector - The popover elements.
 * @param {boolean}
 *            save - true to save value to the session.
 */
function togglePopover($source, $selector, save) {
    'use strict';

    const checked = $source.isChecked();
    const popover = $selector.data('bs.popover');
    if (checked) {
        if (popover) {
            $selector.popover('enable');
        } else {
            $selector.customPopover({
                html: true,
                type: 'primary',
                trigger: 'hover',
                placement: 'top',
                container: 'body',
                content: function () {
                    const body = $(this).data('body');
                    return $(body);
                }
            });
        }
    } else {
        if (popover) {
            $selector.popover('disable');
        }
    }
    
    // save to session
    if (save) {
        const url = $('#pivot').data('session');
        const data =  { 
            name: 'popover', 
            value: checked
        };
        $.post(url, data);
    }   
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    
    // get elements
    const $table = $('#pivot');
    const $popover = $('#popover');
    const $highlight = $('#highlight');
    const $selector = $('[data-toggle="popover"]');

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
});
