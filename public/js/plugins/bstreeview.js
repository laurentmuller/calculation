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
        this.itemIdPrefix = element.id + "-item-";
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
            const $element = that.$element;

            that.tree = [];
            that.nodes = [];

            // retrieve Json Data.
            if (that.options.data) {
                if (that.options.data.isPrototypeOf(String)) {
                    that.options.data = $.parseJSON(this.options.data);
                }
                that.tree = $.extend(true, [], this.options.data);
                delete that.options.data;
            } else if (that.options.url) {
                $.get(that.options.url, function (data) {
                    that.tree = $.extend(true, [], data);
                    that.initData({
                        nodes: that.tree
                    });
                    that.build(that.$element, that.tree, 0);
                    that.selectFirst();
                });
            }

            // set main class to element.
            $element.addClass('bstreeview ' + this.options.treeClass);

            // initialise
            that.initData({
                nodes: that.tree
            });

            // build
            that.build($element, that.tree, 0);
            that.selectFirst();

            // handle events
            $element.on('click', '.list-group-item', function (e) {
                // update state icon
                $('.state-icon', this)
                    .toggleClass(that.options.expandIcon)
                    .toggleClass(that.options.collapseIcon);

                // navigate to href if present
                if (e.target.hasAttribute('href')) {
                    if (that.options.openNodeLinkOnNewTab) {
                        window.open(e.target.getAttribute('href'), '_blank');
                    } else {
                        window.location = e.target.getAttribute('href');
                    }
                }

                that.setSelection($(this));

                // }).on('mouseenter', '.list-group-item', function () {
                // const $this = $(this);
                // $this.addClass(that.options.hoverClass)
                // .removeClass($this.data('class') || '');
                // }).on('mouseleave', '.list-group-item', function () {
                // const $this = $(this);
                // $this.removeClass(that.options.hoverClass)
                // .addClass($this.data('class') || '');
            });
        },

        /**
         * Initialize data.
         */
        initData: function (node) {
            if (node.nodes && node.nodes.length) {
                const that = this;
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
        },

        /**
         * Build nodes.
         */
        build: function ($parentElement, nodes, depth) {
            const that = this;

            // calculate item padding
            let paddingLeft = that.options.parentIndent + "rem;";
            if (depth > 0) {
                paddingLeft = (that.options.indent + depth * that.options.indent).toString() + "rem;";
            }
            depth++;

            const templates = that.options.templates;

            // add each node and sub-nodes
            $.each(nodes, function (index, node) {
                // main node element.
                const $treeItem = $(templates.item)
                    .attr('data-target', "#" + that.itemIdPrefix + node.nodeId)
                    .attr('style', 'padding-left:' + paddingLeft)
                    .attr('aria-level', depth);

                // set expand or collapse icons
                if (node.nodes && node.nodes.length) {
                    $(templates.stateIcon)
                        .addClass(node.expanded ? that.options.expandIcon : that.options.collapseIcon)
                        .appendTo($treeItem);
                }

                // set node icon if exist
                if (node.icon) {
                    $(templates.itemIcon)
                        .addClass(node.icon)
                        .appendTo($treeItem);
                }

                // set text
                $treeItem.append(node.text);

                // set badge if present
                const badgeValue = node.badgeValue || (that.options.badgeCount && node.nodes ? node.nodes.length : false);
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

                // attach node to parent.
                $parentElement.append($treeItem);

                // build child nodes
                if (node.nodes && node.nodes.length) {
                    // node group item
                    var $treeGroup = $(templates.groupItem)
                        .attr('id', that.itemIdPrefix + node.nodeId);

                    // expand the node if requested
                    if (node.expanded) {
                        $treeGroup.addClass('show');
                    }
                    $parentElement.append($treeGroup);
                    that.build($treeGroup, node.nodes, depth);
                }
            });
        },

        /**
         * Select the first element.
         */
        selectFirst: function() {
            this.setSelection(this.$element.find('.list-group-item:first'));
        },

        /**
         * Select the given element.
         */
        setSelection: function($selection) {
            const selection = this.options.selectionClass;
            this.$element.find('.list-group-item').removeClass(selection);
            if ($selection && $selection.length) {
                $selection.addClass(selection);
            }
        },

        getSelection: function () {
            const selectionClass = this.options.selectionClass;
            const $selection = this.$element.find('.list-group-item').hasClass(selectionClass);
            return $selection.length ? $selection : null;
        },

        /**
         * Collapse all.
         */
        collapseAll: function () {
            this.$element.find('.list-group.collapse.show').each(function () {
                $(this).prev('.list-group-item').trigger('click');
            });
            this.selectFirst();
        },

        /**
         * Expand all.
         */
        expandAll: function () {
            // '.list-group-item'
            this.$element.find('.list-group.collapse:not(.show)').each(function () {
                $(this).prev('.list-group-item').trigger('click');
            });
        },

        /**
         * Expand to the given level.
         */
        expandToLevel: function (level) {
            this.$element.find('.list-group.collapse').each(function () {
                const displayed = $(this).hasClass('show');
                const $group = $(this).prev('.list-group-item');
                const groupLevel = Number.parseInt($group.attr('aria-level'), 10);
                if (displayed && groupLevel > level || !displayed && groupLevel <= level) {
                    $group.trigger('click');
                }
            });
            console.log(this.getSelection());
            this.selectFirst();
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    BoostrapTreeView.DEFAULTS = {
        url: null,
        treeClass: 'border rounded',
        hoverClass: 'active',
        selectionClass: 'active',
        expandIcon: 'fas fa-caret-down fa-fw',
        collapseIcon: 'fas fa-caret-right fa-fw',
        indent: 1.25,
        parentIndent: 1.25,
        openNodeLinkOnNewTab: true,
        badgeCount: false,
        templates: {
            item: '<div role="treeitem" class="list-group-item" data-toggle="collapse"></div>',
            groupItem: '<div role="group" class="list-group collapse" id="itemid"></div>',
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
