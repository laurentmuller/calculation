/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // Sidebar public class definition
    // ------------------------------------
    const Sidebar = class {

        // -----------------------------
        // public functions
        // -----------------------------
        constructor(element, options) {
            const that = this;
            that.$element = $(element);
            that.options = $.extend(true, {}, Sidebar.DEFAULTS, that.$element.data(), options);
            that._init();
        }

        destroy() {
            const that = this;
            that.$sidebarToggle.off('click', that.toggleSidebarProxy);
            that.$element.off('click', '.nav-link-toggle', that.toggleMenuProxy);
            that.$element.removeData('sidebar');
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize the plugin.
         */
        _init() {
            // get elements
            const that = this;
            that.$pageContent = $(that.options.pageContent);
            that.$sidebarToggle = $(that.options.sidebarToggle);
            that.$sidebarHorizontal = $(that.options.sidebarHorizontal);
            // create and add proxies
            that.toggleSidebarProxy = function (e) {
                that._toggleSidebar(e);
            };
            that.toggleMenuProxy = function (e) {
                that._toggleMenu(e);
            };
            that.$sidebarToggle.on('click', that.toggleSidebarProxy);
            that.$element.on('click', '.nav-link-toggle', that.toggleMenuProxy);
        }

        /**
         * Gets the navigation state.
         */
        _getState() {
            const menus = {
                'menu_active': this.$element.hasClass('active')
            };

            let visible;
            let wasVisible = false;
            this.$element.find('.nav-item-dropdown[id]').each(function () {
                const $element = $(this);
                visible = $element.find('.navbar-menu').is(':visible');
                // check that only one menu is visible
                if (wasVisible && visible) {
                    visible = false;
                } else if (!wasVisible && visible) {
                    wasVisible = true;
                }
                menus[$element.attr('id')] = visible;
            });

            return menus;
        }

        /**
         * Save the navigation state.
         */
        _saveState() {
            const url = this.options.url;
            if (url) {
                $.ajaxSetup({global: false});
                $.post(url, this._getState()).always(function () {
                    $.ajaxSetup({global: true});
                });
            }
        }

        /**
         * Collapse all menus
         */
        _collapseMenus() {
            const $toggle = this.$element.find('.nav-item-dropdown .nav-link-toggle.nav-link-toggle-show');
            if ($toggle.length) {
                const title = this.options.show;
                $toggle.removeClass('nav-link-toggle-show').attr({
                    'aria-expanded': 'false',
                    'title': title
                });
                this.$element.find('.navbar-menu:visible').hide(350);
            }
        }

        /**
         * Toggle the sidebar.
         *
         * @param {Event} e - the event.
         */
        _toggleSidebar(e) {
            e.stopPropagation();
            // hide drop-down menus
            $('.dropdown-menu.show, .dropdown.show').removeClass('show');
            this.$element.add(this.$pageContent).toggleClass('active');
            const active = this.$element.hasClass('active');
            const $toggle = this.$sidebarHorizontal.find('.nav-sidebar-horizontal');
            if (active) {
                $toggle.show(350);
            } else {
                $toggle.hide(350);
            }
            const title = active ? this.options.show : this.options.hide;
            this.$sidebarToggle.attr('title', title);
            this._saveState();
        }

        /**
         * Toggle a menu.
         *
         * @param {Event} e - the event.
         */
        _toggleMenu(e) {
            e.stopPropagation();
            const that = this;
            const $link = $(e.currentTarget);
            const $parent = $link.parents('.nav-item-dropdown');
            const $menu = $parent.find('.navbar-menu');
            const visible = $menu.is(':visible');
            if (!visible) {
                that._collapseMenus();
            }
            $link.toggleClass('nav-link-toggle-show');
            $menu.toggle(350, function () {
                const title = visible ? that.options.show : that.options.hide;
                $link.attr({
                    'aria-expanded': String(visible),
                    'title': title
                });
                that._saveState();
            });
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    Sidebar.DEFAULTS = {
        url: null,
        sidebarHorizontal: '.navbar-horizontal',
        sidebarToggle: '.sidebar-toggle',
        pageContent: '.page-content',
        show: 'Expand',
        hide: 'Collapse',
    };

    // -----------------------------
    // sidebar plugin definition
    // -----------------------------
    const oldSidebar = $.fn.sidebar;

    $.fn.sidebar = function (options) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('sidebar');
            if (!data) {
                const settings = typeof options === 'object' && options;
                $this.data('sidebar', data = new Sidebar(this, settings));
            }
        });
    };

    $.fn.sidebar.Constructor = Sidebar;

}(jQuery));
