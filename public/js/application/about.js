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
            const html = `<i class='fas fa-lg fa-exclamation-triangle me-2'></i>${content}`;
            $(this).find(".alert:first").toggleClass('py-0 alert-danger py-3').html(html);
        },

        /**
         * @param {string} title - the title to set.
         */
        updateTitle: function (title) {
            $(this).prev('div').find('[data-bs-toggle]').attr('title', title);
        }
    });
    // .card-body
    $('#aboutAccordion').on('shown.bs.collapse', function (e) {
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
}(jQuery));
