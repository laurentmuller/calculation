/**! compression tag for ftp-deployment */

/* global bootstrap */

function getPopovers() {
    'use strict';
    return document.querySelectorAll('.content [data-custom-html]');
}

function disposePopover() {
    'use strict';
    getPopovers().forEach(function (element) {
        const popover = bootstrap.Popover.getInstance(element);
        if (popover) {
            popover.dispose();
        }
    });
}

function createPopover() {
    'use strict';
    const options = {
        html: true,
        trigger: 'hover',
        placement: 'top',
        customClass: 'popover-table popover-w-100',
        title: function (e) {
            return e.dataset.customTitle;
        },
        content: function (e) {
            return $(e.dataset.customHtml);
        }
    };
    getPopovers().forEach(function (element) {
        bootstrap.Popover.getOrCreateInstance(element, options);
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
