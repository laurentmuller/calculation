/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Gets the active page.
 * @return {JQuery<HTMLElement>|null} the active page or null if none.
 */
function getActivePage() {
    'use strict';
    const $source = $('#parent_accordion .collapse.show');
    return $source.length ? $source : null;
}

/**
 * Reset widgets to default values (if any).
 * @param {JQuery} [$source] the active page or null to reset all values.
 */
function setDefaultValues($source) {
    'use strict';
    $source = $source || $('#edit-form');
    $source.find(':input:not(button)[data-default], :checkbox[data-default]').each(function () {
        const $this = $(this);
        const value = $this.data('default');
        if ($this.is(':checkbox')) {
            $this.prop('checked', value);
        } else {
            $this.val(value);
        }
    });

    // special case for radio inputs
    $source.find('.form-group[data-default]:has(:radio)').each(function () {
        const $this = $(this);
        const value = $this.data('default');
        $this.find(`:radio[value="${value}"]`).setChecked(true);
    });
}

/**
 * Update default button state.
 */
function updateVisibleButton() {
    'use strict';
    let disabled = true;
    const $button = $('.btn-default-visible');
    const $source = getActivePage();
    if ($source) {
        const selector = '[data-default]:not([data-default=""])';
        disabled = $source.find(selector).length === 0;
    }
    $button.toggleDisabled(disabled);
}

/**
 * Display a notification.
 */
function displayNotification() {
    'use strict';
    // get random text
    let title = $('.card-title').text();
    const data = $("#flashes").data();
    const url = $("form").data("random");
    $.getJSON(url, function (response) {
        if (response.result && response.content) {
            // content
            const content = '<p class="m-0 p-0">' + response.content + '</p>';
            // type
            const types = Object.values(Toaster.NotificationTypes);
            const type = types.randomElement();
            // title
            if (!$('#message_title').isChecked()) {
                title = null;
            }
            // options
            const options = $.extend({}, data, {
                icon: $('#message_icon').isChecked(),
                position: $("#message_position").val(),
                timeout: $('#message_timeout').intVal(),
                progress: $('#message_progress').intVal(),
                displayClose: $('#message_close').isChecked(),
                displaySubtitle: $('#message_sub_title').isChecked(),
            });
            Toaster.notify(type, content, title, options);
        } else {
            const message = $('form').data('failure');
            Toaster.danger(message, title, data);
        }
    }).fail(function () {
        const message = $('form').data('failure');
        Toaster.danger(message, title, data);
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
        window.location.href = 'mailto:' + $email.val().trim();
    } else {
        $email.focus();
    }
}


/**
 * Ready function
 */
(function ($) {
    'use strict';
    // numbers
    $('#default_product_quantity').inputNumberFormat();

    // validation
    $('#edit-form').initValidator({
        inline: true,
        rules: {
            'customer_url': {
                url: true
            }
        }
    });

    // add handlers
    $('.btn-default-all').on('click', function (e) {
        e.preventDefault();
        setDefaultValues();
    });
    $('.btn-default-visible').on('click', function (e) {
        e.preventDefault();
        const $source = getActivePage();
        if ($source) {
            setDefaultValues($source);
        }
    });
    $('.btn-notify').on('click', function (e) {
        e.preventDefault();
        displayNotification();
    });

    // toggle titles
    $('.toggle-icon').on('show.bs.collapse', function () {
        const $link = $(this).prev();
        $link.attr('title', $link.data('hide'));
    }).on('hide.bs.collapse', function () {
        const $link = $(this).prev();
        $link.attr('title', $link.data('show'));
    }).on('shown.bs.collapse', function () {
        const $this = $(this);
        if ($this.find('.is-invalid').length === 0) {
            $this.find(':input:first').trigger('focus');
        }
        updateVisibleButton();
    });
    updateVisibleButton();

    const $url = $('#customer_url');
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

    const $email = $('#customer_email');
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
