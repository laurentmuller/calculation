/**! compression tag for ftp-deployment */


/**
 * jQuery extensions.
 */
$.fn.extend({
    
    getDataId: function() {
        'use strict';
        const $this = $(this);
        const id = Number.parseInt($this.data('value'), 10);
        if (!Number.isNaN(id) && id !== 0) {
            return id;
        }
        return null;
    },
    
    setDataId(id, $selection) {
        'use strict';
        const $this = $(this);
        $this.data('value', id);
        if (id) {
            $this.text($selection.text());
        } else {
            $this.text($this.attr('title'));
        }
    },
    
    initDropdown: function() {
        'use strict';
        const $this = $(this);
        const $items = $this.next('.dropdown-menu').find('.dropdown-item');
        if ($items.length) {
            $items.on('click', function() {
                const $item = $(this);
                const newValue = $item.getDataId();
                const oldValue = $this.getDataId();
                if (newValue !== oldValue) {
                    $items.removeClass('active');
                    $item.addClass('active');
                    $this.setDataId(newValue, $item);
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
    
    // handle state selection
    const $state = $('#button-state'); 
    if ($state.length) {
        $state.initDropdown().on('input', function() {
            const id = $(this).getDataId();
            $table.refresh({
                query: {
                    stateId: id
                }
            });
        });    
    }    
        
    // handle category selection
    const $category = $('#button-category');
    if ($category.length) {
        $category.initDropdown().on('input', function() {
            const id = $(this).getDataId();
            $table.refresh({
                query: {
                    categoryId: id
                }
            });
        });    
    }

    // initialize table
    const options = {
        queryParams: function (params) {
            const categoryId = $category.getDataId();
            if (categoryId) {
                params.categoryId = categoryId;
            }
            const stateId = $state.getDataId();
            if (stateId) {
                params.stateId = stateId;
            }
            return params;
        },
    };
    $table.initBootstrapTable(options);
     
    // handle clear search
    $('[name ="clearSearch"]').on('click', function () {
        const isState = $state.getDataId() !== null;
        const isCategory = $category.getDataId() !== null;
        const isSearch = $table.getSearchText().length > 0;
        
        // refresh?
        if ((isState || isCategory) && !isSearch) {
            // reset
            $state.setDataId(0);
            $category.setDataId(0);
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

    // create context menu
    const rowSelector = $table.getOptions().rowSelector;    
    const ctxSelector =  rowSelector + ' td:not(.d-print-none)';
    const show = function () {
        $('.dropdown-menu.show').removeClass('show');
    };
    $table.initContextMenu(ctxSelector, show);

    // update UI
    $('input.search-input').attr('type', 'text');
    $('.fixed-table-pagination').appendTo('.card-footer');
    $('.fixed-table-toolbar').addClass('d-print-none');
    $('button[name ="toggle"]').insertBefore('#button_other_actions');
    $('[name ="clearSearch"] .fa.fa-trash').toggleClass('fa fa-trash fas fa-eraser');
    
}(jQuery));
