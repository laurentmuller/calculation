/**! compression tag for ftp-deployment */

/* globals clearSearch */

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
            $('#state').val($this.data('id'));
            $('#button-state').text($this.text());
            $('.dropdown-state').removeClass('active');
            $this.addClass('active');
        }
        return $this;
    }
});

/**
 * Override clear search
 */
const noConflictSearch = clearSearch;
clearSearch = function ($element, table, callback) { // jshint ignore:line
    'use strict';

    const $state = $('#state');
    if ($state.length && $state.val() !== '') {
        $('.dropdown-state:first').updateState();
        table.column(8).search('');
        if (!noConflictSearch($element, table, callback)) {
            table.draw();
            return false;
        }
        return true;
    } else {
        return noConflictSearch($element, table, callback);
    }
};

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
        table.initSearchColumn($state, 8, $('#button-state'));

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
