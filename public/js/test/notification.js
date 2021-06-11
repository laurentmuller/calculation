/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Display a notification.
 *
 * @param {string}
 *            type - the message type.
 * @param {string}
 *            title - the message title.
 * @param {Object}
 *            options - the custom options.
 * @param {boolean}
 *            clear - true to remove the existing container.
 */
function notify(type, title, options, clear) {
    'use strict';

    // get random text
    const url = $('#position').data('random');
    $.getJSON(url, function (response) {
        if (response.result) {
            // clear
            if (clear) {
                Toaster.removeContainer();
            }

            // message
            const message = '<p class="m-0 p-0">{0}</p>'.format(response.content);

            // display
            Toaster[type](message, title, options);
        } else {
            Toaster.danger("Impossible d'afficher une notification.", $('.card-title').text(), options);
        }
    }).fail(function () {
        Toaster.danger("Impossible d'afficher une notification.", $('.card-title').text(), options);
    });
}

/**
 * Document ready function
 */
(function ($) {
    'use strict';

    // default options
    const options = $('#flashbags').data();
    options.onHide = function (settings) {
        console.log(settings);
    };

    $('.btn-notify').on('click', function () {
        // position
        const newPosition = $('#position').val();
        const oldPosition = $('#position').data('position');
        $('#position').data('position', newPosition);

        // options
        options.position = newPosition;
        options.icon = $('#icon').isChecked();
        options.timeout = $('#timeout').intVal();
        options.closeButton = $('#close').isChecked();
        options.autohide = $('#autohide').isChecked();
        options.displaySubtitle = $('#subtitle').isChecked();

        const type = $(this).data('type');
        const title = $('#title').isChecked() ? $(this).text() : null;
        const clear = oldPosition && oldPosition !== newPosition;

        // notify
        notify(type, title, options, clear);

        // update checkbox style
        const classname = 'custom-control-input custom-control-' + type;
        $(":checkbox.custom-control-input").attr('class', classname);
    });

    // random notification
    const button = $('.btn-notify').toArray().randomElement();
    $(button).trigger('click').focus();
}(jQuery));
