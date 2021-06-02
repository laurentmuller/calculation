/**! compression tag for ftp-deployment */

/* globals clearSearch */

/**
 * The category column index
 */
const CATEGORY_COLUMN = 6;

/**
 * -------------- Functions extensions --------------
 */
$.fn.extend({
    /**
     * Update the category selection.
     *
     * @return {jQuery} The jQuery element for chaining.
     */
    updateCategory: function () {
        'use strict';

        const $this = $(this);
        if ($this.length) {
            const id = $this.data('id');
            $('#category').val(id);
            if (id) {
                $('#button-category').text($this.text());
            } else {
                $('#button-category').text($('#button-category').data('default'));
            }
            $('.dropdown-category').removeClass('active');
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
        const $category = $('#category');
        if ($category.val() !== '') {
            $('.dropdown-category:first').updateCategory();
            table.column(CATEGORY_COLUMN).search('');
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

    // initialize category search column
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#category'), CATEGORY_COLUMN, $('#button-category'));

    // handle drop-down category
    $('.dropdown-category').on('click', function () {
        $(this).updateCategory();
        $('#category').trigger('input');
    }).handleKeys();

    // focus category menu
    $('#dropdown-menu-category').on('shown.bs.dropdown', function () {
        $('.dropdown-category.active').focus();
    });

    // select category
    const category = $('#category').val();
    if (category) {
        const selector = '.dropdown-category[data-id="' + category + '"]';
        $(selector).updateCategory();
    }
}(jQuery));
