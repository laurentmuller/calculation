/**! compression tag for ftp-deployment */

/**
 * Plugin to handle a sidebar.
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

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, Sidebar.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            this.$element.off('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
            this.$element.off('click', '.nav-link-toggle', this.toggleClickProxy);
            this.$showSidebarButton.off('click', this.showSidebarProxy);
            this.$hideSidebarButton.off('click', this.hideSidebarProxy);
            $(window).off('resize', this.resizeProxy);
            this.$element.removeData(Sidebar.NAME);
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
            const options = this.options;
            this.$navbarHorizontal = $(options.horizontalNavbarSelector);
            this.$showSidebarButton = $(options.showSidebarSelector);
            this.$hideSidebarButton = $(options.hideSidebarSelector);
            this.$pageContent = $(options.pageContentSelector);

            // update collapse menus
            this._updateSiblingMenus();

            // update toggle buttons
            this._updateToggleButtons();

            // initialize the timeout
            this._initTimeout();

            // highlight url
            this._highlightPath();

            // save state
            /** @type {Array<string, boolean>} */
            this.oldState = this._getState();

            // create proxies
            this.toggleCollapseProxy = () => this._updateToggleButtons();
            this.toggleClickProxy = (e) => this._toggleClick(e);
            this.showSidebarProxy = (e) => this._showSidebar(e);
            this.hideSidebarProxy = (e) => this._hideSidebar(e);
            this.resizeProxy = (e) => this._resize(e);

            // bind events
            this.$element.on('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
            this.$element.on('click', '.nav-link-toggle', this.toggleClickProxy);
            this.$showSidebarButton.on('click', this.showSidebarProxy);
            this.$hideSidebarButton.on('click', this.hideSidebarProxy);
            $(window).on('resize', this.resizeProxy);

            // hide sidebar if too small
            $(window).trigger('resize');
        }

        /**
         * Initialize the timeout to display/hide sidebar automatically.
         * @private
         */
        _initTimeout() {
            // timeout?
            const that = this;
            const timeout = that.options.timeout;
            if (timeout <= 0) {
                return;
            }

            // show button
            const showRemoveTimer = () => that.$showSidebarButton.removeTimer();
            that.$showSidebarButton.on('mouseenter', function (e) {
                if (that._isSideBarHidden()) {
                    that.$showSidebarButton.createTimer(function () {
                        showRemoveTimer();
                        if (that._isSideBarHidden()) {
                            that._showSidebar(e);
                        }
                    }, timeout);
                }
            }).on('mouseleave', showRemoveTimer);

            // hide button
            const hideRemoveTimer = () => that.$hideSidebarButton.removeTimer();
            that.$hideSidebarButton.on('mouseenter', function (e) {
                if (!that._isSideBarHidden()) {
                    that.$hideSidebarButton.createTimer(function () {
                        hideRemoveTimer();
                        if (!that._isSideBarHidden()) {
                            that._hideSidebar(e);
                        }
                    }, timeout);
                }
            }).on('mouseleave', hideRemoveTimer);
        }

        /**
         * Returns a value indicating if the client width is smaller than the minimum width option.
         *
         * @return {boolean}
         * @private
         */
        _isClientTooSmall() {
            return document.documentElement.clientWidth < this.options.minWidth;
        }

        /**
         * Handle the window resize event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _resize(e) {
            if (this._isClientTooSmall()) {
                this._hideSidebar(e);
            }
        }

        /**
         * Hide the sidebar if visible.
         *
         * @param {Event} e - the event.
         * @private
         */
        _hideSidebar(e) {
            if (!this._isSideBarHidden()) {
                this.$element.removeTimer();
                this._toggleSidebar(e);
            }
        }

        /**
         * Show the sidebar if hidden.
         *
         * @param {Event} e - the event.
         * @private
         */
        _showSidebar(e) {
            if (this._isSideBarHidden()) {
                this.$element.removeTimer();
                this._toggleSidebar(e);
            }
        }

        /**
         * Toggle the sidebar.
         *
         * @param {Event} [e] - the event.
         * @private
         */
        _toggleSidebar(e) {
            if (e) {
                e.preventDefault();
            }
            $.hideDropDownMenus();
            const duration = this.options.duration;
            this.$element.add(this.$pageContent).toggleClass('sidebar-hide');
            if (this._isSideBarHidden()) {
                this.$navbarHorizontal.show(duration);
            } else {
                this.$navbarHorizontal.hide(); // duration
            }
            this._saveState();
            this.$element.trigger('toggle.' + Sidebar.NAME);
        }

        /**
         * Handle the toggle button click events.
         *
         * @param {Event} [e] - the event.
         * @private
         */
        _toggleClick(e) {
            const that = this;
            const $this = $(e.currentTarget);
            $this.createTimer(function () {
                $this.removeTimer();
                that._saveState();
            }, 750);
        }

        /**
         * Update toggle buttons.
         * @private
         */
        _updateToggleButtons() {
            const options = this.options;
            this.$element.find('div.collapse').each(function (index, element) {
                const $element = $(element);
                const show = $element.hasClass('show');
                const title = show ? options.hideMenu : options.showMenu;
                const $button = $element.prev('.nav-link-toggle');
                $button.toggleClass('active', show).attr('title', title);
            });
        }

        /**
         * Update siblings menus.
         * @private
         */
        _updateSiblingMenus() {
            if (!this.options.collapseSiblingMenus) {
                return;
            }
            const that = this;
            const rootId = that._ensureId(this.$element);
            this.$element.find('div.collapse').each(function (index, element) {
                const $element = $(element);
                const count = $element.parents('div.collapse').length;
                if (count === 0) {
                    $element.attr('data-bs-parent', rootId);
                } else {
                    $element.parents('div.collapse:first').each(function (index, element) {
                        const id = that._ensureId($(element));
                        $element.attr('data-bs-parent', id);
                    });
                }
            });
        }

        /**
         * Ensure that the given element has a unique identifier.
         *
         * @param {jQuery} $element te element to validate.
         * @return {string} the unique identifier.
         * @private
         */
        _ensureId($element) {
            const id = $element.attr('id');
            if (id) {
                return `#${id}`;
            }
            const randomClass = Math.floor(Math.random() * Date.now()).toString(16);
            $element.addClass(randomClass);
            return `.${randomClass}`;
        }

        /**
         * Add active class to selected url (if any).
         * @private
         */
        _highlightPath() {
            const pathname = this.options.pathname || '';
            const params = (new URL(document.location)).searchParams;
            const search = params.get(pathname) || window.location.pathname;
            let paths = search.split('/');
            while (paths.length > 1) {
                const path = paths.join('/');
                const $element = $(`.nav-item a[href="${path}"]`);
                if ($element.length) {
                    $element.addClass('active');
                    break;
                }
                paths.pop();
            }
        }

        /**
         * Returns if the sidebar is hidden.
         *
         * @return {boolean} true if hidden; false if visible.
         * @private
         */
        _isSideBarHidden() {
            return this.$element.hasClass('sidebar-hide');
        }

        /**
         * Gets the navigation state.
         * @return {Array.<string, boolean>}
         * @private
         */
        _getState() {
            const menus = {};
            this.$element.find('div.collapse[id^="MenuSidebar"]').each(function (index, element) {
                const $element = $(element);
                menus[$element.attr('id')] = $element.hasClass('show');
            });
            menus.SidebarHide = this._isSideBarHidden();
            return menus;
        }

        /**
         * Save the navigation state.
         * @private
         */
        _saveState() {
            if (!this.options.url) {
                return;
            }
            const oldState = this.oldState;
            const newState = this._getState();
            if (oldState && JSON.stringify(oldState) === JSON.stringify(newState)) {
                return;
            }
            this.oldState = newState;
            $.ajaxSetup({global: false});
            $.post(this.options.url, newState)
                .always(() => $.ajaxSetup({global: true}));
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    Sidebar.DEFAULTS = {
        // url to save menu states
        url: null,
        // show sidebar button selector
        showSidebarSelector: '.show-sidebar',
        // hide sidebar button selector
        hideSidebarSelector: '.hide-sidebar',
        // horizontal navigation bar selector
        horizontalNavbarSelector: '.navbar-horizontal',
        // page content selector
        pageContentSelector: '.page-content',
        // the timeout to display/hide sidebar automatically (0 = disabled)
        timeout: 1500,
        // the duration to show / hide menus
        duration: 350,
        // the minimum width to hide sidebar
        minWidth: 1200,
        // texts
        showSidebar: 'Show sidebar',
        hideSidebar: 'Hide sidebar',
        showMenu: 'Expand menu',
        hideMenu: 'Collapse menu',
        // the path name to search in query parameters to highlight URL
        pathname: null,
        // collapse siblings menus
        collapseSiblingMenus: true
    };

    /**
     * The plugin name.
     */
    Sidebar.NAME = 'bs.sidebar';

    // -----------------------------
    // sidebar plugin definition
    // -----------------------------
    const oldSidebar = $.fn.sidebar;
    $.fn.sidebar = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(Sidebar.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(Sidebar.NAME, new Sidebar(this, settings));
            }
        });
    };
    $.fn.sidebar.Constructor = Sidebar;

    // ------------------------------------
    // sidebar no conflict
    // ------------------------------------
    $.fn.sidebar.noConflict = function () {
        $.fn.sidebar = oldSidebar;
        return this;
    };

}(jQuery));
