/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // -----------------------------
    // Initialization
    // -----------------------------
    const BoostrapTreeView = class {

        /**
         * Constructor.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, BoostrapTreeView.DEFAULTS, this.$element.data(), options);
            this.init();
        }

        /**
         * Initialize widget.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        init() {
            const that = this;
            const options = that.options;
            const $element = that.$element;

            that.tree = [];
            that.nodes = [];
            that.clickProxy = $.proxy(that.click, that);
            that.doubleclickProxy = $.proxy(that.doubleclick, that);
            that.keydownProxy = $.proxy(that.keydown, that);
            that.toggling = false;

            // retrieve Json Data.
            if (options.data) {
                // get data
                if (options.data.isPrototypeOf(String)) {
                    options.data = JSON.parse(options.data);
                }
                that.tree = $.extend(true, [], options.data);

                // initialise
                if (that.tree.length) {
                    that.initData({
                        nodes: that.tree
                    });
                    that.build($element, that.tree, 0);
                    that.updateBorders().selectFirst();
                }
            } else if (options.url) {
                $('*').css('cursor', 'wait');
                const $loading = $(options.templates.item)
                    .addClass(options.loadingClass)
                    .text(options.loadingText)
                    .appendTo($element);
                $.getJSON(options.url, function (data) {
                    // get data
                    if (data.isPrototypeOf(String)) {
                        data = JSON.parse(data);
                    }
                    that.tree = $.extend(true, [], data);

                    // initialise
                    if (that.tree.length) {
                        that.initData({
                            nodes: that.tree
                        });
                        that.build(that.$element, that.tree, 0);
                    }

                }).always(function () {
                    $loading.remove();
                    that.updateBorders().selectFirst();
                    $('*').css('cursor', '');
                });
            }

            $element.on('click', '.list-group-item, .state-icon', that.clickProxy);
            $element.on('dblclick', '.list-group-item', that.doubleclickProxy);
            $element.on('keydown', '.list-group-item', that.keydownProxy);

            return that;
        }

        /**
         * Initialize data.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        initData(node) {
            const that = this;
            if (node.nodes && node.nodes.length) {
                const parent = node;
                $.each(node.nodes, function (_index, node) {
                    node.nodeId = that.nodes.length;
                    node.parentId = parent.nodeId;
                    that.nodes.push(node);
                    if (node.nodes && node.nodes.length) {
                        that.initData(node);
                    }
                });
            }
            return that;
        }

        /**
         * Build nodes.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        build($parent, nodes, depth) {
            const that = this;
            const options = that.options;
            const templates = options.templates;

            // calculate padding
            let paddingLeft = options.parentIndent;
            if (depth > 0) {
                paddingLeft += depth * options.indent;
            }
            depth++;

            // add each node and sub-nodes
            $.each(nodes, function (_index, node) {
                // create node element.
                const $item = $(templates.item)
                    .css('padding-left', paddingLeft + 'rem')
                    .attr('aria-level', depth);

                // add toggle icons
                if (node.nodes && node.nodes.length) {
                    $(templates.stateIcon)
                        .addClass(node.expanded ? options.expandIcon : options.collapseIcon)
                        .attr('title', node.expanded ? options.texts.collapse : options.texts.expand)
                        .appendTo($item);
                }

                // set node icon if present
                if (node.icon) {
                    $(templates.itemIcon)
                        .addClass(node.icon)
                        .appendTo($item);
                }

                // set node text if present
                if (node.text) {
                    $(templates.itemText)
                        .text(node.text)
                        .appendTo($item);
                }

                // set badge if present
                const badgeValue = node.badgeValue || (options.badgeCount && node.nodes ? node.nodes.length : false);
                if (badgeValue) {
                    $(templates.itemBadge)
                        .addClass(node.badgeClass || options.badgeClass)
                        .text(badgeValue)
                        .appendTo($item);
                }

                // set node href if present
                if (node.href) {
                    $item.attr('href', node.href);
                }

                // add disable class if present
                if (node.disable) {
                    $item.addClass('disable')
                        .attr('disable', 'disable');
                }

                // add class to node if present
                if (node.class) {
                    $item.addClass(node.class);
                }

                // add custom id to node if present
                if (node.id) {
                    $item.attr('id', node.id);
                }

                // attach item to parent
                $parent.append($item);

                // build child nodes
                if (node.nodes && node.nodes.length) {
                    // group item
                    const $groupItem = $(templates.groupItem)
                        .toggle(node.expanded)
                        .appendTo($parent);

                    // children
                    that.build($groupItem, node.nodes, depth);
                }
            });
            return that;
        }

        /**
         * Handle item and state icon click.
         *
         * @param {Event}
         *            e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        click(e) {
            const $target = $(e.currentTarget);
            const $item = $target.closest('.list-group-item');
            if ($target.is('.state-icon')) {
                this.toggleGroup($item.next('.list-group'));
            } else {
                const href = $item.attr('href');
                if (href) {
                    if (this.options.openNodeLinkOnNewTab) {
                        window.open(href, '_blank');
                    } else {
                        window.location = href;
                    }
                }
            }

            return this.setSelection($item);
        }

        /**
         * Handle the item double-click.
         *
         * @param {Event}
         *            e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        doubleclick(e) {
            const $target = $(e.currentTarget);
            this.toggleGroup($target.next('.list-group'));
            return this.setSelection($target);
        }


        /**
         * Handle item key down event
         *
         * @param {Event}
         *            e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        keydown(e) {
            if (this.toggling) {
                return this;
            }

            const $target = $(e.currentTarget);
            switch (e.keyCode || e.which) {
            case 35: { // end => last
                const $item = this.$element.find('.list-group-item:visible:last');
                this.setSelection($item);
                break;
            }

            case 36: { // home => first
                const $item = this.$element.find('.list-group-item:visible:first');
                this.setSelection($item);
                break;
            }

            case 37: { // arrow left => collapse or parent
                const $group = $target.next('.list-group:visible');
                if ($group.length) {
                    this.toggleGroup($group);
                } else {
                    const $item = $target.parents('.list-group').prev('.list-group-item:first');
                    this.setSelection($item);
                }
                break;
            }

            case 38:{ // arrow up => select previous
                const $items = this.$element.find('.list-group-item:visible');
                const index = $items.index($target);
                if (index > 0) {
                    const $item = $items.eq(index - 1);
                    this.setSelection($item);
                }
                break;
            }

            case 39: { // arrow right => expand or select first child
                const $group = $target.next('.list-group:first:not(:visible)');
                if ($group.length) {
                    this.toggleGroup($group);
                } else {
                    const $item = $target.next('.list-group:first').find('.list-group-item:first');
                    this.setSelection($item);
                }
              break;
            }

            case 40: {  // arrow down => select next
                const $items = this.$element.find('.list-group-item:visible');
                const index = $items.index($target);
                const length = $items.length;
                if (index !==-1 && index < length - 1) {
                    const $item = $items.eq(index + 1);
                    this.setSelection($item);
                }
                break;
            }

            case 49:
            case 107: { // + => expand
                const $group = $target.next('.list-group:first:not(:visible)');
                this.toggleGroup($group);
                break;
            }

            case 109:
            case 189: { // - => collapse
                const $group = $target.next('.list-group:first:visible');
                this.toggleGroup($group);
                break;
            }
            }

            return this;
        }

        /**
         * Toggle the visibility of the given group.
         *
         * @param {JQuery}
         *            $group - the group to toggle.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        toggleGroup($group) {
            const that = this;
            if ($group && $group.length) {
                const options = that.options;
                const $item = $group.prev('.list-group-item');
                const $icon = $item.find('.state-icon');
                const event = new $.Event('togglegroup', {
                    'item': $item,
                    'expanding': $icon.hasClass(options.collapseIcon)});
                if (!that.trigger(event)) {
                    return that;
                }

                that.toggling = true;
                $item.removeClass('rounded-bottom');
                $group.toggle(options.toggleDuration, function () {
                    $icon.toggleClass(options.collapseIcon)
                        .toggleClass(options.expandIcon);
                    if ($icon.hasClass(options.collapseIcon)) {
                        $icon.attr('title', options.texts.expand);
                    } else {
                        $icon.attr('title', options.texts.collapse);
                    }

                    that.updateBorders().toggling = false;

                });

            }
            return that;
        }

        /**
         * Update the borders.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        updateBorders() {
            this.$element.find('.list-group-item:first').removeClass('border-top-0');
            this.$element.find('.list-group-item.rounded-bottom').removeClass('rounded-bottom');
            this.$element.find('.list-group-item:visible:last').addClass('rounded-bottom');
            return this;
        }

        /**
         * Destroy.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        destroy() {
            this.removeProxies();
            this.$element.removeData('boostrapTreeView');
            return this;
        }

        /**
         * Select the first element.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        selectFirst() {
            return this.setSelection(this.$element.find('.list-group-item:first'));
        }

        /**
         * Select the given element.
         *
         * @param {JQuery}
         *            $$selection - the item to select.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        setSelection($selection) {
            const selectionClass = this.options.selectionClass;
            if ($selection && $selection.length) {
                this.$element.find('.list-group-item').removeClass(selectionClass);
                $selection.addClass(selectionClass).focus();
            }
            return this;
        }

        /**
         * Gets the selected item.
         *
         * @return {JQuery} the selected item, if any; null otherwise.
         */
        getSelection() {
            const selectionClass = this.options.selectionClass;
            const $filter = this.$element.find('.list-group-item:visible').filter(function () {
                return $(this).hasClass(selectionClass);
            });
            return $filter.length ? $filter : null;
        }

        /**
         * Set focus to the selected item.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        focus() {
            const $selection = this.getSelection();
            if ($selection) {
                $selection.focus();
            } else {
                this.$element.focus();
            }
            return this;
        }

        /**
         * Trigger the given event.
         *
         * @param {Event}
         *            e - the event to trigger.
         * @return true if the event is not prevented; false otherwise.
         */
        trigger(e) {
            if (e) {
                this.$element.trigger(e);
                return !e.isDefaultPrevented();
            }
            return false;
        }

        /**
         * Refresh data.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        refresh() {
            this.removeProxies();
            this.$element.children().remove();
            this.init();
            return this;
        }

        /**
         * Remove handlers proxies.
         */
        removeProxies() {
            this.$element.off('click', '.list-group-item, .state-icon', this.clickProxy);
            this.$element.off('dblclick', '.list-group-item', this.doubleclickProxy);
            this.$element.off('keydown', '.list-group-item', this.keydownProxy);
        }

        /**
         * Collapse all items.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        collapseAll() {
            const that = this;
            const $groups = that.$element.find('.list-group:visible');
            if ($groups.length === 0) {
                return that;
            }

            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const event = new $.Event('collapseall', {
                'items': $items});
            if (!that.trigger(event)) {
                return that;
            }

            $groups.each(function () {
                that.toggleGroup($(this));
            });

            return that.selectFirst();
        }

        /**
         * Expand all items.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        expandAll() {
            const that = this;
            const $groups = this.$element.find('.list-group:not(:visible)');
            if ($groups.length === 0) {
                return that;
            }

            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const event = new $.Event('expandall', {
                'items': $items});
            if (!that.trigger(event)) {
                return that;
            }

            $groups.each(function () {
                that.toggleGroup($(this));
            });

            return that;
        }

        /**
         * Expand to the given level.
         *
         * @param {int}
         *            level - the level.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        expandToLevel(level) {
            const that = this;
            const $groups = that.$element.find('.list-group').filter(function () {
                const $this = $(this);
                const visible = $this.is(':visible');
                const $item = $this.prev('.list-group-item');
                const groupLevel = Number.parseInt($item.attr('aria-level'), 10);
                return visible && groupLevel > level || !visible && groupLevel <= level;
            });
            if ($groups.length === 0) {
                return that;
            }

            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const event = new $.Event('expandtolevel', {
                'level': level,
                'items': $items});
            if (!that.trigger(event)) {
                return that;
            }

            $groups.each(function () {
                that.toggleGroup($(this));
            });

            return that.selectFirst();
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    BoostrapTreeView.DEFAULTS = {
        url: null,
        toggleDuration: 350,
        loadingText: 'Loading...',
        loadingClass: 'list-group-item-success',
        selectionClass: 'list-group-item-primary',
        expandIcon: 'fas fa-caret-down fa-fw',
        collapseIcon: 'fas fa-caret-right fa-fw',
        indent: 1.25,
        parentIndent: 1.25,
        openNodeLinkOnNewTab: true,
        badgeCount: false,
        badgeClass: 'badge-pill badge-primary',
        texts: {
            expand: 'Expand',
            collapse: 'Collapse'
        },
        templates: {
            item: '<button type="button" role="treeitem" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 border-top-0" />',
            stateIcon: '<i class="state-icon mr-1" />',
            itemIcon: '<i class="item-icon mr-1" />',
            itemText: '<span class="item-text w-100" />',
            itemBadge: '<span class="badge" />',
            groupItem: '<div role="group" class="group-item list-group rounded-0" />'
        }
    };

    // -----------------------------------
    // BoostrapTreeView plugin definition
    // -----------------------------------
    const oldBoostrapTreeView = $.fn.boostrapTreeView;

    $.fn.boostrapTreeView = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('boostrapTreeView');
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data('boostrapTreeView', data = new BoostrapTreeView(this, settings));
            }
        });
    };

    $.fn.boostrapTreeView.Constructor = BoostrapTreeView;

    // -----------------------------------
    // BoostrapTreeView no conflict
    // -----------------------------------
    $.fn.boostrapTreeView.noConflict = function () {
        $.fn.boostrapTreeView = oldBoostrapTreeView;
        return this;
    };

}(jQuery));
