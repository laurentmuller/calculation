/** ! compression tag for ftp-deployment */

/**
 * Toogle the wholly enablement.
 */
function toggleHighlight() {
    'use strict';

    const $pivot = $('#pivot');
    const enabled = $('#highlight').isChecked();
    const data = $pivot.data("wholly");

    if (enabled) {
        if (!data) {
            $pivot.wholly({
                selection: 'td:not(".not-hover"), th:not(".not-hover")',
                highlightHorizontal: 'text-hover',
                highlightVertical: 'text-hover'
            });
        }
    } else {
        if (data) {
            data.destroy();
        }
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize
    const $pivot = $('#pivot');

    // bind events
    const selection = 'td:not(".not-hover"), th:not(".not-hover")';
    $pivot.on('mouseenter', selection, function () {
        $(this).addClass('text-hover');
    }).on('mouseleave', selection, function () {
        $(this).removeClass('text-hover');
    });

    // popover
    $('[data-toggle="popover"]').customPopover({
        html: true,
        type: 'primary',
        trigger: 'hover',
        placement: 'top',
        container: 'body',
        title: $('.card-title').html().replace(/<h1.*>.*?<\/h1>/ig, ''),
        content: function () {
            const html = $(this).data("html");
            return $(html);
        },
    });

    // wholly highlight
    $('#highlight').on('input', function () {
        toggleHighlight();
    });
});
