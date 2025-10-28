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
         * @param {JQuery|any} $dialog
         * @param {string} content
         */
        displayDialogContent: function ($dialog, content) {
            const $this = $(this);
            // content
            $dialog.find('.modal-data').html(content);
            // show modal dialog
            $dialog.one('hidden.bs.modal', function () {
                $this.scrollInViewport().trigger('focus');
            }).modal('show');
            // clipboard
            $dialog.find('.btn-copy').copyClipboard();
        },

        /**
         * @param {Event} e
         * @param {string} attribute
         * @param {string} modalSelector
         */
        loadModalContent: function (e, attribute, modalSelector) {
            e.preventDefault();
            const $this = $(e.currentTarget);
            const $dialog = $(modalSelector);
            const content = $this.data(attribute);
            if (content) {
                $this.displayDialogContent($dialog, content);
                return;
            }
            const url = $this.attr('href');
            $.getJSON(url, function (response) {
                const content = response.result && response.content;
                if (content) {
                    $this.data(attribute, content);
                    $this.displayDialogContent($dialog, content);
                    return;
                }
                $this.fadeOut();
                notifyWarning(response.message || $dialog.data('found-error'));
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
        }).on('click', '.modal-data a', function (e) {
            e.preventDefault();
            window.open(e.target.href, '_blank');
        }).on('click', '.link-license', function (e) {
            $(this).loadModalContent(e, 'license', '#license-modal');
        }).on('click', '.link-package', function (e) {
            $(this).loadModalContent(e, 'package', '#package-modal');
        });
    });
}(jQuery));
