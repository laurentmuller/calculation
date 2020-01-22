/** ! compression tag for ftp-deployment */


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
            $that.removeClass(oldClass).addClass(newClass);
        }
    }
});
    
/**
 * Toogle the cell highlight enablement.
 * 
 * @param {JQuery}
 *            $source - The highlight checkbox.
 * 
 * @param {JQuery}
 *            $$table - The table to update.
 */
function toggleHighlight($source, $table) {
    'use strict';

    const data = $table.data('cellhighlight');
    if ($source.isChecked()) {
        if (!data) {
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
            data.enable();
        }
    } else {
        if (data) {
            data.disable();
        }
    }
}

/**
 * Toogle the popover enablement.
 * 
 * @param {JQuery}
 *            $source - The popover checkbox.
 * 
 * @param {JQuery}
 *            $selector - The popover elements.
 */
function togglePopover($source, $selector) {
    'use strict';

    const data = $selector.data('bs.popover');
    if ($source.isChecked()) {
        if (data) {
            $selector.popover('enable');
        } else {
            $selector.customPopover({
                html: true,
                type: 'primary',
                trigger: 'hover',
                placement: 'top',
                container: 'body',
                title: $('.card-title').html().replace(/<h1.*>.*?<\/h1>/ig, ''),
                content: function () {
                    const data = $(this).data('html');
                    return $(data);
                },
            });
        }
    } else {
        if (data) {
            $selector.popover('disable');
        }
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
        togglePopover($popover, $selector);
    }
    $popover.on('input', function () {
        togglePopover($(this), $selector);
    });

    // highlight
    if ($highlight.isChecked()) {
        toggleHighlight($highlight, $table);
    }
    $highlight.on('input', function () {
        toggleHighlight($(this), $table);
    });

    // selection
    const selection = 'td:not(.not-hover), th:not(.not-hover)';
    $table.on('mouseenter', selection, function () {
        $(this).addClass('text-hover');
    }).on('mouseleave', selection, function () {
        $(this).removeClass('text-hover');
    });
});
