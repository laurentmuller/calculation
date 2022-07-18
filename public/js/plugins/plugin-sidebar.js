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
            this.$element = $(element);
            this.options = $.extend(true, {}, Sidebar.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        destroy() {
            this.$sidebarToggle.off('click', this.toggleSidebarProxy);
            this.$element.off('click', '.nav-link-toggle', this.toggleMenuProxy);
            this.$element.removeData('sidebar');
        }

        // -----------------------------
        // private functions
        // -----------------------------
        /**
         * Initialize the plugin.
         * @private
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

            // update titles
            that._updateSidebar();
            that._updateMenus();
        }

        /**
         * Toggle the sidebar.
         *
         * @param {Event} e - the event.
         * @private
         */
        _toggleSidebar(e) {
            e.preventDefault();
            $('.dropdown-menu.show, .dropdown.show').removeClass('show');
            this.$element.add(this.$pageContent).toggleClass('active');
            const active = this.$element.hasClass('active');
            const $toggle = this.$sidebarHorizontal.find('.nav-sidebar-horizontal');
            if (active) {
                $toggle.show(350);
            } else {
                $toggle.hide(350);
            }
            this._updateSidebar();
            this._saveState();
        }

        /**
         * Toggle a menu.
         *
         * @param {Event} e - the event.
         * @private
         */
        _toggleMenu(e) {
            e.preventDefault();
            const that = this;
            const $link = $(e.currentTarget);
            const $parent = $link.parents('.nav-item-dropdown');
            const $menu = $parent.find('.navbar-menu');
            if (!$menu.is(':visible')) {
                that._collapseMenus();
            }
            $link.toggleClass('nav-link-toggle-show');
            $menu.toggle(350, function () {
                that._updateMenus();
                that._saveState();
            });
        }

        /**
         * Update the sidebar content.
         * @private
         */
        _updateSidebar() {
            const active = this.$element.hasClass('active');
            const title = active ? this.options.showSidebar : this.options.hideSidebar;
            this.$sidebarToggle.attr({
                'aria-expanded': String(!active),
                'title': title,
            });
        }

        /**
         * Update the menus content.
         * @private
         */
        _updateMenus() {
            const options = this.options;
            this.$element.find('.nav-item-dropdown .nav-link-toggle').each(function () {
                const $menu = $(this);
                const visible = $menu.parents('.nav-item-dropdown').find('.navbar-menu').is(':visible');
                const title = visible ? options.hideMenu : options.showMenu;
                $menu.attr({
                    'aria-expanded': String(visible),
                    'title': title
                });
            });
        }

        /**
         * Collapse all menus
         * @private
         */
        _collapseMenus() {
            const $toggle = this.$element.find('.nav-item-dropdown .nav-link-toggle.nav-link-toggle-show');
            if ($toggle.length) {
                $toggle.removeClass('nav-link-toggle-show').attr({
                    'title': this.options.showMenu,
                    'aria-expanded': 'false'
                });
                this.$element.find('.navbar-menu:visible').hide(350);
            }
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
         * @private
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
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    Sidebar.DEFAULTS = {
        url: null,
        sidebarHorizontal: '.navbar-horizontal',
        sidebarToggle: '.sidebar-toggle',
        pageContent: '.page-content',
        showSidebar: 'Show Sidebar',
        hideSidebar: 'Hide Sidebar',
        showMenu: 'Expand',
        hideMenu: 'Collapse',
    };

    // -----------------------------
    // sidebar plugin definition
    // -----------------------------
    const oldSidebar = $.fn.sidebar;

    $.fn.sidebar = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data('sidebar')) {
                const settings = typeof options === 'object' && options;
                $this.data('sidebar', new Sidebar(this, settings));
            }
        });
    };

    $.fn.sidebar.Constructor = Sidebar;

}(jQuery));
