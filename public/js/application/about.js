/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // JQuery extensions
    $.fn.extend({
        loadContent: function () {
            const $this = $(this);
            const url = $this.data('url');
            if (url) {
                $.getJSON(url).done(function (response) {
                    if (response.result) {
                        $this.html(response.content);
                        if ($this.is('#php')) {
                            $this.updatePhp();
                        }
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
            const content = $("#configuration").data("error");
            const html = "<i class='fas fa-lg fa-exclamation-triangle mr-2'></i>" + content;
            $(this).find(".alert:first").addClass("alert-danger").html(html);
        },

        updatePhp: function () {
            const $cell = $(this).findExists('table:first tbody:first tr:first td:first');
            if ($cell) {
                const $link = $('<a/>', {
                    'href': 'https://www.php.net/',
                    'rel': 'noopener noreferrer',
                    'target': '_blank'
                });
                $link.appendTo($cell);
                $cell.children().appendTo($link);
                $link.find('h1').addClass('text-body');
            }
        }
    });

    // load content on show
    $('.card-body.collapse').one('show.bs.collapse', function () {
        $(this).loadContent();
    });
}(jQuery));
