/**! compression tag for ftp-deployment */


/**
 * jQuery extension for Bootstrap tables, rows and cells.
 */
$.fn.extend({
    
    getCategoryId: function() {
        'use strict';
        const $this = $(this);
        const id = Number.parseInt($this.data('value'), 10);
        if (!Number.isNaN(id) && id !== 0) {
            return id;
        }
        return null;
    },
    
    setCategoryId(id, $selection) {
        'use strict';
        const $this = $(this);
        $this.data('value', id);
        if (id === 0) {
            $this.text($this.attr('title'));
        } else {
            $this.text($selection.text());
        }
    },
    
    getStateId: function() {
        'use strict';
        const $this = $(this);
        const id = Number.parseInt($this.data('value'), 10);
        if (!Number.isNaN(id) && id !== 0) {
            return id;
        }
        return null;
    },
    
    setStateId(id, $selection) {
        'use strict';
        const $this = $(this);
        $this.data('value', id);
        if (id === 0) {
            $this.text($this.attr('title'));
        } else {
            $this.text($selection.text());
        }
    },
        
    initDropdown: function() {
        'use strict';
        const $this = $(this);
        const defaultValue = $this.data('value');
        const $items = $this.next('.dropdown-menu').find('.dropdown-item');
        if ($items.length) {
            $items.on('click', function() {
                const $item = $(this);
                const newValue = $item.data('value');
                const oldValue = $this.data('value');
                if (newValue !== oldValue) {
                    $items.removeClass('active');
                    $item.addClass('active');
                    $this.data('value', newValue);
                    if (newValue === defaultValue) {
                        $this.text($this.attr('title') || $item.text());    
                    }
                    $this.trigger('input');
                }
            });
        }
        return $this;
    }
});

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // table
    const $table = $('#table-edit');

    // initialize
    const options = {
        queryParams: function (params) {
            const categoryId = $('#button-category').getCategoryId();
            if (categoryId) {
                params.categoryId = categoryId;
            }
            const stateId = $('#button-state').getStateId();
            if (stateId) {
                params.stateId = stateId;
            }
            return params;
        },
    };
    $table.initBootstrapTable(options);

    // update UI
    $('.fixed-table-pagination').appendTo('.card-footer');
    $('input.search-input').attr('type', 'text');
    $('.fa.fa-trash').toggleClass('fa fa-trash fas fa-eraser');
    $('.fixed-table-toolbar').addClass('d-print-none');

    // handle category selection
    // $('#button-category').initDropdown().on('input', function() {
    // const id = $(this).data('value')
    // const id = $(this).getCategoryId();
    // $table.refresh({
    // query: {
    // categoryId: id
    // }
    // });
    // });
    
     // handle category selection
     $('.dropdown-category').on('click', function () {
         const $this = $(this);
         const $category = $('#button-category');
         const newId = Number.parseInt($this.data('value'), 10);
         const oldId = $category.getCategoryId();
         if (newId !== oldId) {
             $('.dropdown-category').removeClass('active');
             $this.addClass('active');
             $category.setCategoryId(newId, $this);
             $table.refresh({
                 query: {
                     categoryId: newId
                 }
             });
         }
     });

     // handle state selection
     $('.dropdown-state').on('click', function () {
         const $this = $(this);
         const $state = $('#button-state');
         const newId = Number.parseInt($this.data('value'), 10);
         const oldId = $state.getStateId();
         if (newId !== oldId) {
             $('.dropdown-state').removeClass('active');
             $this.addClass('active');
             $state.setStateId(newId, $this);
             $table.refresh({
                 query: {
                     stateId: newId
                 }
             });
         }
     });
     
    // handle clear search
    $('[name ="clearSearch"]').on('click', function () {
        const isSearch = $table.getSearchText().length > 0;
        const isState = $('#button-state').getStateId() !== null;
        const isCategory = $('#button-category').getCategoryId() !== null;
        
        // refresh?
        if ((isState || isCategory) && !isSearch) {
            // reset
            $('#button-state').setStateId(0);
            $('#button-category').setCategoryId(0);
            $table.refresh({
                query: {
                    stateId: 0,
                    categoryId: 0
                }
            });
        }
        $('.search-input').focus();
    });

    // handle keys enablement
    $('.search-input, .btn, .btn-path, .dropdown-item, .page-link, .rowlink-skip').on('focus', function () {
        $table.disableKeys();
    }).on('blur', function () {
        $table.enableKeys();
    });

    // context menu
    const rowSelector = $table.getOptions().rowSelector;    
    const ctxSelector =  rowSelector + ' td:not(.d-print-none)';
    const show = function () {
        $('.dropdown-menu.show').removeClass('show');
    };
    $table.initContextMenu(ctxSelector, show);

}(jQuery));
