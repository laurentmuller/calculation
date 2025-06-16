/* globals Toaster */

(function ($) {
    'use strict';

    /**
     * Notify a warning message.
     *
     * @param {string} message
     */
    function notifyWarning(message) {
        const type = Toaster.NotificationTypes.WARNING;
        const title = $('#license-modal .modal-header').text().trim();
        Toaster.notify(type, message, title);
    }

    // jQuery extensions
    $.fn.extend({
        loadContent: function () {
            const $this = $(this);
            if ($this.data('loaded')) {
                return;
            }
            $this.data('loaded', true);
            const url = $this.data('url');
            if (!url) {
                $this.showError();
                return;
            }
            $.getJSON(url, function (response) {
                if (response.result && response.content) {
                    $this.html(response.content);
                } else {
                    $this.showError();
                }
            }).fail(function () {
                $this.showError();
            });
        },

        showError: function () {
            const $this = $(this);
            if ($this.data('error')) {
                return;
            }
            $this.data('error', true);
            const content = $('#configuration').data('error');
            const html = `<i class='fas fa-lg fa-exclamation-triangle me-2'></i>${content}`;
            $this.find('.alert:first').toggleClass('alert-danger py-0 py-3').html(html);
        },

        /**
         * @param {string} title
         */
        updateTitle: function (title) {
            $(this).prev('div').find('[data-bs-toggle]').attr('title', title);
        },

        /**
         *  @param {string} [content]
         */
        displayLicense: function (content) {
            const $link = $(this);
            if (content) {
                $link.data('content', content);
            } else {
                content = $link.data('content');
            }
            $('#license-content').html(content);

            // modal dialog
            const $dialog = $('#license-modal');
            $dialog.one('hidden.bs.modal', function () {
                $link.scrollInViewport().trigger('focus');
            }).modal('show');

            // clipboard
            $('#license-modal .btn-copy').copyClipboard({
                title: $('#modal-license-title').text().trim(),
                copySuccess: function () {
                    $dialog.modal('hide');
                },
                copyError: function () {
                    $dialog.modal('hide');
                }
            });
        }
    });

    /**
     * Ready function
     */
    $(function () {
        const $accordion = $('#aboutAccordion');
        const $configuration = $('#configuration');
        const expandTitle = $configuration.data('expand');
        const collapseTitle = $configuration.data('collapse');

        // .card-body
        $accordion.on('shown.bs.collapse', function (e) {
            const $target = $(e.target);
            /** @type {JQuery|HTMLElement|*} */
            const $content = $target.find('.collapse-content');
            if ($content.length) {
                $content.loadContent();
            } else {
                $target.showError();
            }
            $target.updateTitle(collapseTitle);
        }).on('hidden.bs.collapse', function (e) {
            $(e.target).updateTitle(expandTitle);
        });

        // license
        $accordion.on('hide.bs.modal', '#license-modal', function () {
            $('#license-modal .pre-scrollable').scrollTop(0);
        }).on('click', '#license-modal a', function (e) {
            e.preventDefault();
            window.open(e.target.href, '_blank');
        }).on('click', '.link-license[data-file]', function (e) {
            e.preventDefault();
            const $this = $(this);
            if ($this.data('content')) {
                $this.displayLicense();
                return;
            }
            const $modal = $('#license-modal');
            const file = $this.data('file');
            const url = $modal.data('url');
            if (!file || !url) {
                $this.fadeOut();
                const message = $modal.data('load-error');
                notifyWarning(message);
                return;
            }
            $.getJSON(url, {'file': file}, function (response) {
                if (response.result && response.content) {
                    $this.displayLicense(response.content);
                    return;
                }
                $this.fadeOut();
                const message = response.message || $modal.data('found-error');
                notifyWarning(message);
            });
        });
    });
}(jQuery));
