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
            Toaster.danger("Impossible d'afficher une notification.", 'Erreur', options);
        }
    }).fail(function () {
        Toaster.danger("Impossible d'afficher une notification.", 'Erreur', options);
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
        // type
        const type = $(this).data('type');

        // position
        const newPosition = $('#position').val();
        const oldPosition = $('#position').data('position');
        const clear = oldPosition && oldPosition !== newPosition;
        $('#position').data('position', newPosition);
        options.position = newPosition;

        // title
        const title = $('#title').isChecked() ? $(this).text() : null;

        // subtitle
        options.displaySubtitle = $('#subtitle').isChecked();

        // icon
        options.icon = $('#icon').isChecked();

        // close button
        options.closeButton = $('#close').isChecked();

        // timeout
        options.timeout = $('#timeout').intVal();

        // auto-hide
        options.autohide = $('#autohide').isChecked();

        // notify
        notify(type, title, options, clear);

        // update checkbox style
        const classname = 'custom-control-input custom-control-' + type;
        $(":checkbox.custom-control-input").attr('class', classname);
    });

    // random notification
    const button = $('.btn-form').toArray().randomElement();
    $(button).trigger('click').focus();
}(jQuery));
