/* globals Toaster */

(function ($) {
    'use strict';

    /**
     * Notify a warning message.
     *
     * @param {string} message the message to display.
     */
    function notifyWarning(message) {
        const type = Toaster.NotificationTypes.WARNING;
        const title = $('.card-title:first').text().trim();
        Toaster.notify(type, message, title);
    }

    // jQuery extensions
    $.fn.extend({
        /**
         * Load and replace the HTML content.
         */
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

        /**
         * Shows the loading error message.
         */
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
         * Update the toggle title.
         * @param {string} title the new title.
         */
        updateTitle: function (title) {
            $(this).prev('div').find('[data-bs-toggle]').attr('title', title);
        },

        /**
         * Display the modal dialog with the given content.
         * @param {JQuery|any} $dialog the modal dialog.
         * @param {string} content the HTML content to display
         */
        displayDialogContent: function ($dialog, content) {
            const $this = $(this);
            // content
            $dialog.find('.modal-data').html(content);
            // clipboard
            $dialog.find('.btn-copy').copyClipboard();
            // show modal dialog
            $dialog.one('hidden.bs.modal', function () {
                $this.trigger('focus');
            }).modal('show');
            //$dialog.modal('show');
        },

        /**
         * Load the content and display it in the modal dialog.
         * @param {Event} e the source event.
         * @param {string} attribute the attribute name.
         */
        loadModalContent: function (e, attribute) {
            e.preventDefault();
            const $this = $(e.currentTarget);
            const $dialog = $(`#${attribute}-modal`);
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
            $(this).loadModalContent(e, 'license');
        }).on('click', '.link-package', function (e) {
            $(this).loadModalContent(e, 'package');
        });
    });
}(jQuery));
