/**! compression tag for ftp-deployment */

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
            const url = $this.data('url');
            if (url) {
                $.getJSON(url).done(function (response) {
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
            const content = $configuration.data("error");
            const html = "<i class='fas fa-lg fa-exclamation-triangle me-2'></i>" + content;
            $(this).find(".alert:first").addClass("alert-danger").html(html);
        },

        /**
         * @param {string} title - the title to set.
         */
        updateTitle: function (title) {
            const $toggle = $(this).prev('.card-header').find('[data-bs-toggle]');
            $toggle.attr('title', title);
        }
    });

    $('#parent_accordion .collapse').on('shown.bs.collapse', function () {
        $(this).find('.collapse-content').loadContent();
        $(this).updateTitle($configuration.data('collapse'));
    }).on('hidden.bs.collapse', function () {
        $(this).updateTitle($configuration.data('expand'));
    });

}(jQuery));
