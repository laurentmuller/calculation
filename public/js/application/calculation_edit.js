/* globals sortable, Toaster, MenuBuilder, EditTaskDialog, EditItemDialog  */
(function ($) {
    'use strict';

    /**
     * -------------- The Application --------------
     */
    const Application = {
        /**
         * Initialize application.
         *
         * @return {Application} This instance for chaining.
         */
        init: function () {
            this.initDragDrop(false);
            this.updateButtons();
            this.initMenus();
            return this;
        },

        /**
         * Initialize the drag and drop.
         *
         * @param {boolean} destroy - true to destroy the existing sortable (if any).
         * @return {Application} This instance for chaining.
         */
        initDragDrop: function (destroy) {
            const that = this;
            if (destroy) {
                const $existing = $('#data-table-edit tbody.sortable');

                // remove handlers
                if (that.dragStartProxy) {
                    $existing.off('sortstart', that.dragStartProxy)
                        .off('sortupdate', that.dragStopProxy);
                }

                // destroy
                sortable($existing, 'destroy');
            }

            // create handlers
            if (!that.dragStartProxy) {
                that.dragStartProxy = function () {
                    that.onDragStart();
                };
                that.dragStopProxy = function (e) {
                    that.onDragStop(e);
                };
            }

            // create sortable
            const $bodies = $('#data-table-edit tbody');
            sortable($bodies, {
                acceptFrom: 'tbody',
                items: 'tr:not(.drag-skip)',
                forcePlaceholderSize: true,
                placeholderClass: 'table-primary'
            });

            // update bodies
            $bodies.addClass('sortable')
                .on('sortstart', that.dragStartProxy)
                .on('sortupdate', that.dragStopProxy)
                .find('tr').removeAttr('role');

            return that;
        },

        /**
         * Gets the edit item dialog.
         *
         * @return {EditItemDialog} the dialog.
         */
        getItemDialog: function () {
            if (!this.itemDialog) {
                this.itemDialog = new EditItemDialog(this);
            }
            return this.itemDialog;
        },

        /**
         * Gets the URL used to load the item dialog.
         *
         * @return {string}
         */
        getItemDialogUrl() {
            return $('#edit-form').data('dialog-item');
        },

        /**
         * Gets the edit task dialog.
         *
         * @return {EditTaskDialog} the dialog.
         */
        getTaskDialog: function () {
            if (!this.taskDialog) {
                this.taskDialog = new EditTaskDialog(this);
            }
            return this.taskDialog;
        },

        /**
         * Gets the URL used to load the task dialog.
         *
         * @return {string}
         */
        getTaskDialogUrl() {
            return $('#edit-form').data('dialog-task');
        },

        /**
         * Initialize the groups and item menus.
         *
         * @return {Application} This instance for chaining.
         */
        initMenus: function () {
            const that = this;
            // adjust button
            const $buttonAdjust = $('.btn-adjust');
            if ($buttonAdjust.length) {
                $buttonAdjust.on('click', () => {
                    $buttonAdjust.tooltip('hide');
                    that.updateTotals(true);
                }).tooltip();
            }
            // toolbar buttons
            $('#items-panel .card-header .btn-add-item').on('click', function () {
                that.showAddItemDialog($(this));
            });
            $('#items-panel .card-header .btn-add-task').on('click', function () {
                that.showAddTaskDialog($(this));
            });
            $('.btn-sort-items').on('click', function () {
                that.sortCalculation();
            });
            // data table buttons
            $('#data-table-edit').on('click', '.btn-add-item', function () {
                that.showAddItemDialog($(this));
            }).on('click', '.btn-add-task', function () {
                that.showAddTaskDialog($(this));
            }).on('click', '.btn-edit-item', function () {
                that.showEditItemDialog($(this));
            }).on('click', '.btn-copy-item', function () {
                that.showCopyItemDialog($(this));
            }).on('click', '.btn-delete-item', function () {
                that.removeItem($(this));
            }).on('click', '.btn-delete-category', function () {
                that.removeCategory($(this));
            }).on('click', '.btn-delete-group', function () {
                that.removeGroup($(this));
            }).on('click', '.btn-edit-price', function () {
                that.editItemPrice($(this));
            }).on('click', '.btn-edit-quantity', function () {
                that.editItemQuantity($(this));
            });

            return that;
        },

        /**
         * Update the positions, the buttons, the total and initialize the drag-drop.
         *
         * @return {Application} This instance for chaining.
         */
        updateAll: function () {
            this.updatePositions();
            this.updateButtons();
            this.updateTotals(false);
            this.initDragDrop(true);
            return this;
        },

        /**
         * Update the move up/down and the sort buttons.
         *
         * @return {Application} This instance for chaining.
         */
        updateButtons: function () {
            const that = this;

            let hideUp;
            let hideDown;
            let disabled = true;

            // groupes
            let lastGroup = null;
            /** @type {jQuery|HTMLElement|*} */
            const $groups = that.getGroups();
            $groups.each(function (indexGroup, group) {
                const $group = $(group);
                hideUp = indexGroup === 0;
                hideDown = indexGroup === $groups.length - 1;
                $group.find('.btn-first-group').toggleClass('d-none', hideUp);
                $group.find('.btn-up-group').toggleClass('d-none', hideUp);
                $group.find('.btn-down-group').toggleClass('d-none', hideDown);
                $group.find('.btn-last-group').toggleClass('d-none', hideDown);
                $group.find('.btn-first-group').prev('.dropdown-divider')
                    .toggleClass('d-none', hideUp && hideDown);

                // sortable?
                const newGroup = $group.find('th:first').text();
                if (disabled && lastGroup && that.compareStrings(lastGroup, newGroup) > 0) {
                    disabled = false;
                }
                lastGroup = newGroup;

                // categories
                let lastCategory = null;
                const $categories = that.getCategories($group);
                $categories.each(function (indexCategory, category) {
                    const $category = $(category);
                    hideUp = indexCategory === 0;
                    hideDown = indexCategory === $categories.length - 1;
                    $category.find('.btn-first-category').toggleClass('d-none', hideUp);
                    $category.find('.btn-up-category').toggleClass('d-none', hideUp);
                    $category.find('.btn-down-category').toggleClass('d-none', hideDown);
                    $category.find('.btn-last-category').toggleClass('d-none', hideDown);
                    $category.find('.btn-first-category').prev('.dropdown-divider').toggleClass('d-none', hideUp && hideDown);

                    // sortable?
                    const newCategory = $category.find('th:first').text();
                    if (disabled && lastCategory && that.compareStrings(lastCategory, newCategory) > 0) {
                        disabled = false;
                    }
                    lastCategory = newCategory;

                    // items
                    let lastItem = null;
                    const $items = that.getItems($category);
                    $items.each(function (indexItem, item) {
                        const $item = $(item);
                        hideUp = indexItem === 0;
                        hideDown = indexItem === $items.length - 1;
                        $item.find('.btn-first-item').toggleClass('d-none', hideUp);
                        $item.find('.btn-up-item').toggleClass('d-none', hideUp);
                        $item.find('.btn-down-item').toggleClass('d-none', hideDown);
                        $item.find('.btn-last-item').toggleClass('d-none', hideDown);
                        $item.find('.btn-first-item').prev('.dropdown-divider').toggleClass('d-none', hideUp && hideDown);

                        // sortable?
                        const newItem = $item.find('td:first').text();
                        if (disabled && lastItem && that.compareStrings(lastItem, newItem) > 0) {
                            disabled = false;
                        }
                        lastItem = newItem;
                    });
                });
            });

            // update global sort
            $('.btn-sort-items').toggleDisabled(disabled);

            return this;
        },


        /**
         * Serialize the form.
         *
         * @param {boolean} adjust - true to adjust the user margin.
         * @return {Object} the data to post.
         */
        serializeForm: function (adjust) {
            const groups = [];
            $('#data-table-edit thead').each(function () {
                let total = 0.0;
                const $bodies = $(this).nextUntil('thead');
                $bodies.each(function () {
                    $(this).find('tr.item').each(function () {
                        const $row = $(this);
                        const price = $row.findNamedInput('price').floatVal();
                        const quantity = $row.findNamedInput('quantity').floatVal();
                        total += price * quantity;
                    });
                });
                const id = $(this).find('input[name$="[group]"]').intVal();
                groups.push({
                    id: id,
                    total: total
                });
            });
            const userMargin = $('#calculation_userMargin').floatVal() / 100.0;

            return JSON.stringify({
                adjust: adjust,
                userMargin: userMargin,
                groups: groups,
            });
        },

        /**
         * Update the totals.
         *
         * @param {boolean} adjust - true to adjust the user margin.
         * @return {Application} This instance for chaining.
         */
        updateTotals: function (adjust) {
            const that = this;
            const $form = $('#edit-form');
            const $buttonAdjust = $('.btn-adjust');
            const $userMarginRow = $('#user-margin-row');

            // hide tooltip
            $buttonAdjust.tooltip('hide');

            // show or hide empty items
            $('#empty-items').toggleClass('d-none', $('#data-table-edit tbody').length !== 0);

            // validate user margin
            if (!$('#calculation_userMargin').valid()) {
                if ($userMarginRow.length === 0) {
                    const $tr = $('<tr/>', {
                        'id': 'user-margin-row'
                    });
                    const $td = $('<td>', {
                        'class': 'text-body-secondary',
                        'text': $form.data('error-margin')
                    });
                    $tr.append($td);
                    $('#totals-table tbody:first tr').remove();
                    $('#totals-table tbody:first').append($tr);
                } else {
                    $userMarginRow.removeClass('d-none');
                }
                $buttonAdjust.toggleDisabled(true).addClass('cursor-default');
                return that;
            }

            // abort
            if (that.jqXHR) {
                that.jqXHR.abort();
                that.jqXHR = null;
            }

            /**
             * @param {Object} response
             * @param {boolean} response.result
             * @param {boolean} response.adjust
             * @param {string} response.message
             * @param {string} response.view
             * @param {number} response.user_margin
             * @param {boolean} response.overall_below
             */
            that.jqXHR = $.post({
                url: $form.data('update'),
                data: this.serializeForm(adjust),
                contentType: 'application/json;',
                success: function (response) {
                    // error?
                    if (!response.result) {
                        return that.disable(response.message);
                    }

                    // update content
                    /** @type {jQuery|HTMLElement|*} */
                    const $totalPanel = $('#totals-panel');
                    if (response.view) {
                        /** @type {jQuery|HTMLElement|*} */
                        const $body = $('#totals-table > tbody');
                        $body.fadeOut(300, function () {
                            $body.html(response.view).fadeIn(300);
                            $totalPanel.fadeIn();
                        });
                    } else {
                        $totalPanel.fadeOut();
                    }
                    if (response.adjust && !$.isUndefined(response.user_margin) && !isNaN(response.user_margin)) {
                        const value = Math.round(response.user_margin * 100);
                        $('#calculation_userMargin').data('value', value)
                            .intVal(value).selectFocus();
                    }
                    if (response.overall_below) {
                        $buttonAdjust.toggleDisabled(false).removeClass('cursor-default');
                    } else {
                        $buttonAdjust.toggleDisabled(true).addClass('cursor-default');
                    }
                    $('#calculation_customer').trigger('input');
                    $('#data-table-edit').updateErrors();
                    return that;
                },
                fail: function (_jqXHR, textStatus) {
                    if (textStatus !== 'abort') {
                        return that.disable();
                    }
                }
            });

            return that;
        },

        /**
         * Disable edition mode.
         *
         * @param {string} [message] - the error message to display.
         * @return {Application} This instance for chaining.
         */
        disable: function (message) {
            $('#edit-form :input, #item_form :input').attr('readonly', 'readonly');
            $(':submit, .btn-adjust, .btn-add-item, #totals-panel, #data-table-edit div.dropdown').fadeOut();

            $('#data-table-edit *').css('cursor', 'auto');
            $('#data-table-edit a.btn-add-item').removeClass('btn-add-item');
            $('#data-table-edit a.btn-edit-item').removeClass('btn-edit-item');
            $('#data-table-edit a.btn-delete-item').removeClass('btn-delete-item');
            $('#data-table-edit a.btn-delete-group').removeClass('btn-delete-group');
            $('#data-table-edit a.btn-sort-group').removeClass('btn-sort-group');
            $('#error-all > p').html('<br>').addClass('small').removeClass('text-end');

            $.contextMenu('destroy');
            sortable($('#data-table-edit tbody'), 'destroy');

            // display error message
            const $form = $('#edit-form');
            const title = $form.data('title');
            message = message || $form.data('error-update');
            const options = {
                onHide: function () {
                    const html = message.replace('<br><br>', ' ');
                    $('#error-all > p').addClass('text-danger text-center').html(html);
                }
            };
            Toaster.danger(message, title, options);

            return this;
        },

        /**
         * Gets groups.
         *
         * @returns {jQuery|HTMLElement|*} the groups.
         */
        getGroups: function () {
            return $('#data-table-edit .group');
        },

        /**
         * Gets the categories for the given group.
         *
         * @param {jQuery|HTMLElement|*} $group - the group (thead) to search categories for.
         * @returns {jQuery|HTMLElement|*} the categories.
         */
        getCategories: function ($group) {
            /** @type {jQuery|HTMLElement|*} */
            const $last = $group.nextUntil('.group');
            return $last.find('.category');
        },

        /**
         * Gets the items for the given category.
         *
         * @param {jQuery|HTMLElement|*} $category - the category (th) to search items for.
         * @returns {jQuery|HTMLElement|*} the items.
         */
        getItems: function ($category) {
            return $category.parents('tbody').find('.item');
        },

        /**
         * Finds or create the table head for the given group.
         *
         * @param {{id: number, code: string}} group - the group data used to find row.
         * @returns {jQuery|HTMLElement|*} the table head.
         */
        findOrCreateGroup: function (group) {
            const $group = $('#data-table-edit .group:has(input[name$="[group]"][value="' + group.id + '"])');
            if ($group.length > 0) {
                return $group;
            }
            return this.appendGroup(group);
        },

        /**
         * Find or create the table body for the given category.
         *
         * @param {jQuery|HTMLElement|*} $group - the parent group (thead).
         * @param {{id: number, code: string}} category - the category data used to update row.
         * @returns {jQuery|HTMLElement|*} the table body.
         */
        findOrCreateCategory: function ($group, category) {
            const $body = $('#data-table-edit tbody:has(input[name$="[category]"][value="' + category.id + '"])');
            if ($body.length > 0) {
                return $body;
            }
            return this.appendCategory($group, category);
        },

        /**
         * Compare 2 strings with language sensitive.
         *
         * @param {string} string1 - the first string to compare.
         * @param {string} string2 - the second string to compare.
         * @return {int} a negative value if string1 comes before string2; a
         *         positive value if string1 comes after string2; 0 if they are
         *         considered equal.
         */
        compareStrings: function (string1, string2) {
            if ($.isUndefined(this.collator)) {
                const lang = $('html').attr('lang') || 'fr-CH';
                this.collator = new Intl.Collator(lang, {sensitivity: 'variant', caseFirst: 'upper'});
            }
            return this.collator.compare(string1, string2);
        },

        /**
         * Sort items by description.
         *
         * @param {jQuery|HTMLElement|*} $element - the caller element (button or tbody) used to find the category.
         * @return {Application} This instance for chaining.
         */
        sortItems: function ($element) {
            // get rows
            const that = this;
            const $tbody = $element.closest('tbody');
            /** @type {jQuery|HTMLElement|*} */
            const $items = $tbody.find('.item');
            if ($items.length < 2) {
                return that;
            }

            // sort
            $items.sort(function (rowA, rowB) {
                const textA = $('td:first', rowA).text();
                const textB = $('td:first', rowB).text();
                return that.compareStrings(textA, textB);
            }).appendTo($tbody);

            // update UI
            that.updatePositions();
            that.updateButtons();
            that.initDragDrop(true);
            return that;
        },

        /**
         * Sort categories by code.
         *
         * @param {jQuery|HTMLElement|*} $element - the caller element (button, row or thead) used to
         *            find the group and the categories.
         * @return {Application} This instance for chaining.
         */
        sortCategories: function ($element) {
            const that = this;
            let $group = $element.closest('.group');
            if ($group.length === 0) {
                $group = $element.parents('tbody').prev();
            }
            const $bodies = $group.nextUntil('.group');
            if ($bodies.length < 2) {
                return that;
            }

            $bodies.sort(function (a, b) {
                const textA = $('th:first', a).text();
                const textB = $('th:first', b).text();
                return that.compareStrings(textA, textB);
            });
            $group.after($bodies);

            return that;
        },

        /**
         * Sort groups by code.
         *
         * @return {Application} This instance for chaining.
         */
        sortGroups: function () {
            const that = this;
            const $groups = that.getGroups();
            if ($groups.length < 2) {
                return that;
            }

            // attach
            let map = new Map();
            $groups.each(function () {
                const $group = $(this);
                const $bodies = $group.nextUntil('.group');
                map.set($group, $bodies);
            });

            // sort
            map = new Map([...map].sort(function (a, b) {
                const textA = $('th:first', a[0]).text();
                const textB = $('th:first', b[0]).text();
                return that.compareStrings(textA, textB);
            }));

            // replace
            const $table = $('#data-table-edit');
            map.forEach(function (value, key) {
                key.appendTo($table);
                value.appendTo($table);
            });

            return that;
        },

        /**
         * Sort groups, categories and items.
         *
         * @return {Application} This instance for chaining.
         */
        sortCalculation: function () {
            const that = this;
            that.sortGroups();
            that.getGroups().each(function () {
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
         * @param {{id: number, code: string}} group - the group data used to update row.
         * @returns {jQuery} the appended group.
         */
        appendGroup: function (group) {
            // find the next group where to insert this group before
            const that = this;
            let $nextGroup = null;
            that.getGroups().each(function () {
                const $head = $(this);
                const text = $head.find('th:first').text();
                if (that.compareStrings(text, group.code) > 0) {
                    $nextGroup = $head;
                    return false;
                }
            });

            // create a group and update
            const $parent = $('#data-table-edit');
            const prototype = $parent.getPrototype(/__groupIndex__/g, 'groupIndex');
            const $group = $(prototype);
            $group.find('tr:first th:first').text(group.code);
            $group.findNamedInput('group').val(group.id);
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
         * @param {jQuery|HTMLElement|*} $group - the parent group (thead).
         * @param {{id: number, code: string}} category - the category data used to update row.
         * @returns {jQuery|HTMLElement|*} the appended category.
         */
        appendCategory: function ($group, category) {
            // find the next category where to insert this category before
            const that = this;
            let $nextCategory = null;
            $group.nextUntil('.group').each(function () {
                const $body = $(this);
                const text = $body.find('th:first').text();
                if (that.compareStrings(text, category.code) > 0) {
                    $nextCategory = $body;
                    return false;
                }
            });

            // create a category and update
            const prototype = $group.getPrototype(/__categoryIndex__/g, 'categoryIndex');
            const $category = $(prototype);
            $category.find('tr:first th:first').text(category.code);
            $category.findNamedInput('category').val(category.id);
            $category.findNamedInput('code').val(category.code);

            // insert or append
            if ($nextCategory) {
                $category.insertBefore($nextCategory);
            } else {
                const $last = $group.nextUntil('.group').last();
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
         * Display the dialog to add an item.
         *
         * @param {jQuery|HTMLElement|*} $source - the caller element (normally a button).
         */
        showAddItemDialog: function ($source) {
            // reset
            $('.table-edit tr.table-success').removeClass('table-success');

            // show dialog
            const $row = $source.getParentRow();
            this.getItemDialog().showAdd($row);
        },

        /**
         * Display the add task dialog.
         *
         * @param {jQuery|HTMLElement|*} $source - the caller element (normally a button).
         */
        showAddTaskDialog: function ($source) {
            // reset
            $('.table-edit tr.table-success').removeClass('table-success');

            // show dialog
            const $row = $source.getParentRow();
            this.getTaskDialog().showAdd($row);
        },


        /**
         * Display the edit item dialog.
         * This function copies the element to the dialog and displays it.
         * If the user clicks OK, the item is updated.
         *
         * @param {jQuery|HTMLElement|*} $source - the caller element (normally a button).
         */
        showEditItemDialog: function ($source) {
            const $row = $source.getParentRow();
            if ($row && $row.length) {
                $row.addClass('table-primary').scrollInViewport();
                this.getItemDialog().showEdit($row);
            }
        },

        /**
         * Display the edit item dialog.
         * This function copies the selected element to the dialog and displays it.
         * If the user clicks OK, a new item is added.
         *
         * @param {jQuery|HTMLElement|*} $source - the caller element (normally a button).
         */
        showCopyItemDialog: function ($source) {
            const $row = $source.getParentRow();
            if ($row && $row.length) {
                $row.addClass('table-primary').scrollInViewport();
                this.getItemDialog().showCopy($row);
            }
        },

        /**
         * Remove a calculation group.
         *
         * @param {jQuery|HTMLElement|*} $element - the caller element (normally a button).
         * @return {Application} This instance for chaining.
         */
        removeGroup: function ($element) {
            const that = this;
            const $head = $element.closest('.group');
            const $elements = $head.add($head.nextUntil('.group'));
            $elements.removeFadeOut(function () {
                that.updateAll();
            });

            return that;
        },

        /**
         * Remove a calculation category.
         * If the parent group is empty after deletion, then the group is also deleted.
         *
         * @param {jQuery|HTMLElement|*} $element - the caller element (normally a button).
         * @return {Application} This instance for chaining.
         */
        removeCategory: function ($element) {
            const that = this;
            const $body = $element.closest('tbody');
            const $prev = $body.prev();
            const $next = $body.next();

            // if it is the last category, then remove the group
            if ($prev.is('.group') && ($next.length === 0 || $next.is('.group'))) {
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
         * @param {jQuery|HTMLElement|*} $element - the caller element (button).
         * @return {Application} This instance for chaining.
         */
        removeItem: function ($element) {
            // get row and body
            const that = this;
            let $row = $element.getParentRow();
            const $body = $row.parents('tbody');

            // if it is the last item, then remove the category
            if ($body.children().length === 2) {
                return that.removeCategory($body);
            }

            $row.removeFadeOut(function () {
                that.updateAll();
            });
            return that;
        },

        /**
         * Handle the item dialog form submit event when adding an item.
         *
         * @return {Application} This instance for chaining.
         */
        onAddItemDialogSubmit: function () {
            // hide dialog
            const dialog = this.getItemDialog().hide();

            // get dialog values
            const group = dialog.getGroup();
            const category = dialog.getCategory();
            const item = dialog.getItem();

            // get or create the group and the category
            const $group = this.findOrCreateGroup(group);
            const $category = this.findOrCreateCategory($group, category);

            // append
            const $row = $category.appendRowItem(item);
            $row.scrollInViewport().timeoutToggle('table-success');

            // update
            return this.updateAll();
        },

        /**
         * Handle the item dialog form submit event when editing an item.
         *
         * @return {Application} This instance for chaining.
         */
        onEditItemDialogSubmit: function () {
            // hide dialog
            const dialog = this.getItemDialog().hide();

            // get dialog values
            /** @type {jQuery|HTMLElement|*} */
            const $editingRow = dialog.getEditingRow();
            if (!$editingRow) {
                return this;
            }
            const group = dialog.getGroup();
            const category = dialog.getCategory();
            const item = dialog.getItem();

            const $oldBody = $editingRow.parents('tbody');
            let $oldHead = $oldBody.prevUntil('.group').prev();
            if ($oldHead.length === 0) {
                $oldHead = $oldBody.prev();
            }

            // get old values
            const oldGroupId = $oldHead.findNamedInput('group').intVal();
            const oldCategoryId = $oldBody.findNamedInput('category').intVal();
            const oldItem = $editingRow.getRowItem();

            // change?
            if (oldGroupId === group.id && oldCategoryId === category.id && JSON.stringify(item) === JSON.stringify(oldItem)) {
                $editingRow.scrollInViewport().timeoutToggle('table-success');
                return this;
            }

            // same group and category?
            if (oldGroupId !== group.id || oldCategoryId !== category.id) {
                // get or create the group and the category
                const $group = this.findOrCreateGroup(group);
                const $category = this.findOrCreateCategory($group, category);

                // append
                const $row = $category.appendRowItem(item);

                // check if empty
                const $next = $oldHead.nextUntil('.group');
                const isEmptyCategory = $oldBody.children().length === 2;
                const isEmptyGroup = isEmptyCategory && $next.length === 1;

                $editingRow.remove();
                if (isEmptyGroup) {
                    this.removeGroup($oldHead);
                } else if (isEmptyCategory) {
                    this.removeCategory($oldBody);
                } else {
                    this.updateAll();
                }
                $row.scrollInViewport().timeoutToggle('table-success');
            } else {
                // update
                $editingRow.updateRowItem(item).timeoutToggle('table-success');
                this.updateAll();
            }
            return this;
        },

        /**
         * Handle the task dialog form submit event when adding a task.
         *
         * @return {Application} This instance for chaining.
         */
        onAddTaskDialogSubmit: function () {
            // hide dialog
            /**
             * @type {EditTaskDialog}
             */
            const dialog = this.getTaskDialog().hide();

            // get dialog values
            const group = dialog.getGroup();
            const category = dialog.getCategory();
            const items = dialog.getItems();

            // get or create the group and the category
            const $group = this.findOrCreateGroup(group);
            const $category = this.findOrCreateCategory($group, category);

            // append items and select
            items.forEach(function (item) {
                const $row = $category.appendRowItem(item);
                $row.scrollInViewport().timeoutToggle('table-success');
            });

            this.$editingRow = null;
            return this.updateAll();
        },

        /**
         * Handle the task dialog form submit event when editing a task.
         *
         * @return {Application} This instance for chaining.
         */
        onEditTaskDialogSubmit: function () {
            this.getTaskDialog().hide();
        },

        /**
         * Handles the row drag start event.
         */
        onDragStart: function () {
            $('.table-edit tr.table-success').removeClass('table-success');
        },

        /**
         * Handles the row drag stop event.
         *
         * @param {CustomEvent} e - the source event.
         */
        onDragStop: function (e) {
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
                const $newRow = $newBody.appendRowItem(item);
                $row.replaceWith($newRow);

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
                $row.timeoutToggle('table-success');
                that.updatePositions();
                that.updateButtons();
            } else {
                // -----------------------------
                // No change
                // -----------------------------
                $row.timeoutToggle('table-success');
            }
        },


        /**
         * Update positions of groups, categories and items.
         *
         * @return {Application} This instance for chaining.
         */
        updatePositions: function () {
            const that = this;

            // groupes
            const $groups = that.getGroups();
            $groups.each(function (indexGroup, group) {
                const $group = $(group);
                $group.findNamedInput('position').val(indexGroup);

                // categories
                const $categories = that.getCategories($group);
                $categories.each(function (indexCategory, category) {
                    const $category = $(category);
                    $category.findNamedInput('position').val(indexCategory);

                    // items
                    const $items = that.getItems($category);
                    $items.each(function (indexItem, item) {
                        const $item = $(item);
                        $item.findNamedInput('position').val(indexItem);
                    });
                });
            });

            return that;
        },

        /**
         * Edit the calculation item's price.
         *
         * @param {jQuery|HTMLElement|*} $element - the caller element (button).
         * @return {Application} This instance for chaining.
         */
        editItemPrice: function ($element) {
            const $row = $element.getParentRow();
            if ($row && $row.length) {
                $row.find('td:eq(2)').trigger('click');
            }
            return this;
        },

        /**
         * Edit the calculation item's quantity.
         *
         * @param {jQuery|HTMLElement|*} $element - the caller element (button).
         * @return {Application} This instance for chaining.
         */
        editItemQuantity: function ($element) {
            const $row = $element.getParentRow();
            if ($row && $row.length) {
                $row.find('td:eq(3)').trigger('click');
            }
            return this;
        }
    };

    /**
     * -------------- jQuery extensions --------------
     */
    $.fn.extend({

        /**
         * Finds an input element that has the name attribute within a given
         * substring.
         *
         * @param {string} name - the partial attribute name.
         * @return {jQuery|HTMLElement|*|null} - The input, if found; null otherwise.
         */
        findNamedInput: function (name) {
            const selector = `input[name*='${name}']`;
            const $result = $(this).find(selector);
            return $result.length ? $result : null;
        },

        /**
         * Fade out and remove the selected element.
         *
         * @param {function} callback - the optional function to call after the element is removed.
         */
        removeFadeOut: function (callback) {
            const $this = $(this);
            const lastIndex = $this.length - 1;
            $this.each(function (i, element) {
                $(element).fadeOut(400, function () {
                    $(this).remove();
                    if (i === lastIndex && typeof callback === 'function') {
                        callback();
                    }
                });
            });
        },

        /**
         * Gets the template prototype from the current element.
         *
         * @param {RegExp} pattern - the regex pattern used to replace the index.
         * @param {string} key - the data key used to retrieve and update the index.
         * @returns {string} the template.
         */
        getPrototype: function (pattern, key) {
            const $parent = $(this);
            // get and update index
            const $table = $('#data-table-edit');
            const index = $.parseInt($table.data(key));
            $table.data(key, index + 1);
            // get the prototype
            const prototype = $parent.data('prototype');
            // replace index
            return prototype.replace(pattern, index);
        },

        /**
         * Gets item values from this row.
         *
         * @returns {Object} the item data.
         */
        getRowItem: function () {
            const $row = $(this);
            const price = $row.findNamedInput('price').floatVal();
            const quantity = $row.findNamedInput('quantity').floatVal();
            const total = $.roundValue(price * quantity);

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
         * @param {Object} item - the item values used to update the row
         * @returns {jQuery<HTMLTableRowElement>} the created row.
         */
        appendRowItem: function (item) {
            // tbody
            const $parent = $(this);

            // get the prototype
            const prototype = $parent.getPrototype(/__itemIndex__/g, 'itemIndex');

            // append and update
            return $(prototype).appendTo($parent).updateRowItem(item);
        },

        /**
         * Copy the values of the item to this row.
         *
         * @param {Object} item - the item to get values from.
         * @returns {jQuery<HTMLTableRowElement>} The updated row.
         */
        updateRowItem: function (item) {
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
            $row.find('td:eq(2)').text($.formatFloat(item.price));
            $row.find('td:eq(3)').text($.formatFloat(item.quantity));
            $row.find('td:eq(4)').text($.formatFloat(item.total));
            return $row;
        },

        /**
         * Update the total cell of this row.
         *
         * @returns {jQuery} The updated row.
         */
        updateTotal: function () {
            const $row = $(this);
            const item = $row.getRowItem();
            $row.find('td:eq(4)').text($.formatFloat(item.total));
            return $row;
        },

        /**
         * Gets the parent group.
         *
         * @returns {jQuery} The parent group (thead).
         */
        getParentGroup: function () {
            return $(this).closest('.group');
        },

        /**
         * Gets the parent category.
         *
         * @returns {jQuery} The parent category (tbody).
         */
        getParentCategory: function () {
            return $(this).closest('tbody');
        },

        /**
         * Gets the parent row.
         *
         * @returns {jQuery} The parent row.
         */
        getParentRow: function () {
            return $(this).parents('tr:first');
        },

        /**
         * Creates the context menu items.
         *
         * @returns {Object} the context menu items.
         */
        getContextMenuItems: function () {
            const $elements = $(this).getParentRow().find('.dropdown-menu').children();
            const builder = new MenuBuilder({
                classSelector: 'btn-default'
            });
            return builder.fill($elements).getItems();
        },
    });

    /**
     * -------------- The move rows handler --------------
     */
    const MoveHandler = {

        /**
         * Initialize handlers.
         */
        init: function () {
            const that = this;
            const $dataTableEdit = $('#data-table-edit');
            // groupes
            $dataTableEdit.on('click', '.btn-first-group', function () {
                that.moveGroupFirst($(this).getParentGroup());
            }).on('click', '.btn-up-group', function () {
                that.moveGroupUp($(this).getParentGroup());
            }).on('click', '.btn-down-group', function () {
                that.moveGroupDown($(this).getParentGroup());
            }).on('click', '.btn-last-group', function () {
                that.moveGroupLast($(this).getParentGroup());
            });

            // categories
            $dataTableEdit.on('click', '.btn-first-category', function () {
                that.moveCategoryFirst($(this).getParentCategory());
            }).on('click', '.btn-up-category', function () {
                that.moveCategoryUp($(this).getParentCategory());
            }).on('click', '.btn-down-category', function () {
                that.moveCategoryDown($(this).getParentCategory());
            }).on('click', '.btn-last-category', function () {
                that.moveCategoryLast($(this).getParentCategory());
            });

            // items
            $dataTableEdit.on('click', '.btn-first-item', function () {
                that.moveItemFirst($(this).getParentRow());
            }).on('click', '.btn-up-item', function () {
                that.moveItemUp($(this).getParentRow());
            }).on('click', '.btn-down-item', function () {
                that.moveItemDown($(this).getParentRow());
            }).on('click', '.btn-last-item', function () {
                that.moveItemLast($(this).getParentRow());
            });
        },

        /**
         * Move a source group before or after the target group.
         *
         * @param {jQuery|HTMLElement|*} $source - the group to move.
         * @param {jQuery|HTMLElement|*} $target - the target group.
         * @param {boolean} up - true to move before the target (up); false to move after (down).
         * @return {jQuery|HTMLElement|*} the moved group.
         */
        moveGroup: function ($source, $target, up) {
            // hide menus
            $.hideDropDownMenus();

            // check
            if ($source && $target && $source !== $target) {
                // save the source tbody
                /** @type {jQuery|HTMLElement|*} */
                const $bodies = $source.nextUntil('.group');

                // move
                if (up) {
                    $source.insertBefore($target);
                } else {
                    $source.insertAfter($target.nextUntil('.group'));
                }
                $bodies.insertAfter($source);

                // update
                $source.scrollInViewport().find('tr:first').timeoutToggle('table-success');
                Application.updatePositions();
                Application.updateButtons();
            }
            return $source;
        },

        /**
         * Move a calculation group to the first position.
         *
         * @param {jQuery|HTMLElement|*} $group - the group to move.
         * @return {jQuery|HTMLElement|*} - the moved group.
         */
        moveGroupFirst: function ($group) {
            const $target = $group.prevAll('.group:last');
            if ($target.length && $target !== $group) {
                return this.moveGroup($group, $target, true);
            }
            return $group;
        },

        /**
         * Move a calculation group to the last position.
         *
         * @param {jQuery|HTMLElement|*} $group - the group to move.
         * @return {jQuery|HTMLElement|*} - the moved group.
         */
        moveGroupLast: function ($group) {
            const $target = $group.nextAll('.group:last');
            if ($target.length && $target !== $group) {
                return this.moveGroup($group, $target, false);
            }
            return $group;
        },

        /**
         * Move up a calculation group.
         *
         * @param {jQuery|HTMLElement|*} $group - the group to move.
         * @return {jQuery|HTMLElement|*} - the moved group.
         */
        moveGroupUp: function ($group) {
            const $target = $group.prevAll('.group:first');
            if ($target.length && $target !== $group) {
                return this.moveGroup($group, $target, true);
            }

            return $group;
        },

        /**
         * Move down a calculation group.
         *
         * @param {jQuery|HTMLElement|*} $group - the group to move.
         * @return {jQuery|HTMLElement|*} - the moved group.
         */
        moveGroupDown: function ($group) {
            const $target = $group.nextAll('.group:first');
            if ($target.length && $target !== $group) {
                return this.moveGroup($group, $target, false);
            }
            return $group;
        },

        /**
         * Move a source category before or after the target category.
         *
         * @param {jQuery|*} $source - the category to move.
         * @param {jQuery|*} $target - the target category.
         * @param {boolean} up - true to move before the target (up); false to move after (down).
         * @return {jQuery|HTMLElement|*} - the moved category.
         */
        moveCategory: function ($source, $target, up) {
            // hide menus
            $.hideDropDownMenus();

            // check
            if ($source && $target && $source !== $target) {
                // move
                if (up) {
                    $source.insertBefore($target);
                } else {
                    $source.insertAfter($target);
                }

                // update
                $source.scrollInViewport().find('tr:first').timeoutToggle('table-success');
                Application.updatePositions();
                Application.updateButtons();
            }
            return $source;
        },

        /**
         * Move a calculation category to the first position.
         *
         * @param {jQuery|HTMLElement|*} $category - the category to move.
         * @return {jQuery|HTMLElement|*} the moved category.
         */
        moveCategoryFirst: function ($category) {
            const $target = $category.prevUntil('thead').last();
            if ($target.length && $target !== $category) {
                return this.moveCategory($category, $target, true);
            }
            return $category;
        },

        /**
         * Move a calculation category to the last position.
         *
         * @param {jQuery|HTMLElement|*} $category - the category to move.
         * @return {jQuery|HTMLElement|*} the moved category.
         */
        moveCategoryLast: function ($category) {
            const $target = $category.nextUntil('thead').last();
            if ($target.length && $target !== $category) {
                return this.moveCategory($category, $target, false);
            }
            return $category;
        },

        /**
         * Move up a calculation category.
         *
         * @param {jQuery|HTMLElement|*} $category - the category to move.
         * @return {jQuery|HTMLElement|*} - the moved category.
         */
        moveCategoryUp: function ($category) {
            const $target = $category.prev();
            if ($target.length && $target !== $category) {
                return this.moveCategory($category, $target, true);
            }

            return $category;
        },

        /**
         * Move down a calculation category.
         *
         * @param {jQuery|HTMLElement|*} $category - the category to move.
         * @return {jQuery|HTMLElement|*} the moved category.
         */
        moveCategoryDown: function ($category) {
            const $target = $category.next();
            if ($target.length && $target !== $category) {
                return this.moveCategory($category, $target, false);
            }
            return $category;
        },
        /**
         * Move a source item before or after the target item.
         *
         * @param {jQuery|HTMLElement|*} $source - the item to move.
         * @param {jQuery|HTMLElement|*} $target - the target item.
         * @param {boolean} up - true to move before the target (up); false to move after (down).
         * @return {jQuery|HTMLElement|*} the moved item.
         */
        moveItem: function ($source, $target, up) {
            // hide menus
            $.hideDropDownMenus();

            // check
            if ($source && $target && $source !== $target) {
                // move
                if (up) {
                    $source.insertBefore($target);
                } else {
                    $source.insertAfter($target);
                }

                // update
                $source.scrollInViewport().timeoutToggle('table-success');
                Application.updatePositions();
                Application.updateButtons();
            }
            return $source;
        },

        /**
         * Move a calculation item to the first position.
         *
         * @param {jQuery|HTMLElement|*} $item - the item to move.
         * @return {jQuery|HTMLElement|*} - the moved item.
         */
        moveItemFirst: function ($item) {
            const index = $item.index();
            if (index > 1 && $item.prev()) {
                const $target = $item.siblings(':nth-child(2)');
                return this.moveItem($item, $target, true);
            }
            return $item;
        },

        /**
         * Move a calculation item to the last position.
         *
         * @param {jQuery|HTMLElement|*} $item - the item to move.
         * @return {jQuery|HTMLElement|*} - the moved item.
         */
        moveItemLast: function ($item) {
            const index = $item.index();
            const count = $item.siblings().length;
            if (index < count && $item.next()) {
                const $target = $item.siblings(':last');
                return this.moveItem($item, $target, false);
            }
            return $item;
        },

        /**
         * Move up a calculation item.
         *
         * @param {jQuery|HTMLElement|*} $item - the item to move.
         * @return {jQuery|HTMLElement|*} - the moved item.
         */
        moveItemUp: function ($item) {
            const index = $item.index();
            if (index > 1 && $item.prev()) {
                const $target = $item.prev();
                return this.moveItem($item, $target, true);
            }
            return $item;
        },

        /**
         * Move down a calculation item.
         *
         * @param {jQuery|HTMLElement|*} $item - the item to move.
         * @return {jQuery|HTMLElement|*} - the moved item.
         */
        moveItemDown: function ($item) {
            const index = $item.index();
            const count = $item.siblings().length;
            if (index < count && $item.next()) {
                const $target = $item.next();
                return this.moveItem($item, $target, false);
            }
            return $item;
        }
    };

    /**
     * Ready function
     */
    $(function () {
        // move rows
        MoveHandler.init();

        // application
        Application.init();

        // context menu
        const $tableEdit = $('.table-edit');
        const selector = '.table-edit th:not(.d-print-none),.table-edit td:not(.d-print-none,:has(:input))';
        const show = function () {
            $.hideDropDownMenus();
            $(this).parents('tr').addClass('table-primary');
        };
        const hide = function () {
            $(this).parents('tr').removeClass('table-primary');
        };
        $tableEdit.initContextMenu(selector, show, hide);

        // edit in place (price and quantity)
        $tableEdit.on('click', 'td.text-editable', function () {
            const $cell = $(this);
            $cell.cellEdit({
                type: 'number',
                required: true,
                autoEdit: true,
                autoDispose: true,
                useNumberFormat: true,
                inputClass: 'form-control form-control-sm text-end skip-validation',
                attributes: {
                    inputmode: 'decimal',
                    scale: 2
                },
                parser: function (value) {
                    return $.parseFloat(value);
                },
                formatter: function (value) {
                    return $.formatFloat(value);
                },
                onStartEdit: function () {
                    $.hideDropDownMenus();
                    $cell.removeClass('empty-cell');
                },
                onEndEdit: function (oldValue, newValue) {
                    const $row = $cell.parents('tr');
                    $row.timeoutToggle('table-success');
                    if (oldValue !== newValue) {
                        $row.updateTotal();
                        Application.updateTotals(false);
                    } else {
                        $('#data-table-edit').updateErrors();
                    }
                },
                onCancelEdit: function () {
                    $('#data-table-edit').updateErrors();
                }
            });
        });

        // user margin
        const $margin = $('#calculation_userMargin');
        $margin.data('value', $margin.intVal()).on('input', function () {
            $margin.updateTimer(function () {
                const oldValue = $margin.data('value');
                const newValue = $margin.intVal();
                if (oldValue !== newValue) {
                    $margin.data('value', newValue);
                    Application.updateTotals(false);
                }
            }, 250);
        });

        // main form validation
        const $form = $('#edit-form');
        $form.initValidator({
            spinner: {
                parent: $('#main-content')
            }
        });

        // initialize the type ahead
        $('#calculation_customer').initTypeahead({
            url: $form.data('search-customer'),
            error: $form.data('error-customer')
        });

        // edit the default product if new calculation
        const edit = $form.data('edit') || false;
        const $button = $('#data-table-edit .dropdown-item.btn-edit-item');
        if (edit && $button.length === 1) {
            Application.showEditItemDialog($button);
        }
    });
}(jQuery));
