/**! compression tag for ftp-deployment */

/* globals Toaster */


/**
 * Notify a message.
 *
 * @param {string} type - the message type.
 * @param {string} message - the message content.
 */
function notify(type, message) {
    'use strict';
    const title = $('.card-title:first').text();
    Toaster.notify(type, message, title);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $accordion = $('#aboutAccordion');
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
        },

        /** @param {string} [content] */
        displayLicense: function (content) {
            const $row = $(this);
            if (content) {
                $row.data('content', content);
            } else {
                content = $row.data('content');
            }
            $('#license-content').html(content);
            $('#license-modal').one('hidden.bs.modal', function () {
                $row.find('.link-license').scrollInViewport().trigger('focus');
            }).modal('show');

            // clipboard
            $('#license-modal .btn-copy').copyClipboard({
                title: $('.card-title:first').text(),
                copySuccess: function (e) {
                    $(e.trigger).parents('#license-modal').modal('hide');
                },
                copyError: function (e) {
                    $(e.trigger).parents('#license-modal').modal('hide');
                }
            });
        }
    });

    // .card-body
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
    $accordion.on('hide.bs.modal', '#license-modal', function () {
        $('#license-modal .pre-scrollable').scrollTop(0);
    }).on('click', '#license-modal a', function (e) {
        e.preventDefault();
        window.open(e.target.href, '_blank');
    }).on('click', 'tr[data-license] .link-license', function (e) {
        e.preventDefault();
        const $this = $(this);
        const $row = $this.parents('tr');
        if ($row.data('content')) {
            $row.displayLicense();
            return;
        }
        const $modal = $('#license-modal');
        const file = $row.data('license');
        const url = $modal.data('url');
        if (!file || !url) {
            $this.remove();
            const message = $modal.data('load-error');
            notify(Toaster.NotificationTypes.WARNING, message);
            return;
        }
        $.getJSON(url, {'file': file}, function (response) {
            if (response.result && response.content) {
                $row.displayLicense(response.content);
            } else {
                $this.remove();
                const message = response.message || $modal.data('load-error');
                notify(Toaster.NotificationTypes.WARNING, message);
            }
        });
    });

}(jQuery));
