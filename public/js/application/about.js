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
        const title = $('#main .card-title').text();
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
                title: $('#modal-license-title').text(),
                copySuccess: function (e) {
                    $(e.trigger).parents('#license-modal').modal('hide');
                },
                copyError: function (e) {
                    $(e.trigger).parents('#license-modal').modal('hide');
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
                notifyWarning(message);
                return;
            }
            $.getJSON(url, {'file': file}, function (response) {
                if (response.result && response.content) {
                    $row.displayLicense(response.content);
                    return;
                }
                $this.remove();
                const message = response.message || $modal.data('load-error');
                notifyWarning(message);
            });
        });
    });
}(jQuery));
