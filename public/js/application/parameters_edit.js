/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Reset widgets to default values (if any).
 */
function setDefaultValues() {
    'use strict';

    $('#edit-form :input:not(button), :checkbox').each(function () {
        const $this = $(this);
        const value = $this.data('default');
        if (!$.isUndefined(value) && value !== '') {
            if ($this.is(':checkbox')) {
                $this.prop('checked', value);
            } else {
                $this.val(value);
            }
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
            const title = $('.card-title').text();

            // display sub-title
            const displaySubtitle = $('#parameters_message_sub_title').intVal();

            // options
            const options = $.extend({}, $("#flashbags").data(), {
                timeout: timeout,
                position: newPosition,
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
function displayUrl() {
    'use strict';

    const $element = $('#parameters_customer_url');
    if ($element.valid()) {
        const $url = $element.val();
        // eslint-disable-next-line
        window.open($url, '_blank');
    } else {
        $element.focus();
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
    $('.btn-url').on('click', function (e) {
        e.preventDefault();
        displayUrl();
    });
}(jQuery));
