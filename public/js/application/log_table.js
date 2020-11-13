/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * -------------- Functions extensions --------------
 */
$.fn.extend({
    /**
     * Update the channel selection.
     * 
     * @param {String}
     *            text - the text to use for the button or null to copy the
     *            selected drop-down item.
     * @return {jQuery} The jQuery element for chaining.
     */
    updateChannel: function (text) {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            $('#channel').val($this.data('id'));
            $('#button-channel').text(text || $this.text());
            $('.dropdown-channel').removeClass('active');
            $this.addClass('active');
        }
        return $this;
    },

    /**
     * Update the level selection.
     * 
     * @param {String}
     *            text - the text to use for the button or null to copy the
     *            selected drop-down item.
     * @return {jQuery} The jQuery element for chaining.
     */
    updateLevel: function (text) {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            $('#level').val($this.data('id'));
            $('#button-level').text(text || $this.text());
            $('.dropdown-level').removeClass('active');
            $this.addClass('active');
        }
        return $this;
    }
});

/**
 * Override clear search
 */
const noConflict = clearSearch;
clearSearch = function ($element, table, callback) { // jshint ignore:line
    'use strict';

    const $channel = $('#channel');
    const $level = $('#level');
    if ($channel.val() || $level.val()) {
        // update buttons
        $('.dropdown-channel:first').updateChannel($('#button-channel').prop('title'));
        $('.dropdown-level:first').updateLevel($('#button-level').prop('title'));

        // clear
        table.column(2).search('');
        table.column(3).search('');

        if (!noConflict($element, table, callback)) {
            table.draw();
            return false;
        }
        return true;
    } else {
        return noConflict($element, table, callback);
    }
};

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize search columns
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#channel'), 2, $('#button-channel'));
    table.initSearchColumn($('#level'), 3, $('#button-level'));

    // handle drop-down channel
    $('.dropdown-channel').on('click', function () {
        $(this).updateChannel();
        $('#channel').trigger('input');
    }).handleKeys();

    // handle drop-down level
    $('.dropdown-level').on('click', function () {
        $(this).updateLevel();
        $('#level').trigger('input');
    }).handleKeys();

    // select channel
    const channel = $('#channel').val();
    if (channel) {
        const selector = '.dropdown-channel[data-id="' + channel + '"]';
        $(selector).updateChannel();
    }

    // select level
    const level = $('#level').val();
    if (channel) {
        const selector = '.dropdown-level[data-id="' + level + '"]';
        $(selector).updateLevel();
    }
}(jQuery));
