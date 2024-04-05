/**! compression tag for ftp-deployment */

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
}(jQuery));
