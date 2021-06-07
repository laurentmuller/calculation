/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function($) {
    "use strict";

    // -----------------------------
    // Initialization
    // -----------------------------
    var BoostrapTreeView = function (element, options) {
        this.$element = $(element);
        this.options = $.extend(true, {}, BoostrapTreeView.DEFAULTS, this.$element.data(), options);
        this.init();
    };

    // -----------------------------
    // Prototype functions
    // -----------------------------
    BoostrapTreeView.prototype = {

        /**
         * Constructor.
         */
        constructor: BoostrapTreeView,

        /**
         * Initialize widget.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        init: function () {
            const that = this;
            const options = that.options;
            const $element = that.$element;

            that.tree = [];
            that.nodes = [];
            that.clickProxy = $.proxy(that.click, that);
            that.keyDownProxy = $.proxy(that.keydown, that);

            // retrieve Json Data.
            if (options.data) {
                if (options.data.isPrototypeOf(String)) {
                    options.data = $.parseJSON(options.data);
                }
                that.tree = $.extend(true, [], options.data);

                // initialise
                if (that.tree.length) {
                    that.initData({
                        nodes: that.tree
                    });
                    that.build($element, that.tree, 0);
                    that.selectFirst();
                }
            } else if (options.url) {
                $('*').css('cursor', 'wait');
                const $loading = $(options.templates.item)
                    .addClass(options.loadingClass)
                    .text(options.loading)
                    .appendTo($element);
                $.getJSON(options.url, function (data) {
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
                    that.selectFirst();
                    $('*').css('cursor', '');
                });
            }

            // set main class to element.
            $element.addClass('bstreeview ' + options.treeClass);

            // handle events
            $element.on('click', '.list-group-item', that.clickProxy);
            $element.on('keydown', '.list-group-item', that.keyDownProxy);

            return that;
        },

        /**
         * Initialize data.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        initData: function (node) {
            const that = this;
            if (node.nodes && node.nodes.length) {
                const parent = node;
                $.each(node.nodes, function (index, node) {
                    node.nodeId = that.nodes.length;
                    node.parentId = parent.nodeId;
                    that.nodes.push(node);
                    if (node.nodes && node.nodes.length) {
                        that.initData(node);
                    }
                });
            }
            return that;
        },

        /**
         * Build nodes.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        build: function ($parent, nodes, depth) {
            const that = this;
            const options = that.options;
            const templates = options.templates;

            // calculate padding
            let paddingLeft = options.parentIndent;
            if (depth > 0) {
                paddingLeft = (depth + 1) * options.indent;
            }
            depth++;

            // add each node and sub-nodes
            $.each(nodes, function (index, node) {
                // create node element.
                const $item = $(templates.item)
                    .css('padding-left', paddingLeft + 'rem')
                    .attr('aria-level', depth);

                // add toggle icons
                if (node.nodes && node.nodes.length) {
                    $(templates.stateIcon)
                        .addClass(node.expanded ? options.expandIcon : options.collapseIcon)
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
                    $item.append(node.text);
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
        },

        /**
         * Handle item click.
         *
         * @param {Event}
         *            e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        click: function (e) {
            // toggle group
            const $target = $(e.currentTarget);
            this.toggleGroup($target.next('.list-group'));

            // notify

            // navigate to href if present
            if ($target.attr('href')) {
                if (this.options.openNodeLinkOnNewTab) {
                    window.open($target.attr('href'), '_blank');
                } else {
                    window.location = $target.attr('href');
                }
            }
            return this.setSelection($target);
        },

        /**
         * Handle item key down event
         *
         * @param {Event}
         *            e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        keydown: function (e) {
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
        },

        /**
         * Toggle the visibility of the given group.
         *
         * @param {jQuery}
         *            $group - the group to toggle.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        toggleGroup($group) {
            const that = this;
            if ($group && $group.length) {
                const options = that.options;
                const $item = $group.prev('.list-group-item');
                const $icon = $item.find('.state-icon');

                const event = $.Event("togglegroup", {
                    'item': $item,
                    'expanded': $icon.hasClass(options.expandIcon)});
                if (!that.trigger(event)) {
                    return that;
                }


                $group.toggle(options.toggleDuration, function() {
                    $icon.toggleClass(options.collapseIcon)
                        .toggleClass(options.expandIcon);
                });

            }
            return that;
        },

        /**
         * Destroy.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        destroy: function () {
            this.$element.off('click', '.list-group-item', this.clickProxy);
            this.$element.off('keydown', '.list-group-item', this.keyDownProxy);
            this.$element.removeData('boostrapTreeView');
            return this;
        },

        /**
         * Select the first element.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        selectFirst: function() {
            return this.setSelection(this.$element.find('.list-group-item:first'));
        },

        /**
         * Select the given element.
         *
         * @param {jQuery}
         *            $$selection - the item to select.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        setSelection: function($selection) {
            const selectionClass = this.options.selectionClass;
            if ($selection && $selection.length) {
                this.$element.find('.list-group-item').removeClass(selectionClass);
                $selection.addClass(selectionClass).focus();
            }
            return this;
        },

        /**
         * Gets the selected item.
         *
         * @return {jQuery} the selected item, if any; null otherwise.
         */
        getSelection: function () {
            const selectionClass = this.options.selectionClass;
            const $filter = this.$element.find('.list-group-item:visible').filter(function () {
                return $(this).hasClass(selectionClass);
            });
            return $filter.length ? $filter : null;
        },

        /**
         * Set focus to the selected item.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        focus: function () {
            const $selection = this.getSelection();
            if ($selection) {
                $selection.focus();
            } else {
                this.$element.focus();
            }
            return this;
        },

        /**
         * Trigger the given event.
         *
         * @param {Event}
         *            e - the event to trigger.
         * @return true if the event is not prevented; false otherwise.
         */
        trigger: function(e) {
            if (e) {
                this.$element.trigger(e);
                return !e.isDefaultPrevented();
            }
            return false;
        },

        /**
         * Refresh data.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        refresh: function () {
            this.$element.off('click', '.list-group-item', this.clickProxy);
            this.$element.off('keydown', '.list-group-item', this.keyDownProxy);
            this.$element.children().remove();
            this.init();
            return this;
        },

        /**
         * Collapse all items.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        collapseAll: function () {
            const that = this;
            const $groups = that.$element.find('.list-group:visible');
            if ($groups.length === 0) {
                return that;
            }

            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const event = $.Event("collapseall", {'items': $items});
            if (!that.trigger(event)) {
                return that;
            }

            $groups.each(function () {
                that.toggleGroup($(this));
            });

            return that.selectFirst();
        },

        /**
         * Expand all items.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        expandAll: function () {
            const that = this;
            const $groups = this.$element.find('.list-group:not(:visible)');
            if ($groups.length === 0) {
                return that;
            }

            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const event = $.Event("expandall", {'items': $items});
            if (!that.trigger(event)) {
                return that;
            }

            $groups.each(function () {
                that.toggleGroup($(this));
            });

            return that;
        },

        /**
         * Expand to the given level.
         *
         * @param {int}
         *            level - the level.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        expandToLevel: function (level) {
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
            const event = $.Event("expandtolevel", {
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
        toggleDuration: 400,
        loading: 'Loading...',
        loadingClass: 'list-group-item-success',
        treeClass: 'list-group border',
        selectionClass: 'list-group-item-primary',
        expandIcon: 'fas fa-caret-down fa-fw',
        collapseIcon: 'fas fa-caret-right fa-fw',
        indent: 1.25,
        parentIndent: 1.25,
        openNodeLinkOnNewTab: true,
        badgeCount: false,
        badgeClass: 'badge-primary',
        templates: {
            item: '<button type="button" role="treeitem" class="list-group-item list-group-item-action" />',
            groupItem: '<div role="group" class="list-group" />',
            stateIcon: '<i class="state-icon" />',
            itemIcon: '<i class="item-icon" />',
            itemBadge: '<span class="float-right item-badge badge badge-pill" />'
        }
    };

    // -----------------------------------
    // BoostrapTreeView plugin definition
    // -----------------------------------
    const oldBoostrapTreeView = $.fn.boostrapTreeView;

    $.fn.boostrapTreeView = function (options) {
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
