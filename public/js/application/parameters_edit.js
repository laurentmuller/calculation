/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Reset widgets to default values (if any).
 */
function setDefaultValues() {
    'use strict';

    $('#edit-form :input:not(button)[data-default], :checkbox[data-default]').each(function () {
        const $this = $(this);
        const value = $this.data('default');
        if ($this.is(':checkbox')) {
            $this.prop('checked', value);
        } else {
            $this.val(value);
        }
    });
}

/**
 * Display a notification.
 */
function displayNotification() {
    'use strict';

    // get random text
    const url = $("form").data("random");
    $.getJSON(url, function (response) {
        if (response.result) {
            // content
            const content = '<p class="m-0 p-0">' + response.content + '</p>';

            // position
            const $position = $("#parameters_message_position");
            const oldPosition = $position.data('position');
            const newPosition = $position.val();
            $position.data('position', newPosition);

            // type
            const last = $position.data('type');
            const types = Object.values(Toaster.NotificationTypes);
            const type = types.randomElement(last);
            $position.data('type', type);

            // timeout
            const timeout = $('#parameters_message_timeout').intVal();

            // title
            let title = $('.card-title').text();
            if (!$('#parameters_message_title').isChecked()) {
               title = null;
            }

            // display
            const icon = $('#parameters_message_icon').isChecked();
            const displayClose = $('#parameters_message_close').isChecked();
            const displaySubtitle = $('#parameters_message_sub_title').isChecked();
            const displayProgress = $('#parameters_message_progress').isChecked();

            // options
            const options = $.extend({}, $("#flashbags").data(), {
                icon: icon,
                timeout: timeout,
                position: newPosition,
                displayClose: displayClose,
                displayProgress: displayProgress,
                displaySubtitle: displaySubtitle,
            });

            // clear (if required) and display
            if (oldPosition && oldPosition !== newPosition) {
                Toaster.removeContainer();
            }
            Toaster.notify(type, content, title, options);

        } else {
            const title = $('form').data('title');
            const message = $('form').data('failure');
            Toaster.danger(message, title, $("#flashbags").data());
        }
    }).fail(function () {
        const title = $('form').data('title');
        const message = $('form').data('failure');
        Toaster.danger(message, title, $("#flashbags").data());
    });
}

/**
 * Display the customer URL in a blank window.
 */
function displayUrl($url) {
    'use strict';
    if ($url.valid() && $url.val().trim()) {
        const url = $url.val().trim();
        // eslint-disable-next-line
        window.open(url, '_blank');
    } else {
        $url.focus();
    }
}

/**
 * Display the customer mail to.
 */
function displayEmail($email) {
    'use strict';
    if ($email.valid() && $email.val().trim()) {
        const email = 'mailto:' + $email.val().trim();
        // eslint-disable-next-line
        window.location.href = email;
    } else {
        $email.focus();
    }
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#edit-form').initValidator({
        inline: true,
        rules: {
            'parameters[customer_url]': {
                url: true
            }
        }
    });

    // toogle icons and titles
    $('.toggle-icon').on('show.bs.collapse', function () {
        const $prev = $(this).prev();
        $prev.find('i').toggleClass('fa-caret-left fa-caret-down');
        $prev.find('a').attr('title', $prev.data('hide'));
    }).on('hide.bs.collapse', function () {
        const $prev = $(this).prev();
        $prev.find('i').toggleClass('fa-caret-left fa-caret-down');
        $prev.find('a').attr('title', $prev.data('show'));
    }).on('shown.bs.collapse', function () {
        if ($(this).find('.is-invalid').length === 0) {
            $(this).find(':input:first').focus();
        }
    });

    // add handlers
    $('.btn-default').on('click', function (e) {
        e.preventDefault();
        setDefaultValues();
    });
    $('.btn-notify').on('click', function (e) {
        e.preventDefault();
        displayNotification();
    });

    const $url = $('#parameters_customer_url');
    const $urlGroup = $url.parents('.input-group').find('.input-group-url');
    if ($url.length && $urlGroup.length) {
        const handler = function (e) {
            e.preventDefault();
            displayUrl($url);
        };
        $url.on('input', function () {
            $urlGroup.off('click', handler);
            $urlGroup.removeClass('cursor-pointer');
            if ($url.valid()) {
                $urlGroup.on('click', handler);
                $urlGroup.addClass('cursor-pointer');
            }
        });
        $url.trigger('input');
    }

    const $email = $('#parameters_customer_email');
    const $emailGroup = $email.parents('.input-group').find('.input-group-email');
    if ($email.length && $emailGroup.length) {
        const handler = function (e) {
            e.preventDefault();
            displayEmail($email);
        };
        $email.on('input', function () {
            $emailGroup.off('click', handler);
            $emailGroup.removeClass('cursor-pointer');
            if ($email.valid()) {
                $emailGroup.on('click', handler);
                $emailGroup.addClass('cursor-pointer');
            }
        });
        $email.trigger('input');
    }
}(jQuery));
