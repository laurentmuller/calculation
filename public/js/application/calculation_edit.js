/**! compression tag for ftp-deployment */

/* globals updateErrors, sortable, Toaster, MenuBuilder  */

/**
 * -------------- The type ahead search helper --------------
 */
var SearchHelper = {

    /**
     * Initialize type ahead searches.
     * 
     * @return {SearchHelper} this instance for chaining.
     */
    init: function () {
        'use strict';

        this.initSearchCustomer();
        this.initSearchProduct();
        this.initSearchUnits();
        
        return this;
    },

    /**
     * Initialize the type ahead search customers.
     * 
     * @return {Typeahead} The type ahead instance.
     */
    initSearchCustomer: function () {
        'use strict';

        const $element = $('#calculation_customer');
        return $element.initSearch({
            url: $('#edit-form').data('search-customer'),
            error: $('#edit-form').data('error-customer')
        });
    },

    /**
     * Initialize the type ahead search products.
     * 
     * @return {Typeahead} The type ahead instance.
     */
    initSearchProduct: function () {
        'use strict';

        const $element = $('#item_search_input');
        return $element.initSearch({
            alignWidth: false,
            valueField: 'description',
            displayField: 'description',

            url: $('#edit-form').data('search-product'),
            error: $('#edit-form').data('error-unit'),

            onSelect: function (item) {
                // copy values
                $('#item_description').val(item.description);
                $('#item_unit').val(item.unit);
                $('#item_category').val(item.categoryId);
                $('#item_price').floatVal(item.price);
                $('#item_price').trigger('input');

                // clear
                $element.val('');// .data('typeahead').query = '';

                // select
                if (item.price) {
                    $('#item_quantity').selectFocus();
                } else {
                    $('#item_price').selectFocus();
                }
            }
        });
    },

    /**
     * Initialize the type ahead search product units.
     * 
     * @return {Typeahead} The type ahead instance.
     */
    initSearchUnits: function () {
        'use strict';

        const $element = $('#item_unit');
        return $element.initSearch({
            url: $('#edit-form').data('search-unit'),
            error: $('#edit-form').data('error-unit')
        });
    }
};

/**
 * -------------- The move rows handler --------------
 */
var MoveRowHandler = {

    /**
     * Initialize.
     */
    init: function () {
        'use strict';

        const that = this;
        $('#data-table-edit').on('click', '.btn-first-item', function (e) {
            e.preventDefault();
            that.moveFirst($(this).getParentRow());
        }).on('click', '.btn-up-item', function (e) {
            e.preventDefault();
            that.moveUp($(this).getParentRow());
        }).on('click', '.btn-down-item', function (e) {
            e.preventDefault();
            that.moveDown($(this).getParentRow());
        }).on('click', '.btn-last-item', function (e) {
            e.preventDefault();
            that.moveLast($(this).getParentRow());
        });
    },

    /**
     * Move a source row before or after the target row.
     * 
     * @param {jQuery}
     *            $source - the row to move.
     * @param {jQuery}
     *            $target - the target row.
     * @param {boolean}
     *            up - true to move before the target (up); false to move after
     *            (down).
     * 
     * @return {jQuery} - The moved row.
     */
    move: function ($source, $target, up) {
        'use strict';

        if ($source && $target) {
            if (up) {
                $source.insertBefore($target);
            } else {
                $source.insertAfter($target);
            }
            $source.swapIdAndNames($target).scrollInViewport().timeoutToggle('table-success');
        }
        return $source;
    },

    /**
     * Move a calculation item to the first position.
     * 
     * @param {jQuery}
     *            $row - the row to move.
     * 
     * @return {jQuery} - The parent row.
     */
    moveFirst: function ($row) {
        'use strict';

        const index = $row.index();
        if (index > 1 && $row.prev()) {
            const $target = $row.siblings(':nth-child(2)');
            return this.move($row, $target, true);
        }
        return $row;
    },

    /**
     * Move a calculation item to the last position.
     * 
     * @param {jQuery}
     *            $row - the row to move.
     * 
     * @return {jQuery} - The parent row.
     */
    moveLast: function ($row) {
        'use strict';

        const index = $row.index();
        const count = $row.siblings().length;
        if (index < count && $row.next()) {
            const $target = $row.siblings(':last');
            return this.move($row, $target, false);
        }
        return $row;
    },

    /**
     * Move up a calculation item.
     * 
     * @param {jQuery}
     *            $row - the row to move.
     * 
     * @return {jQuery} - The parent row.
     */
    moveUp: function ($row) {
        'use strict';

        const index = $row.index();
        if (index > 1 && $row.prev()) {
            const $target = $row.prev();
            return this.move($row, $target, true);
        }
        return $row;
    },

    /**
     * Move down a calculation item.
     * 
     * @param {jQuery}
     *            $row - the row to move.
     * 
     * @return {jQuery} - The parent row.
     */
    moveDown: function ($row) {
        'use strict';

        const index = $row.index();
        const count = $row.siblings().length;        
        if (index < count && $row.next()) {
            const $target = $row.next();
            return this.move($row, $target, false);
        }
        return $row;
    }
};

/**
 * -------------- The Application handler --------------
 */
