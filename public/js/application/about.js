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
        const title = $('.card-title:first').text().trim();
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
        },

        /**
         *  @param {string} [content]
         */
        displayPackage: function (content) {
            const $link = $(this);
            if (content) {
                $link.data('package', content);
            } else {
                content = $link.data('package');
            }
            $('#package-content').html(content);

            // modal dialog
            const $dialog = $('#package-modal');
            $dialog.one('hidden.bs.modal', function () {
                $link.scrollInViewport().trigger('focus');
            }).modal('show');

            // clipboard
            $('#package-modal .btn-copy').copyClipboard({
                title: $('#modal-package-title').text().trim(),
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

        $accordion.on('hide.bs.modal', '.modal', function () {
            $('.modal .pre-scrollable').scrollTop(0);
        }).on('click', '#license-modal a', function (e) {
            e.preventDefault();
            window.open(e.target.href, '_blank');
        }).on('click', '.link-license', function (e) {
            e.preventDefault();
            const $this = $(this);
            if ($this.data('content')) {
                $this.displayLicense();
                return;
            }
            const $modal = $('#license-modal');
            const url = $this.attr('href');
            if (!url || '#' === url) {
                $this.fadeOut();
                notifyWarning($modal.data('load-error'));
                return;
            }
            $.getJSON(url, function (response) {
                if (response.result && response.content) {
                    $this.displayLicense(response.content);
                    return;
                }
                $this.fadeOut();
                notifyWarning(response.message || $modal.data('found-error'));
            });
        }).on('click', '.link-package', function (e) {
            e.preventDefault();
            const $this = $(this);
            if ($this.data('package')) {
                $this.displayPackage();
                return;
            }
            const $modal = $('#package-modal');
            const url = $this.attr('href');
            if (!url || '#' === url) {
                $this.fadeOut();
                notifyWarning($modal.data('load-error'));
                return;
            }
            $.getJSON(url, function (response) {
                if (response.result && response.content) {
                    $this.displayPackage(response.content);
                    return;
                }
                $this.fadeOut();
                notifyWarning(response.message || $modal.data('found-error'));
            });
        });
    });
}(jQuery));
