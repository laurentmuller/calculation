/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Display a notification.
 *
 * @param {string} type - the message type.
 * @param {string} title - the message title.
 * @param {Object} options - the custom options.
 */
function notify(type, title, options) {
    'use strict';

    // get random text
    const url = $('#position').data('random');
    $.getJSON(url, function (response) {
        if (response.result) {
            const message = '<p class="m-0 p-0">{0}</p>'.format(response.content);
            Toaster[type](message, title, options);
        } else {
            Toaster.danger("Impossible d'afficher une notification.", $('.card-title').text(), options);
        }
    }).fail(function () {
        Toaster.danger("Impossible d'afficher une notification.", $('.card-title').text(), options);
    });
}

/**
 * Display a random notification.
 */
function random() {
    'use strict';
    const button = $('.btn-notify').toArray().randomElement();
    $(button).trigger('click'); //.focus();
}

/**
 * Document ready function
 */
(function ($) {
    'use strict';

    // default options
    const options = $('#flashbags').data();
    options.onHide = function (settings) {
        window.console.log(JSON.stringify(settings, null, '   '));
    };

    // handle notify button click
    $('.btn-notify').on('click', function () {
        // options
        options.icon = $('#icon').isChecked();
        options.title = $('#title').isChecked();
        options.position = $('#position').val();
        options.timeout = $('#timeout').intVal();
        options.progress = $('#progress').intVal();
        options.autohide = $('#autohide').isChecked();
        options.displayClose = $('#close').isChecked();
        options.displaySubtitle = $('#subtitle').isChecked();

        // moved position ?
        const oldPosition = $('#position').data('position');
        if (oldPosition && oldPosition !== options.position) {
            Toaster.removeContainer();
        }
        $('#position').data('position', options.position);

        // notify
        const type = $(this).data('type');
        const title = options.title ? $(this).text() : null;
        notify(type, title, options);

        // update checkbox style
        const className = 'custom-control-input control-option custom-control-' + type;
        $(":checkbox.custom-control-input").attr('class', className);
    });

    // set default values
    $('.btn-default').on('click', function () {
        let changed = false;
        $('.card-body [data-default]').each(function () {
            const $this = $(this);
            const value = $this.data('default');
            if ($this.is(':checkbox')) {
                if ($this.isChecked() !== value) {
                    $this.setChecked(value);
                    changed = true;
                }
            } else {
                if ($this.val() !== String(value)) {
                    $this.val(value);
                    changed = true;
                }
            }
        });
        if (changed) {
            random();
        }
    });

    // display a notification when a value change
    $('#position, #timeout, #progress, .control-option').on('input', function () {
        random();
        const $this = $(this);
        if ($this.is(('#autohide'))) {
            $('#close').toggleDisabled(!$this.isChecked());
        }
    });

    // first notification
    random();
}(jQuery));
