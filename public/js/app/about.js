/**! compression tag for ftp-deployment */

/**
 * Add link to PHP image
 */
function updatePhp($element) {
    'use strict';

    // find cell
    const $cell = $element.find('table tbody tr td:first');
    if ($cell.length) {
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

/**
 * Ready function
 */
$(function () {
    'use strict';

    // extensions
    $.fn.extend({
        loadContent: function () {
            const $this = $(this);
            const url = $this.data('url');
            $.getJSON(url).done(function (data) {
                if (data.result) {
                    $this.html(data.content);
                    if ($this.is('#php')) {
                        updatePhp($this);
                    }
                } else {
                    $this.showError();
                }
            }).fail(function () {
                $this.showError();
            });
        },

        showError: function () {
            const content = $("#configuration").data("error");
            const html = "<i class='fas fa-lg fa-exclamation-triangle mr-2'></i>" + content;
            $(this).find(".alert:first").addClass("alert-danger").html(html);
        }
    });

    // load content asynchrone
    const handler = function () {
        $(this).off('show.bs.collapse', handler).loadContent();
    };
    $('#symfony, #php, #mysql').on('show.bs.collapse', handler);

    // update style
    $('.collapse').on('show.bs.collapse', function () {
        $(this).parents(".card").find(".card-header").removeClass("card-collapse");
    }).on('hidden.bs.collapse', function () {
        $(this).parents(".card").find(".card-header").addClass("card-collapse");
    });

});
