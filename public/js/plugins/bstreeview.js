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
        this.proxy = $.proxy(this.click, this);
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
         */
        init: function () {
            const that = this;
            const options = that.options;
            const $element = that.$element;

            that.tree = [];
            that.nodes = [];

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
            $element.addClass('bstreeview ' + this.options.treeClass);

            // handle events
            $element.on('click', '.list-group-item', this.proxy);
        },

        /**
         * Initialize data.
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
         */
        build: function ($parentElement, nodes, depth) {
            const that = this;
            const options = that.options;
            const templates = options.templates;

            // calculate item padding
            let paddingLeft = options.parentIndent;
            if (depth > 0) {
                paddingLeft = (depth + 1) * options.indent;
            }
            depth++;

            // add each node and sub-nodes
            $.each(nodes, function (index, node) {
                // main node element.
                const $treeItem = $(templates.item)
                    .attr('style', 'padding-left:' + paddingLeft + "rem;")
                    .attr('aria-level', depth);

                // set expand or collapse icons
                if (node.nodes && node.nodes.length) {
                    $(templates.stateIcon)
                        .addClass(node.expanded ? options.expandIcon : options.collapseIcon)
                        .appendTo($treeItem);
                }

                // set node icon if exist
                if (node.icon) {
                    $(templates.itemIcon)
                        .addClass(node.icon)
                        .appendTo($treeItem);
                }

                // set text
                $treeItem.append(node.text || '');

                // set badge if present
                const badgeValue = node.badgeValue || (options.badgeCount && node.nodes ? node.nodes.length : false);
                if (badgeValue) {
                    $(templates.itemBadge)
                        .addClass(node.badgeClass || 'badge-primary')
                        .text(badgeValue)
                        .appendTo($treeItem);
                }

                // reset node href if present
                if (node.href) {
                    $treeItem.attr('href', node.href);
                }

                // add class to node if present
                if (node.class) {
                    $treeItem.addClass(node.class)
                        .data('class', node.class);
                }

                // add custom id to node if present
                if (node.id) {
                    $treeItem.attr('id', node.id);
                }

                // attach node to parent
                $parentElement.append($treeItem);

                // build child nodes
                if (node.nodes && node.nodes.length) {
                    // group item
                    const $treeGroup = $(templates.groupItem)
                        .toggle(node.expanded)
                        .appendTo($parentElement);

                    // children
                    that.build($treeGroup, node.nodes, depth);
                }
            });
            return that;
        },

        /**
         * Handle item click
         */
        click: function (e) {
            // toggle group
            const $target = $(e.currentTarget);
            this.toggleGroup($target.next('.list-group'));

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

        focus: function() {
            const $selection = this.getSelection();
            if ($selection) {
                $selection.focus();
            }
            return this;
        },

        /**
         * Toggle group visibility
         */
        toggleGroup($group) {
            const options = this.options;
            const $item = $group.prev('.list-group-item');
            const $icon = $item.find('.state-icon');
            $group.toggle(options.toggleDuration);
            $icon.toggleClass(options.collapseIcon)
                .toggleClass(options.expandIcon);
        },

        /**
         * Destroy.
         */
        destroy: function () {
            this.$element.off('click', '.list-group-item', this.proxy);
            this.$element.removeData('boostrapTreeView');
        },

        /**
         * Select the first element.
         */
        selectFirst: function() {
            return this.setSelection(this.$element.find('.list-group-item:first'));
        },

        /**
         * Select the given element.
         */
        setSelection: function($selection) {
            const selectionClass = this.options.selectionClass;
            this.$element.find('.list-group-item').removeClass(selectionClass);
            if ($selection && $selection.length) {
                $selection.addClass(selectionClass);
            }
            return this;
        },

        /**
         * Gets the selected element.
         */
        getSelection: function () {
            const selectionClass = this.options.selectionClass;
            const $filter = this.$element.find('.list-group-item').filter(function () {
                return $(this).hasClass(selectionClass);
            });
            return $filter.length ? $filter : null;
        },

        /**
         * Refresh data
         */
        refresh: function () {
            this.$element.off('click', '.list-group-item', this.proxy);
            this.$element.children().remove();
            this.init();
            return this;
        },

        /**
         * Collapse all.
         */
        collapseAll: function () {
            const that = this;
            this.$element.find('.list-group:visible').each(function () {
                that.toggleGroup($(this));
            });
            return this.selectFirst();
        },

        /**
         * Expand all.
         */
        expandAll: function () {
            const that = this;
            this.$element.find('.list-group:not(:visible)').each(function () {
                that.toggleGroup($(this));
            });
            return that;
        },

        /**
         * Expand to the given level.
         */
        expandToLevel: function (level) {
            const that = this;
            this.$element.find('.list-group').each(function () {
                const $this = $(this);
                const visible = $this.is(':visible');
                const $item = $this.prev('.list-group-item');
                const groupLevel = Number.parseInt($item.attr('aria-level'), 10);
                if (visible && groupLevel > level || !visible && groupLevel <= level) {
                    that.toggleGroup($this);
                }
            });
            return that;
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    BoostrapTreeView.DEFAULTS = {
        url: null,
        toggleDuration: 400,
        loading: 'Loading...',
        treeClass: 'border rounded',
        selectionClass: 'list-group-item-primary',
        expandIcon: 'fas fa-caret-down fa-fw',
        collapseIcon: 'fas fa-caret-right fa-fw',
        indent: 1.25,
        parentIndent: 1.25,
        openNodeLinkOnNewTab: true,
        badgeCount: false,
        templates: {
            item: '<button type="button" role="treeitem" class="list-group-item list-group-item-action text-left"></button>',
            groupItem: '<div role="group" class="list-group" id="itemid"></div>',
            stateIcon: '<i class="state-icon"></i>',
            itemIcon: '<i class="item-icon"></i>',
            itemBadge: '<span class="item-badge badge badge-pill"></span>'
        }
    };

    // -----------------------------------
    // BoostrapTreeView plugin definition
    // -----------------------------------
    const oldBoostrapTreeView = $.fn.boostrapTreeView;

    $.fn.boostrapTreeView = function (option) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('boostrapTreeView');
            if (!data) {
                const options = typeof option === 'object' && option;
                $this.data('boostrapTreeView', data = new BoostrapTreeView(this, options));
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
