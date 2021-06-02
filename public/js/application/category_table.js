/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * The group column index
 */
const GROUP_COLUMN = 6;
/**
 * -------------- Functions extensions --------------
 */
$.fn.extend({
    /**
     * Update the group selection.
     *
     * @return {jQuery} The jQuery element for chaining.
     */
    updateGroup: function () {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            const id = $this.data('id');
            $('#group').val(id);
            if (id) {
                $('#button-group').text($this.text());
            } else {
                $('#button-group').text($('#button-group').data('default'));
            }
            $('.dropdown-group').removeClass('active');
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
        const $group = $('#group');
        if ($group.val() !== '') {
            $('.dropdown-group:first').updateGroup();
            table.column(GROUP_COLUMN).search('');
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

    // initialize group search column
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#group'), GROUP_COLUMN, $('#button-group'));

    // handle drop-down group
    $('.dropdown-group').on('click', function () {
        $(this).updateGroup();
        $('#group').trigger('input');
    }).handleKeys();

    // focus group menu
    $('#dropdown-menu-group').on('shown.bs.dropdown', function () {
        $('.dropdown-group.active').focus();
    });

    // select group
    const group = $('#group').val();
    if (group) {
        const selector = '.dropdown-group[data-id="' + group + '"]';
        $(selector).updateGroup();
    }
}(jQuery));
