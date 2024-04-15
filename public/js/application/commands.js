/**! compression tag for ftp-deployment */

function disposePopover() {
    'use strict';
    $('.content [data-content').popover('dispose');
}

function createPopover() {
    'use strict';
    $('.content [data-content').popover({
        html: true,
        trigger: 'hover',
        placement: 'top',
        customClass: 'popover-table popover-w-100',
        content: function (e) {
            const $content = $(e).data('content');
            return $($content);
        }
    });
}

/**
 * Load the given command.
 *
 * @param {string } name the command name to load.
 */
function loadContent(name) {
    'use strict';
    const url = $('#command').data('url').replace('query', name);
    $.get(url, function (data) {
        if (data.result) {
            $('.content').html(data.content).fadeIn();
            const url = new URL(location);
            url.searchParams.set('name', name);
            window.history.pushState({}, '', url);
            updateExecute();
            createPopover();
        } else {
            $('.content').fadeOut();
        }
    });
}

function updateExecute() {
    'use strict';
    const $command = $('#command');
    const $execute = $('.btn-execute');
    const href = $execute.data('url').replace('query', $command.val());
    $execute.attr('href', href);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $command = $('#command');
    const callback = () => {
        loadContent($command.val());
    };
    $command.on('input', function () {
        disposePopover();
        $command.createTimer(callback, 350);
    }).trigger('focus');

    updateExecute();
    createPopover();
}(jQuery));
