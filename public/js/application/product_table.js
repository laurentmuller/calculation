/**! compression tag for ftp-deployment */

/* globals clearSearch */

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
const noConflictSearch = clearSearch;
clearSearch = function ($element, table, callback) { // jshint ignore:line
    'use strict';

    const $category = $('#category');
    if ($category.val() !== '') {
        $('.dropdown-category:first').updateCategory();
        table.column(6).search('');
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

    // initialize category search column
    const table = $('#data-table').dataTable().api();
    table.initSearchColumn($('#category'), 6, $('#button-category'));

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