var Application = {

    /**
     * Initialize application.
     * 
     * @return {Application} This application instance for chaining.
     */
    init: function () {
        'use strict';

        return this.initDragDrop(false).initMenus();
    },

    /**
     * Initialize the drag and drop.
     * 
     * @param {boolean}
     *            destroy - true to destroy the existing sortable.
     * @return {Application} This application instance for chaining.
     */
    initDragDrop: function (destroy) {
        'use strict';

        const that = this;
        const selector = '#data-table-edit tbody';
        const $bodies = $(selector);

        if (destroy) {
            // remove proxies
            $bodies.off('sortstart', that.dragStartProxy).off('sortupdate', that.dragStopProxy);
            sortable(selector, 'destroy');
        } else {
            // create proxies
            that.dragStartProxy = $.proxy(that.onDragStart, that);
            that.dragStopProxy = $.proxy(that.onDragStop, that);
        }

        // create
        sortable(selector, {
            items: 'tr:not(.drag-skip)',
            placeholderClass: 'table-primary',
            forcePlaceholderSize: false,
            acceptFrom: 'tbody'
        });

        // remove role attribute (aria)
        $('#data-table-edit tbody tr').removeAttr('role');

        // add handlers
        $bodies.on('sortstart', that.dragStartProxy).on('sortupdate', that.dragStopProxy);

        return that;
    },

    /**
     * Initialize the edit dialog.
     * 
     * @return {Application} This application instance for chaining.
     */
    initEditDialog: function () {
        'use strict';

        // already initialized?
        const that = this;
        if (that.dialogInitialized) {
            return that;
        }

        // dialog validator
        const options = {
            submitHandler: function () {
                if (that.$editingRow) {
                    that.onEditDialogSubmit();
                } else {
                    that.onAddDialogSubmit();
                }
            }
        };
        $('#item_form').initValidator(options);

        // dialog events
        $('#item_modal').on('show.bs.modal', function () {
            const key = that.$editingRow ? 'edit' : 'add';
            const title = $('#item_form').data(key);
            $('#dialog-title').html(title);
            if (that.$editingRow) {
                $('#item_search_row').hide();
                $('#item_delete_button').show();
            } else {
                $('#item_search_row').show();
                $('#item_delete_button').hide();
            }
        }).on('shown.bs.modal', function () {
            if ($('#item_price').attr('readonly')) {
                $('#item_reset_button').focus();
            } else if (that.$editingRow) {
                if ($('#item_price').isEmptyValue()) {
                    $('#item_price').selectFocus();
                } else {
                    $('#item_quantity').selectFocus();
                }
                that.$editingRow.addClass('table-primary');
            } else {
                $('#item_search_input').selectFocus();
            }
        }).on('hide.bs.modal', function () {
            $('#data-table-edit tbody tr').removeClass('table-primary');
        });

        // buttons
        $('#item_delete_button').on('click', function () {
            $('#item_modal').modal('hide');
            if (that.$editingRow) {
                const button = that.$editingRow.findExists('.btn-delete-item');
                if (button) {
                    that.removeItem(button);
                }
            }
        });

        // widgets
        $('#item_price').inputNumberFormat();
        $('#item_quantity').inputNumberFormat();

        // bind
        const proxy = $.proxy(that.updateItemLine, that);
        $('#item_price, #item_quantity').on('input', proxy);

        // ok
        that.dialogInitialized = true;
        return that;
    },

    /**
     * Initialize the draggable edit dialog.
     * 
     * @return {Application} This application instance for chaining.
     */
    initDragDialog: function() {
        'use strict';

        // already initialized?
        const that = this;
        if (that.dragInitialized) {
            return that;
        }
        
        // draggable edit dialog
        $('#item_modal .modal-header').on('mousedown', function(e) {
            // left button?
            if (event.which !== 1) {
                return;
            }
            
            // get elements
            const $draggable = $(this);            
            const $dialog = $draggable.closest('.modal-dialog');
            const $content = $draggable.closest('.modal-content');
            const $close = $draggable.find('.close');
            const $focused = $(':focus');
            
            // save values
            const startX = e.pageX - $draggable.offset().left;
            const startY = e.pageY - $draggable.offset().top;
            const footerHeight = $('.footer').outerHeight();
            const margin = Number.parseInt($dialog.css('margin-top'), 10);
            const windowWidth = window.innerWidth - margin;
            const windowHeight = window.innerHeight - margin - footerHeight;
            const right = windowWidth - $content.width();
            const bottom = windowHeight - $content.height();

            // update style
            $draggable.toggleClass('bg-primary text-white');
            $close.toggleClass('bg-primary text-white');
            
            $('body').on('mousemove.draggable', function(e) {
                // compute
                const left = Math.max(margin, Math.min(right, e.pageX - startX));
                const top = Math.max(margin, Math.min(bottom, e.pageY - startY));

                // move
                $dialog.offset({
                    left: left,
                    top: top
                });

            }).one('mouseup', function() {
                $('body').off('mousemove.draggable');
                $draggable.toggleClass('bg-primary text-white');
                $close.toggleClass('bg-primary text-white');
                if ($focused.length) {
                    $focused.focus();
                }
            });

            $draggable.closest('.modal').one('hide.bs.modal', function() {
                $('body').off('mousemove.draggable');
            }).one('hidden.bs.modal', function() {
                $dialog.removeAttr('style');
            });
        });
        
        // ok
        that.dragInitialized = true;
        return that;        
    },
    
    /**
     * Initialize group and item menus.
     * 
     * @return {Application} This application instance for chaining.
     */
    initMenus: function () {
        'use strict';

        const that = this;

        // adjust button
        $('.btn-adjust').on('click', function (e) {
            e.preventDefault();
            $(this).tooltip('hide');
            that.updateTotals(true);
        });

        // add item button
        $('#items-panel .card-header .btn-add-item').on('click', function (e) {
            e.preventDefault();
            that.showAddDialog($(this));
        });

        // sort calculation button
        $('.btn-sort-items').on('click', function (e) {
            e.preventDefault();
            that.sortCalculation();
        });

        // data table buttons
        $('#data-table-edit').on('click', '.btn-add-item', function (e) {
            e.preventDefault();
            that.showAddDialog($(this));
        }).on('click', '.btn-edit-item', function (e) {
            e.preventDefault();
            that.showEditDialog($(this));
        }).on('click', '.btn-delete-item', function (e) {
            e.preventDefault();
            that.removeItem($(this));
        }).on('click', '.btn-delete-category', function (e) {
            e.preventDefault();
            that.removeCategory($(this));            
        }).on('click', '.btn-delete-group', function (e) {
            e.preventDefault();
            that.removeGroup($(this));
        });

        return that;
    },

    /**
     * Format a value with 2 fixed decimals and grouping separator.
     * 
     * @param {Number}
     *            value - the value to format.
     * @returns {string} - the formatted value.
     */
    toLocaleString: function (value) {
        'use strict';

        // get value
        let parsedValue = parseFloat(value);
        if (isNaN(parsedValue)) {
            parsedValue = parseFloat(0);
        }

        // format
        let formatted = parsedValue.toLocaleString('de-CH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // replace grouping and separator
        const grouping = $('#edit-form').data('grouping');
        if (grouping) {
            formatted = formatted.replace(/’|'/g, grouping);
        }
        const decimal = $('#edit-form').data('decimal');
        if (decimal) {
            formatted = formatted.replace(/\./g, decimal);
        }

        return formatted;
    },

    /**
     * Update the buttons, the total and initialize the drag-drop.
     * 
     * @return {Application} This application instance for chaining.
     */
    updateAll: function() {
        'use strict';
        return this.updateButtons().updateTotals().initDragDrop(true);
    },
    
    /**
     * Update the total of the line in the item dialog.
     * 
     * @return {Application} This application instance for chaining.
     */
    updateItemLine: function () {
        'use strict';

        const that = this;
        const price = $('#item_price').floatVal();
        const quantity = $('#item_quantity').floatVal();
        const total = that.toLocaleString(price * quantity);
        $('#item_total').val(total);
        
        return that;
    },

    /**
     * Update the move up/down and sort buttons.
     * 
     * @return {Application} This application instance for chaining.
     */
    updateButtons: function () {
        'use strict';

        // run hover bodies
        let disabled = true;
        $('#data-table-edit tbody').each(function (index, element) {
            const $body = $(element);
            const $rows = $body.find('tr:not(:first)');
            const lastIndex = $rows.length - 1;

            // run over rows
            $rows.each(function (index, element) {
                const $row = $(element);
                const hideUp = index === 0;
                const hideDown = index === lastIndex;
                $row.find('.btn-first-item').toggleClass('d-none', hideUp);
                $row.find('.btn-up-item').toggleClass('d-none', hideUp);
                $row.find('.btn-down-item').toggleClass('d-none', hideDown);
                $row.find('.btn-last-item').toggleClass('d-none', hideDown);
                $row.find('.dropdown-divider:first').toggleClass('d-none', hideUp && hideDown);
            });
            if ($rows.length > 1) {
                disabled = false;  
            }
        });
        
        if (disabled) {
            const $head = $('#data-table-edit thead');
            if ($head.length > 1) {
                disabled = false;
            } else {
                $head.each(function () {
                    const $body = $(this).nextUntil('thead');
                    if ($body.length > 1) {
                        disabled = false;
                        return false;
                    }
                });     
            }
        }
        
        // update global sort
        $('.btn-sort-items').toggleClass('disabled', disabled);

        return this;
    },

    /**
     * Update the totals.
     * 
     * @param {boolean}
     *            adjust - true to adjust the user margin.
     * 
     * @return {Application} This application instance for chaining.
     */
    updateTotals: function (adjust) {
        'use strict';

        const that = this;

        // show or hide empty items
        $('#empty-items').toggleClass('d-none', $('#data-table-edit tbody').length !== 0);

        // validate user margin
        if (!$('#calculation_userMargin').valid()) {
            if ($('#user-margin-row').length === 0) {
                const $tr = $('<tr/>', {
                    'id': 'user-margin-row'
                });
                const $td = $('<td>', {
                    'class': 'text-muted',
                    'text': $('#edit-form').data('error-margin')
                });
                $tr.append($td);
                $('#totals-table tbody:first tr').remove();
                $('#totals-table tbody:first').append($tr);
            } else {
                $('#user-margin-row').removeClass('d-none');
            }
            $('.btn-adjust').attr('disabled', 'disabled').addClass('cursor-default');
            return that;
        }

        // abort
        if (that.jqXHR) {
            that.jqXHR.abort();
            that.jqXHR = null;
        }

        // parameters
        adjust = adjust || false;
        let data = $('#edit-form').serializeArray();
        if (adjust) {
            data.push({
                name: 'adjust',
                value: true
            });
        }

        // call
        const url = $('#edit-form').data('update');
        that.jqXHR = $.post(url, data, function (response) {
            // error?
            if (!response.result) {
                return that.disable(response.message);
            }

            // update content
            const $totalPanel = $('#totals-panel');
            if (response.body) {
                $('#totals-table > tbody').html(response.body);
                $totalPanel.fadeIn();
            } else {
                $totalPanel.fadeOut();
            }
            if (adjust && !isNaN(response.overall_margin)) {
                $('#calculation_userMargin').intVal(response.overall_margin).selectFocus();
            }
            if (response.overall_below) {
                $('.btn-adjust').removeAttr('disabled').removeClass('cursor-default');
            } else {
                $('.btn-adjust').attr('disabled', 'disabled').addClass('cursor-default');
            }
            updateErrors();
            return that;
        }).fail(function () {
            return that.disable(null);
        });

        return that;
    },

    /**
     * Disable edition.
     * 
     * @param {string}
     *            message - the error message to display.
     * 
     * @return {Application} This application instance for chaining.
     */
    disable: function (message) {
        'use strict';

        $(':submit').fadeOut();
        $('.btn-adjust').fadeOut();
        $('.btn-add-item').fadeOut();
        $('#totals-panel').fadeOut();
        $('#edit-form :input').attr('readonly', 'readonly');
        $('#item_form :input').attr('readonly', 'readonly');

        $('#data-table-edit *').css('cursor', 'auto');
        $('#data-table-edit a.btn-add-item').removeClass('btn-add-item');
        $('#data-table-edit a.btn-edit-item').removeClass('btn-edit-item');
        $('#data-table-edit a.btn-delete-item').removeClass('btn-delete-item');
        $('#data-table-edit a.btn-delete-group').removeClass('btn-delete-group');
        $('#data-table-edit a.btn-sort-group').removeClass('btn-sort-group');

        // $('#item_delete_button').remove();
        $('#data-table-edit div.dropdown').fadeOut();
        $('#error-all > p').html('<br>').addClass('small').removeClass('text-right');

        $.contextMenu('destroy');
        sortable('#data-table-edit tbody', 'destroy');

        // display error message
        const title = $('#edit-form').data('title');
        message = message || $('#edit-form').data('error-update');
        const options = $.extend({}, $('#flashbags').data(), {
            onHide: function () {
                const html = message.replace('<br><br>', ' ');
                $('#error-all > p').addClass('text-danger text-center').html(html);
            }
        });
        Toaster.danger(message, title, options);

        return this;
    },

    /**
     * Finds the table head for the given group.
     * 
     * @param {int}
     *            id - the group identifier.
     * @returns {jQuery} - the table head, if found; null otherwise.
     */
    findGroup: function (id) {
        'use strict';

        const $head = $("#data-table-edit thead:has(input[name*='groupId'][value=" + id + "])");
        return $head.length !== 0 ? $head : null;
    },

    /**
     * Finds the table body for the given category.
     * 
     * @param {int}
     *            id - the category identifier.
     * @returns {jQuery} - the table body, if found; null otherwise.
     */
    findCategory: function (id) {
        'use strict';

        const $body = $("#data-table-edit tbody:has(input[name*='categoryId'][value=" + id + "])");
        return $body.length !== 0 ? $body : null;
    },
    
    /**
     * Gets the edit dialog group.
     * 
     * @returns {Object} the group.
     */
    getDialogGroup: function () {
        'use strict';
        
        const $selection = $('#item_category :selected');
        return {
            id: $selection.data('groupId'),
            code: $selection.data('groupCode')
            
        };
    },
    
    /**
     * Gets the edit dialog category.
     * 
     * @returns {Object} the category.
     */
    getDialogCategory: function () {
        'use strict';
        
        return {
            id: $('#item_category').val(),
            code: $('#item_category :selected').data('code')
            
        };
    },

    /**
     * Gets the edit dialog item.
     * 
     * @returns {Object} the item.
     */
    getDialogItem: function () {
        'use strict';

        const price = $('#item_price').floatVal();
        const quantity = $('#item_quantity').floatVal();
        const total = Math.round(price * quantity * 100 + Number.EPSILON) / 100;

        return {
            description: $('#item_description').val(),
            unit: $('#item_unit').val(),
            quantity: quantity,
            price: price,            
            total: total
        };
    },

    /**
     * Compare 2 strings with language sensitive.
     * 
     * @param {string}
     *            string1 - the first string to compare.
     * @param {string}
     *            string2 - the second string to compare.
     * @return {int} a negative value if string1 comes before string2; a
     *         positive value if string1 comes after string2; 0 if they are
     *         considered equal.
     */
    compareStrings: function (string1, string2) {
        'use strict';
        
        if ($.isUndefined(this.collator)) {
            const lang = $('html').attr('lang') || 'fr-CH';
            this.collator = new Intl.Collator(lang, {sensitivity: 'variant', caseFirst: 'upper'});
        }
        return this.collator.compare(string1, string2);
    },

    /**
     * Sort items of a category.
     * 
     * @param {jQuery}
     *            $element - the caller element (button or tbody) used to find
     *            the category.
     * @return {Application} This application instance for chaining.
     */
    sortItems: function ($element) {
        'use strict';

        // get rows
        const that = this;
        const $tbody = $element.closest('tbody');
        const $rows = $tbody.find('tr:not(:first)');
        if ($rows.length < 2) {
            return that;
        }

        // save identifiers
        const identifiers = $rows.map(function() {
            return $(this).inputIndex();
        });
        
        // sort
        $rows.sort(function (rowA, rowB) {
            const textA = $('td:first', rowA).text();
            const textB = $('td:first', rowB).text();
            return that.compareStrings(textA, textB);
        }).appendTo($tbody);
        
        // update identifiers
        $rows.each(function(index) {
            const $row = $(this);
            const oldId = $row.inputIndex(); 
            const newId = identifiers[index];
            if (oldId !== newId) {
                $rows.filter(function() {
                    return newId === $(this).inputIndex();
                }).swapIdAndNames($row);                
            }
        });
        
        // update UI
        return that.updateButtons().initDragDrop(true);
    },

    /**
     * Sort categories by name.
     * 
     * @param {jQuery}
     *            $element - the caller element (button, row or thead) used to
     *            find the group and the categories.
     * 
     * @return {Application} This application instance for chaining.
     */
    sortCategories: function ($element) {
        'use strict';

        const that = this;
        let $head = $element.parents('thead');
        if ($head.length === 0) {
            $head = $element.parents('tbody').prev();
        }
        const $bodies = $head.nextUntil('thead');
        if ($bodies.length < 2) {
            return that;
        }

        $bodies.sort(function (a, b) {
          const textA = $('th:first', a).text();
          const textB = $('th:first', b).text();
          return that.compareStrings(textA, textB);
        });
        $head.after($bodies);

        return that;
    },
    
    /**
     * Sort groups by name.
     * 
     * @return {Application} This application instance for chaining.
     */
    sortGroups: function () {
        'use strict';
        
        const that = this;
        const $heads = $('#data-table-edit thead');
        if ($heads.length < 2) {
            return that;
        }
        
        $heads.sort(function (a, b) {
            const textA = $('th:first', a).text();
            const textB = $('th:first', b).text();
            return that.compareStrings(textA, textB);
        });
        
        return that;
    },
    
    /**
     * Sort groups, categories and items.
     * 
     * @return {Application} This application instance for chaining.
     */
    sortCalculation: function () {
        'use strict';

        const that = this;
        that.sortGroups($(this));       
        $('#data-table-edit thead').each(function () {
            that.sortCategories($(this));
        });        
        $('#data-table-edit tbody').each(function () {
            that.sortItems($(this));
        });
        return that;
    },
    
    /**
     * Appends the given group to the table.
     * 
     * @param {Object}
     *            group - the group data used to update row.
     * @returns {jQuery} - the appended group.
     */
    appendGroup: function (group) {
        'use strict';

        // find the next group where to insert this group before
        const that = this;
        let $nextGroup = null;
        $('#data-table-edit thead').each(function() {
            const $head = $(this);
            const text = $head.find('th:first').text();
            if (that.compareStrings(text, group.code) > 0) {
                $nextGroup = $head;
                return false;
            }
        });
        
        // create group and update
        const $parent = $('#data-table-edit');
        const prototype = $parent.getPrototype(/__groupIndex__/g, 'groupIndex');
        const $group = $(prototype);
        $group.find('tr:first th:first').text(group.code);
        $group.findNamedInput('groupId').val(group.id);
        $group.findNamedInput('code').val(group.code);
        
        // insert or append
        if ($nextGroup) {
            $group.insertBefore($nextGroup);
        } else {
            $group.appendTo($parent);
        }

        // reset the drag and drop handler.
        this.initDragDrop(true);

        return $group;
    },

    /**
     * Appends the given category to the table.
     * 
     * @param {jQuery}
     *            $group - the parent group (thead).
     * @param {Object}
     *            category - the category data used to update row.
     * @returns {jQuery} - the appended category.
     */
    appendCategory: function ($group, category) {
        'use strict';

        // find the next category where to insert this category before
        const that = this;
        let $nextCategory = null;
        $group.nextUntil('thead').each(function() {
            const $body = $(this);
            const text = $body.find('th:first').text();
            if (that.compareStrings(text, category.code) > 0) {
                $nextCategory = $body;
                return false;
            }
        });
        
        // create category and update
        const prototype = $group.getPrototype(/__categoryIndex__/g, 'categoryIndex');
        const $category = $(prototype);
        $category.find('tr:first th:first').text(category.code);
        $category.findNamedInput('categoryId').val(category.id);
        $category.findNamedInput('code').val(category.code);
        
        // insert or append
        if ($nextCategory) {
            $category.insertBefore($nextCategory);
        } else {
            const $last = $group.nextUntil('thead').last();
            if ($last.length) {
                $last.after($category);
            } else {
                $group.after($category);    
            }
        }        
        
        // reset the drag and drop handler.
        this.initDragDrop(true);

        return $category;
    },

    /**
     * Display the add item dialog.
     * 
     * @param {jQuery}
     *            $source - the caller element (normally a button).
     */
    showAddDialog: function ($source) {
        'use strict';

        // initialize
        this.initEditDialog().initDragDialog();
        this.$editingRow = null;

        // reset
        $('tr.table-success').removeClass('table-success');
        $('#item_form').resetValidator();

        // update values
        const $input = $source.parents('tbody').find("tr:first input[name*='categoryId']");
        if ($input.length) {
            $('#item_category').val($input.val());
        }
        $('#item_price').floatVal(1);
        $('#item_quantity').floatVal(1);
        $('#item_total').floatVal(1);

        // show
        $('#item_modal').modal('show');
    },

    /**
     * Display the edit item dialog.
     * 
     * This function copy the element to the dialog and display it.
     * 
     * @param {jQuery}
     *            $source - the caller element (normally a button).
     */
    showEditDialog: function (source) {
        'use strict';

        // row
        const $row = source.getParentRow();

        // initialize
        this.initEditDialog().initDragDialog();
        this.$editingRow = $row;

        // reset
        $row.addClass('table-primary');
        $('#item_form').resetValidator();

        // update values
        $('#item_description').val($row.findNamedInput('description').val());
        $('#item_unit').val($row.findNamedInput('unit').val());
        $('#item_category').val($row.parent().findNamedInput('categoryId').val());
        $('#item_price').floatVal($row.findNamedInput('price').val());
        $('#item_quantity').floatVal($row.findNamedInput('quantity').val());
        $('#item_total').floatVal($row.findNamedInput('total').val());

        // show
        $('#item_modal').modal('show');
    },

    /**
     * Remove a calculation group.
     * 
     * @param {jQuery}
     *            $element - the caller element (normally a button).
     * @return {Application} This application instance for chaining.
     */
    removeGroup: function ($element) {
        'use strict';

        const that = this;
        const $head = $element.closest('thead');
        const $elements = $head.add($head.nextUntil('thead'));
        $elements.removeFadeOut(function () {
            that.updateAll();
        });
        
        return that;
    },

    /**
     * Remove a calculation category. If the parent group is empty after
     * deletion, then group is also deleted.
     * 
     * @param {jQuery}
     *            $element - the caller element (normally a button).
     * @return {Application} This application instance for chaining.
     */
    removeCategory: function ($element) {
        'use strict';

        const that = this;
        const $body = $element.closest('tbody');
        const $prev = $body.prev();
        const $next = $body.next();
        
        // if it is the last category then remove the group
        if ($prev.is('thead') && ($next.length === 0 || $next.is('thead'))) {
            return that.removeGroup($prev);
        }
        
        $body.removeFadeOut(function () {
            that.updateAll();
        });    
        return that;
    },
    
    /**
     * Remove a calculation item.
     * 
     * @param {jQuery}
     *            $element - the caller element (button).
     * @return {Application} This application instance for chaining.
     */
    removeItem: function ($element) {
        'use strict';

        // get row and body
        const that = this;
        let $row = $element.getParentRow();
        const $body = $row.parents('tbody');

        // if it is the last item then remove the category
        if ($body.children().length === 2) {
            return that.removeCategory($body);
        }
        
        $row.removeFadeOut(function () {
            that.updateAll();
        });
        return that;
    },

    /**
     * Handle the dialog form submit event when adding an item.
     */
    onAddDialogSubmit: function () {
        'use strict';

        // hide
        $('#item_modal').modal('hide');
        $('#empty-items').addClass('d-none');

        // get dialog values
        const that = this;
        const group = that.getDialogGroup();
        const category = that.getDialogCategory();
        const item = that.getDialogItem();
        
        // get or add group and category
        const $group = that.findGroup(group.id) || that.appendGroup(group);
        const $category = that.findCategory(category.id) || that.appendCategory($group, category);
        
        // append
        const $row = $category.appendRow(item);

        // update total and scroll
        $row.scrollInViewport().timeoutToggle('table-success');
        that.$editingRow = null;
        that.updateAll();
        
    },

    /**
     * Handle the dialog form submit event when editing an item.
     * 
     * @return {Application} This application instance for chaining.
     */
    onEditDialogSubmit: function () {
        'use strict';

        // hide
        $('#item_modal').modal('hide');

        // row?
        const that = this;
        if (!that.$editingRow) {
            return;
        }

        // get dialog values
        const group = that.getDialogGroup();
        const category = that.getDialogCategory();
        const item = that.getDialogItem();
        
        // get old elements
        const $oldBody = that.$editingRow.parents('tbody');
        let $oldHead = $oldBody.prevUntil('thead').prev();
        if ($oldHead.length === 0) {
            $oldHead = $oldBody.prev();
        } 
        
        // get old inputs
        const $oldCategory = $oldBody.findNamedInput('categoryId');
        const $oldGroup = $oldHead.findNamedInput('groupId');

        // get old values
        const oldGroupId = $oldGroup.intVal();
        const oldCategoryId = $oldCategory.intVal();
        
        // same group and category?
        if (oldGroupId !== group.id || oldCategoryId !== category.id) {
            const $group = that.findGroup(group.id) || that.appendGroup(group);
            const $category = that.findCategory(category.id) || that.appendCategory($group, category);
            
            // append
            const $row = $category.appendRow(item);
            
            // check if empty
            const $next = $oldHead.nextUntil('thead');
            const emptyCategory = $oldBody.children().length === 2;
            const emptyGroup = emptyCategory && $next.length === 1;
            
            that.$editingRow.remove();
            that.$editingRow = null;
            
            if (emptyGroup) {
                that.removeGroup($oldHead);                
            } else if (emptyCategory) {
                that.removeCategory($oldBody);
            } else {
                that.updateAll();
            }   
            $row.scrollInViewport().timeoutToggle('table-success');
        } else {
            // update
            that.$editingRow.updateRow(item).timeoutToggle('table-success');
            that.$editingRow = null;
            that.updateAll();
        }
        
        return that;
    },

    /**
     * Handles the row drag start event.
     */
    onDragStart: function () {
        'use strict';
        $('tr.table-success').removeClass('table-success');
    },

    /**
     * Handles the row drag stop event.
     * 
     * @param {Event}
     *            e - the source event.
     */
    onDragStop: function (e) {
        'use strict';

        const that = this;
        const $row = $(e.detail.item);
        const origin = e.detail.origin;
        const destination = e.detail.destination;

        if (origin.container !== destination.container) {
            // -----------------------------
            // Moved to an other category
            // -----------------------------

            // create template and replace content
            const item = $row.getRowItem();
            const $newBody = $(destination.container);
            const $newRow = $newBody.appendRow(item);
            $row.replaceWith($newRow);

            // swap ids and names
            const rows = $newBody.children();
            for (let i = destination.index + 2, len = rows.length; i < len; i++) {
                const $source = $(rows[i - 1]);
                const $target = $(rows[i]);
                $source.swapIdAndNames($target);
            }

            // remove old category if empty
            const $oldBody = $(origin.container);
            if ($oldBody.children().length === 1) {
                that.removeCategory($oldBody);
            } else {
                that.updateAll();
            }   
            $newRow.timeoutToggle('table-success');
            
        } else if (origin.index !== destination.index) {
            // -----------------------------
            // Moved to a new position
            // -----------------------------
            const $target = origin.index < destination.index ? $row.prev() : $row.next();
            $row.swapIdAndNames($target).timeoutToggle('table-success');
        } else {
            // -----------------------------
            // No change
            // -----------------------------
            $row.timeoutToggle('table-success');
        }
    },
};

/**
 * -------------- jQuery extensions --------------
 */
$.fn.extend({

    /**
     * Gets the index, for a row, of the first item input.
     * 
     * For example: calculation_groups_4_items_12_total will return 12.
     * 
     * @returns {int} - the index, if found; -1 otherwise.
     */
    inputIndex() {
        'use strict';
        const $input = $(this).find('input:first');
        const values = $input.attr('id').split('_');
        const value = Number.parseInt(values[values.length - 2], 10);
        return isNaN(value) ? - 1 : value;
    },
    
    /**
     * Swap id and name input attributes.
     * 
     * @param {jQuery}
     *            $target - the target row.
     * 
     * @return {jQuery} - The jQuery source row.
     */
    swapIdAndNames: function ($target) {
        'use strict';

        // get inputs
        const $source = $(this);
        const sourceInputs = $source.find('input');
        const targetInputs = $target.find('input');

        for (let i = 0, len = sourceInputs.length; i < len; i++) {
            // get source attributes
            const $sourceInput = $(sourceInputs[i]);
            const sourceId = $sourceInput.attr('id');
            const sourceName = $sourceInput.attr('name');

            // get target attributes
            const $targetInput = $(targetInputs[i]);
            const targetId = $targetInput.attr('id');
            const targetName = $targetInput.attr('name');

            // swap
            $targetInput.attr('id', sourceId).attr('name', sourceName);
            $sourceInput.attr('id', targetId).attr('name', targetName);
        }

        // update
        Application.updateButtons();

        return $source;
    },
    
    /**
     * Finds an input element that have the name attribute within a given
     * substring.
     * 
     * @param {string}
     *            name - the partial attribute name.
     * 
     * @return {jQuery} - The input, if found; null otherwise.
     */
    findNamedInput: function (name) {
        'use strict';

        const selector = "input[name*='" + name + "']";
        const $result = $(this).find(selector);
        return $result.length ? $result : null;
    },

    /**
     * Fade out and remove the selected element.
     * 
     * @param {function}
     *            callback - the optional function to call after the element is
     *            removed.
     */
    removeFadeOut: function (callback) {
        'use strict';

        const $this = $(this);
        const lastIndex = $this.length - 1; 
        $this.each(function(i, element) {
            $(element).fadeOut(400, function () {
                $(this).remove();
                if (i === lastIndex && $.isFunction(callback)) {
                    callback();
                }
            });
        });
    },

    /**
     * Gets the template prototype from the current element.
     * 
     * @param {string}
     *            pattern - the regex pattern used to replace the index.
     * @param {string}
     *            key - the data key used to retrieve and update the index.
     * @returns {string} - the template.
     */
    getPrototype: function (pattern, key) {
        'use strict';

        const $parent = $(this);

        // get and update index
        const $table = $('#data-table-edit');
        const index = $table.data(key);
        $table.data(key, index + 1);

        // get prototype
        const prototype = $parent.data('prototype');

        // replace index
        return prototype.replace(pattern, index);
    },

    /**
     * Gets item values from the current row.
     * 
     * @returns {Object} the item data.
     */
    getRowItem: function () {
        'use strict';

        const $row = $(this);
        const price = $row.findNamedInput('price').floatVal();
        const quantity = $row.findNamedInput('quantity').floatVal();
        const total = Math.round(price * quantity * 100 + Number.EPSILON) / 100;
        
        return {
            description: $row.findNamedInput('description').val(),
            unit: $row.findNamedInput('unit').val(),
            price: price,
            quantity: quantity,
            total: total
        };
    },

    /**
     * Create a new row and appends to this current parent category (tbody).
     * 
     * @param {Object}
     *            item - the item values used to update the row
     * @returns {jQuery} - the created row.
     */
    appendRow: function (item) {
        'use strict';

        // tbody
        const $parent = $(this);

        // get prototype
        const prototype = $parent.getPrototype(/__itemIndex__/g, 'itemIndex');

        // append and update
        return $(prototype).appendTo($parent).updateRow(item);
    },

    /**
     * Copy the values of the item to the current row.
     * 
     * @param {Object}
     *            item - the item to get values from.
     * @returns {jQuery} - The updated row.
     */
    updateRow: function (item) {
        'use strict';

        const $row = $(this);

        // update inputs
        $row.findNamedInput('description').val(item.description);
        $row.findNamedInput('unit').val(item.unit);
        $row.findNamedInput('price').floatVal(item.price);
        $row.findNamedInput('quantity').floatVal(item.quantity);
        $row.findNamedInput('total').floatVal(item.total);

        // update cells
        $row.find('td:eq(0) .btn-edit-item').text(item.description);
        $row.find('td:eq(1)').text(item.unit);
        $row.find('td:eq(2)').text(Application.toLocaleString(item.price));
        $row.find('td:eq(3)').text(Application.toLocaleString(item.quantity));
        $row.find('td:eq(4)').text(Application.toLocaleString(item.total));

        return $row;
    },

    /**
     * Initialize a type ahead search.
     * 
     * @param {Object}
     *            options - the options to override.
     * 
     * @return {Typeahead} The type ahead instance.
     */
    initSearch: function (options) {
        'use strict';

        const $element = $(this);

        // default options
        const defaults = {
            valueField: '',
            ajax: {
                url: options.url
            },
            // overridden functions (all are set in the server side)
            matcher: function () {
                return true;
            },
            grepper: function (data) {
                return data;
            },
            onSelect: function () {
                $element.select();
            },
            onError: function () {
                const message = options.error;
                const title = $('#edit-form').data('title');
                Toaster.danger(message, title, $('#flashbags').data());
            }
        };

        // merge
        const settings = $.extend(true, defaults, options);

        return $element.typeahead(settings);
    },

    /**
     * Gets the parent row.
     * 
     * @returns {jQuery} - The parent row.
     */
    getParentRow: function () {
        'use strict';

        return $(this).parents('tr:first');
    },

    /**
     * Creates the context menu items.
     * 
     * @returns {Object} the context menu items.
     */
    getContextMenuItems: function () {
        'use strict';
        
        const $elements = $(this).getParentRow().find('.dropdown-menu').children();
        return (new MenuBuilder()).fill($elements).getItems();
    }
});

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // searches
    SearchHelper.init();

    // move rows
    MoveRowHandler.init();

    // application
    Application.init();

    // context menu
    const selector = '.table-edit th:not(.d-print-none), .table-edit td:not(.d-print-none)';
    const show = function () {
        $('.dropdown-menu.show').removeClass('show');
        $(this).parent().addClass('table-primary');
    };
    const hide = function () {
        $(this).parent().removeClass('table-primary');
    };
    $('.table-edit').initContextMenu(selector, show, hide);

    // errors
    updateErrors();

    // main form validation
    $('#edit-form').initValidator();

    // user margin
    const $margin = $('#calculation_userMargin');
    $margin.on('input propertychange', function () {
        $margin.updateTimer(function () {
            Application.updateTotals();
        }, 250);
    });

    // prototypes
    // const table = $('.table-edit').data('prototype');
    // console.log('table', $(table).html());
    // const head = $('.table-edit thead:first').data('prototype');
    // console.log('thead', $(head).html());
    // const body = $('.table-edit tbody:first').data('prototype');
    // console.log('tbody', $(body).html());
    
}(jQuery));
