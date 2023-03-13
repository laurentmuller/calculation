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

function handleUrl() {
    'use strict';
    const handler = function (value) {
        window.open(value, '_blank');
    };
    handleInput('#customer_url', '.input-group-url', handler);
}

/**
 * @param {string} inputId the input selector.
 * @param {string} groupId the group selector.
 * @param {function} callback the function to call.
 */
function handleInput(inputId, groupId, callback) {
    'use strict';
    const $input = $(inputId);
    const $group = $input.parents('.input-group').find(groupId);
    if (!$input.length || !$group.length) {
        return;
    }
    const handler = function (e) {
        e.preventDefault();
        if ($input.valid() && $input.val().trim()) {
            try {
                callback($input.val().trim());
            } catch (error) {
                $group.off('click', handler).removeClass('cursor-pointer');
                window.console.error(error);
            }
        }
        $input.trigger('focus');
    };
    $input.on('input', function () {
        $group.removeClass('cursor-pointer').off('click', handler);
        if ($input.valid() && $input.val().trim()) {
            $group.addClass('cursor-pointer').on('click', handler);
        }
    }).trigger('input');
}

function handlePhone() {
    'use strict';
    const handler = function (value) {
        window.location.href = 'tel:' + value;
    };
    handleInput('#customer_phone', '.input-group-phone', handler);
}

// function handleFax() {
//     'use strict';
//     const handler = function (value) {
//         window.location.href = 'fax:' + value;
//     };
//     handleInput('#customer_fax', '.input-group-fax', handler);
// }

function handleEmail() {
    'use strict';
    const handler = function (value) {
        window.location.href = 'mailto:' + value;
    };
    handleInput('#customer_email', '.input-group-email', handler);
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
        inline: true, rules: {
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
    handlePhone();
    handleEmail();
    // handleFax();
    handleUrl();
}(jQuery));
