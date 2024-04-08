/**! compression tag for ftp-deployment */

/* global bootstrap */

function getPopovers() {
    'use strict';
    return document.querySelectorAll('.content [data-custom-html]');
}

function hidePopover() {
    'use strict';
    getPopovers().forEach(function (element) {
        const popover = bootstrap.Popover.getInstance(element);
        if (popover) {
            popover.dispose();
        }
    });
}

function updatePopover() {
    'use strict';
    const options = {
        html: true,
        trigger: 'hover',
        placement: 'top',
        customClass: 'popover-table',
        title: function (e) {
            return e.dataset.customTitle;
        },
        content: function (e) {
            const template = document.createElement('template');
            template.innerHTML = e.dataset.customHtml;
            return template.content;
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
    hidePopover();
    const url = $('#command').data('url').replace('query', name);
    $.get(url, function (data) {
        if (data.result) {
            $('.content').html(data.content).fadeIn();
            const url = new URL(location);
            url.searchParams.set('name', name);
            window.history.pushState({}, '', url);
            updatePopover();
        } else {
            $('.content').fadeOut();
        }
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $command = $('#command');
    const callback = () => {
        const $selection = $command.getSelectedOption();
        if ($selection && $selection.length) {
            loadContent($selection.text());
        } else {
            $('.content').fadeOut();
        }
    };
    $command.on('change', function () {
        $command.createTimer(callback, 350);
    }).trigger('focus');

    // popover
    updatePopover();
}(jQuery));
