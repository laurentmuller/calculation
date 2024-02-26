/**! compression tag for ftp-deployment */

/* globals ClipboardJS, Toaster */


/**
 * Notify a message.
 *
 * @param {string} type - the message type.
 * @param {string} message - the message content.
 */
function notify(type, message) {
    'use strict';
    const title = $('#modal-license-title').text();
    Toaster.notify(type, message, title);
}

/**
 * Handle the error copy event.
 */
function onCopyError(e) {
    'use strict';
    e.clearSelection();
    const $button = $(e.trigger);
    const $modal = $('#license-modal');
    const message = $modal.data('copy-error');
    $modal.modal('hide');
    $button.remove();
    notify(Toaster.NotificationTypes.WARNING, message);
}

/**
 * Handle success copy event.
 */
function onCopySuccess(e) {
    'use strict';
    e.clearSelection();
    const $modal = $('#license-modal');
    const message = $modal.data('copy-success');
    $modal.modal('hide');
    notify(Toaster.NotificationTypes.SUCCESS, message);
}

/**
 * @param {jQuery} $row
 * @param {string} [content]
 * @param {boolean} [html]
 */
const displayLicense = function ($row, content, html) {
    'use strict';
    if (content) {
        $row.data('content', content);
        $row.data('html', html);
    } else {
        content = $row.data('content');
        html = $row.data('html');
    }
    if (html) {
        $('#license-content-html').html(content).removeClass('d-none');
        $('#license-content-text').addClass('d-none');
    } else {
        $('#license-content-text').html(content).removeClass('d-none');
        $('#license-content-html').addClass('d-none');
    }
    $('#license-modal').one('hidden.bs.modal', function () {
        $row.find('.link-license').scrollInViewport().trigger('focus');
    }).modal('show');
};

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $configuration = $('#configuration');

    // jQuery extensions
    $.fn.extend({
        loadContent: function () {
            const $this = $(this);
            if ($this.data('loaded')) {
                return;
            }
            $this.data('loaded', true);
            /** @type {string} */
            const url = $this.data('url');
            if (url) {
                $.getJSON(url, function (response) {
                    if (response.result && response.content) {
                        $this.html(response.content);
                    } else {
                        $this.showError();
                    }
                }).fail(function () {
                    $this.showError();
                });
            } else {
                $this.showError();
            }
        },

        showError: function () {
            const content = $configuration.data('error');
            const html = `<i class='fas fa-lg fa-exclamation-triangle me-2'></i>${content}`;
            $(this).find('.alert:first').toggleClass('alert-danger py-0 py-3').html(html);
        },

        /** @param {string} title */
        updateTitle: function (title) {
            $(this).prev('div').find('[data-bs-toggle]').attr('title', title);
        }
    });

    // .card-body
    const $accordion = $('#aboutAccordion');
    $accordion.on('shown.bs.collapse', function (e) {
        const $this = $(e.target);
        const $content = $this.find('.collapse-content');
        if ($content.length) {
            $content.loadContent();
        } else if (!$this.data('error')) {
            $this.data('error', true).showError();
        }
        $this.updateTitle($configuration.data('collapse'));
    }).on('hidden.bs.collapse', function (e) {
        $(e.target).updateTitle($configuration.data('expand'));
    });

    // license
    let clipboard = null;
    $accordion.on('hide.bs.modal', '#license-modal', function () {
        $(this).find('.pre-scrollable').scrollTop(0);
    });
    $accordion.on('click', '.btn-copy-license', function () {
        if (!clipboard) {
            clipboard = new ClipboardJS('.btn-copy-license');
            clipboard.on('success', function (e) {
                onCopySuccess(e);
            }).on('error', function (e) {
                onCopyError(e);
            });
        }
    });
    $accordion.on('click', 'tr[data-license] .link-license', function (e) {
        e.preventDefault();
        const $row = $(this).parents('tr');
        if ($row.data('content')) {
            displayLicense($row);
            return;
        }
        const file = $row.data('license');
        const url = $('#license-modal').data('url');
        $.getJSON(url, {'file': file}, function (response) {
            if (response.result) {
                displayLicense($row, response.content, response.html);
            } else {
                notify(Toaster.NotificationTypes.WARNING, response.message);
            }
        });
    });

}(jQuery));
