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
            const html = "<i class='fas fa-lg fa-exclamation-triangle mr-2'></i>" + content;
            $(this).find(".alert:first").addClass("alert-danger").html(html);
        }
    });

    // update link title on toggle
    const $collapse = $('.card-body.collapse');
    $collapse.on('shown.bs.collapse', function () {
        const $link = $(this).parents('.card').find('.stretched-link');
        $link.attr('title', $configuration.data('collapse'));
    }).on('hidden.bs.collapse', function () {
        const $link = $(this).parents('.card').find('.stretched-link');
        $link.attr('title', $configuration.data('expand'));
    });

    // load content on first show
    $collapse.one('show.bs.collapse', function () {
        $(this).loadContent();
    });

}(jQuery));
