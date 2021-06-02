/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * The state column index
 */
const STATE_COLUMN = 8;

/**
 * -------------- Functions extensions --------------
 */
$.fn.extend({
    /**
     * Update the state selection.
     *
     * @return {jQuery} The jQuery element for chaining.
     */
    updateState: function () {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            const id = $this.data('id');
            $('#state').val(id);
            if (id) {
                $('#button-state').text($this.text());
            } else {
                $('#button-state').text($('#button-state').data('default'));
            }
            $('.dropdown-state').removeClass('active');
            $this.addClass('active');
        }
        return $this;
    }
});

/**
 * Override clear search
 */
clearSearch = (function ($parent) { // jshint ignore:line
    'use strict';
    return function ($element, table) {
        const $state = $('#state');
        if ($state.length && $state.val() !== '') {
            $('.dropdown-state:first').updateState();
            table.column(STATE_COLUMN).search('');
            if (!$parent.apply(this, arguments)) {
                table.draw();
                return false;
            }
            return true;
        } else {
            return $parent.apply(this, arguments);
        }
    };
})(clearSearch);

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // get state
    const $state = $('#state');
    if ($state.length) {
        // initialize state search column
        const table = $('#data-table').dataTable().api();
        table.initSearchColumn($state, STATE_COLUMN, $('#button-state'));

        // handle drop-down state
        $('.dropdown-state').on('click', function () {
            $(this).updateState();
            $('#state').trigger('input');
        }).handleKeys();

        // focus state menu
        $('#dropdown-menu-state').on('shown.bs.dropdown', function () {
            $('.dropdown-state.active').focus();
        });

        // select state
        const state = $state.val();
        if (state) {
            const selector = '.dropdown-state[data-id="' + state + '"]';
            $(selector).updateState();
        }
    }
}(jQuery));
