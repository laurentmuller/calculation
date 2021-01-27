/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * -------------- Functions extensions --------------
 */
$.fn.extend({
    /**
     * Update the channel selection.
     * 
     * @return {jQuery} The jQuery element for chaining.
     */
    updateChannel: function () {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            const id = $this.data('id');
            $('#channel').val(id);
            if (id) {
                $('#button-channel').text($this.text());    
            } else {
                $('#button-channel').text($('#button-channel').data('default'));
            }
            $('.dropdown-channel').removeClass('active');
            $this.addClass('active');
        }
        return $this;
    },

    /**
     * Update the level selection.
     * 
     * @return {jQuery} The jQuery element for chaining.
     */
    updateLevel: function () {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            const id = $this.data('id');
            $('#level').val(id);
            if (id) {
                $('#button-level').text($this.text());    
            } else {
                $('#button-level').text($('#button-level').data('default'));
            }
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
        $('.dropdown-channel:first').updateChannel();
        $('.dropdown-level:first').updateLevel();

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

    // focus channel
    $('#dropdown-menu-channel').on('shown.bs.dropdown', function () {
        $('.dropdown-channel.active').focus();
    });

    // focus level
    $('#dropdown-menu-level').on('shown.bs.dropdown', function () {
        $('.dropdown-level.active').focus();
    });

    // select channel
    const channel = $('#channel').val();
    if (channel) {
        const selector = '.dropdown-channel[data-id="' + channel + '"]';
        $(selector).updateChannel();
    }

    // select level
    const level = $('#level').val();
    if (level) {
        const selector = '.dropdown-level[data-id="' + level + '"]';
        $(selector).updateLevel();
    }
}(jQuery));
