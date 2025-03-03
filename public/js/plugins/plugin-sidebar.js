/**
 * ------------------------------------
 * Plugin to handle a sidebar.
 * ------------------------------------
 */
$(function () {
    'use strict';

    // ------------------------------------
    // Public class definition
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
            if (this.$verticalNavigation) {
                this.$verticalNavigation.off('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
            }
            if (this.$showButton) {
                this.$showButton.off('click', this.showSidebarProxy);
            }
            if (this.$hideButton) {
                this.$hideButton.off('click', this.hideSidebarProxy);
            }
            this.$element.removeData(Sidebar.NAME);
            $(window).off('resize', this.resizeProxy);
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
            this.$verticalNavigation = this._ensureInstance(null, options.verticalSelector);
            this.$horizontalNavigation = this._ensureInstance(null, options.horizontalSelector);

            this.$verticalTarget = this._ensureInstance(null, options.verticalTarget);
            this.$horizontalTarget = this._ensureInstance(null, options.horizontalTarget);

            // create proxies
            this.toggleCollapseProxy = () => this._updateToggleButtons();
            this.showSidebarProxy = (e) => this._showVerticalNavigation(e);
            this.hideSidebarProxy = (e) => this._showHorizontalNavigation(e);
            this.resizeProxy = (e) => this._resize(e);

            if (this.$verticalNavigation) {
                this.$verticalNavigation.on('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
                this._updateToggleButtons();
                this._updateSiblingMenus();
                this._highlightPath();
            }

            this._initShowButton();
            this._initHideButton();

            $(window).on('resize', this.resizeProxy)
                .trigger('resize');
        }

        _initShowButton() {
            const options = this.options;
            this.$showButton = this._ensureInstance(null, options.showButtonSelector);
            if (this.$showButton) {
                this.$showButton.on('click', this.showSidebarProxy);
                this._initShowButtonTimeout(options.timeout);
            }
        }

        _initHideButton() {
            const options = this.options;
            this.$hideButton = this._ensureInstance(null, options.hideButtonSelector);
            if (this.$hideButton) {
                this.$hideButton.on('click', this.hideSidebarProxy);
                this._initHideButtonTimeout(options.timeout);
            }
        }

        /**
         * Initialize the timeout to show vertical navigation automatically.
         * @param {number} timeout
         * @private
         */
        _initShowButtonTimeout(timeout) {
            const that = this;
            if (!that.$showButton) {
                return;
            }
            const removeTimer = () => that.$showButton.removeTimer();
            that.$showButton.on('mouseenter', function (e) {
                if (!that._isVerticalNavigationVisible()) {
                    that.$showButton.createTimer(function () {
                        removeTimer();
                        that._showVerticalNavigation(e);
                    }, timeout);
                }
            }).on('mouseleave', removeTimer);
        }

        /**
         * Initialize the timeout to hide vertical navigation automatically.
         * @param {number} timeout
         * @private
         */
        _initHideButtonTimeout(timeout) {
            const that = this;
            if (!that.$hideButton) {
                return;
            }
            const removeTimer = () => that.$hideButton.removeTimer();
            that.$hideButton.on('mouseenter', function (e) {
                if (that._isVerticalNavigationVisible()) {
                    that.$hideButton.createTimer(function () {
                        removeTimer();
                        that._showHorizontalNavigation(e);
                    }, timeout);
                }
            }).on('mouseleave', removeTimer);
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
                this._showHorizontalNavigation(e);
            }
        }

        /**
         * Show the horizontal navigation.
         *
         * @param {Event} e - the event.
         * @private
         */
        _showHorizontalNavigation(e) {
            if (!this._isVerticalNavigationVisible()) {
                return;
            }
            if (!this.$horizontalNavigation) {
                this._loadHorizontalNavigation();
            } else {
                this._toggleNavigation(e);
            }
        }

        /**
         * Show the vertical navigation.
         *
         * @param {Event} e - the event.
         * @private
         */
        _showVerticalNavigation(e) {
            if (this._isVerticalNavigationVisible()) {
                return;
            }
            if (!this.$verticalNavigation) {
                this._loadVerticalNavigation();
            } else {
                this._toggleNavigation(e);
            }
        }

        _loadVerticalNavigation() {
            const that = this;
            const options = this.options;
            if (!options.verticalUrl || !options.verticalTarget || !that.$verticalTarget) {
                return;
            }

            $.getJSON(options.verticalUrl, function (response) {
                if (!response.result || !response.view || !that.$verticalTarget) {
                    return;
                }
                that.$verticalNavigation = $(response.view);
                that._updateSiblingMenus();
                that._updateToggleButtons();
                that._highlightPath();
                that.$verticalTarget.prepend(that.$verticalNavigation);
                if (!that.$hideButton) {
                    that._initHideButton();
                }
                that._toggleNavigation();
            });
        }

        _loadHorizontalNavigation() {
            const that = this;
            const options = this.options;
            if (!options.horizontalUrl || !options.horizontalTarget || !that.$horizontalTarget) {
                return;
            }

            $.getJSON(options.horizontalUrl, function (response) {
                if (!response.result || !response.view) {
                    return;
                }
                that.$horizontalNavigation = $(response.view);
                that.$horizontalTarget.prepend(that.$horizontalNavigation);
                if (!that.$showButton) {
                    that._initShowButton();
                }
                that._toggleNavigation();
            });
        }

        /**
         * Toggle the navigation visibility.
         *
         * @param {Event} [e] - the event.
         * @private
         */
        _toggleNavigation(e) {
            if (e) {
                e.preventDefault();
            }
            $.hideTooltips();
            $.hideDropDownMenus();
            const that = this;
            if (that.$verticalNavigation && that.$horizontalTarget) {
                that.$verticalNavigation.add(that.$horizontalTarget).toggleClass('sidebar-show').promise().done(() => {
                    that.$horizontalNavigation.toggle(that.options.duration, () => {
                        that._saveState();
                        that.$element.trigger('toggle-navigation');
                    });

                });
            }
        }

        /**
         * Update toggle buttons.
         * @private
         */
        _updateToggleButtons() {
            const options = this.options;
            this.$verticalNavigation.find('div.collapse').each(function (index, element) {
                const $element = $(element);
                const show = $element.hasClass('show');
                const title = show ? options.hideMenu : options.showMenu;
                $element.prev('.nav-link-toggle').toggleClass('active', show).attr('title', title);
            });
            this._saveState();
        }

        /**
         * Update siblings menus.
         * @private
         */
        _updateSiblingMenus() {
            if (!this.options.collapseSiblingMenus || !this.$verticalNavigation) {
                return;
            }
            const that = this;
            const rootId = that._ensureId(this.$verticalNavigation);
            this.$verticalNavigation.find('div.collapse').each(function (index, element) {
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
         * Add active class to the selected url (if any).
         * @private
         */
        _highlightPath() {
            if (!this.$verticalNavigation) {
                return;
            }
            const pathname = this.options.pathname || '';
            const params = (new URL(document.location)).searchParams;
            const search = params.get(pathname) || window.location.pathname;
            let paths = search.split('/');
            while (paths.length > 2) {
                const path = paths.join('/');
                const $element = $(`.nav-item a[href="${path}"]`);
                if ($element.length) {
                    $element.addClass('active');
                    break;
                }
                paths.pop();
            }

            // active target element
            this.$verticalNavigation.find(`a[href="${search}"]:not(.navbar-brand)`).addClass('active bg-body-secondary');
        }

        /**
         * Returns if the vertical navigation is visible.
         *
         * @return {boolean} true if visible; false if hidden.
         * @private
         */
        _isVerticalNavigationVisible() {
            return this.$verticalNavigation && this.$verticalNavigation.hasClass('sidebar-show');
        }

        /**
         * Gets the navigation state.
         * @return {Array.<string, boolean>}
         * @private
         */
        _getState() {
            const menus = {};
            const options = this.options;
            const selector = `div.collapse[id^='${options.menuPrefix}']`;
            this.$verticalNavigation.find(selector).each(function (index, element) {
                menus[element.id] = element.classList.contains('show');
            });
            menus[options.menuShow] = this._isVerticalNavigationVisible();
            return menus;
        }

        /**
         * Gets the cookie path.
         * @return {string}
         * @private
         */
        _getCookiePath() {
            return document.body.dataset.cookiePath || '/';
        }

        /**
         * Gets the cookie date.
         * @return {string}
         * @private
         */
        _getCookieDate() {
            const date = new Date();
            date.setFullYear(date.getFullYear() + 1);
            return date.toUTCString();
        }

        /**
         * Save the navigation state.
         * @private
         */
        _saveState() {
            const date = this._getCookieDate();
            const path = this._getCookiePath();
            const suffix = `expires=${date};path=${path};samesite=lax;secure`;
            const state = this._getState();
            for (const [key, value] of Object.entries(state)) {
                document.cookie = `${key}=${JSON.stringify(value)};${suffix}`;
            }
        }

        /**
         * Find a child element.
         *
         * @param {jQuery|null} $parent
         * @param {string} selector
         * @return {jQuery|null}
         * @private
         */
        _ensureInstance($parent, selector) {
            const $instance = $parent ? $parent.find(selector) : $(selector);
            if ($instance && $instance.length) {
                return $instance;
            }
            return null;
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    Sidebar.DEFAULTS = {
        // the sidebar key used to save state
        menuShow: 'MENU_SIDEBAR_SHOW',
        // the menu prefix used to save state
        menuPrefix: 'MENU_SIDEBAR_',
        // the timeout to display/hide sidebar automatically (0 = disabled)
        timeout: 1500,
        // the duration to show / hide menus
        duration: 350,
        // the minimum client width to hide sidebar
        minWidth: 1200,
        // texts
        showSidebar: 'Show sidebar',
        hideSidebar: 'Hide sidebar',
        showMenu: 'Expand menu',
        hideMenu: 'Collapse menu',
        // the path name to search in query parameters to highlight URL
        pathname: null,
        // collapse sibling's menus
        collapseSiblingMenus: true,

        // the URL to load the vertical navigation
        verticalUrl: null,
        // the selector where to prepend the loaded vertical navigation
        verticalTarget: null,
        // vertical navigation selector
        verticalSelector: '.navbar-vertical',
        // the URL to load the horizontal navigation
        horizontalUrl: null,
        // the selector where to prepend the loaded horizontal navigation
        horizontalTarget: null,
        // horizontal navigation selector
        horizontalSelector: '.navbar-horizontal',
        // show vertical navigation button selector
        showButtonSelector: '.show-sidebar',
        // hide vertical navigation button selector
        hideButtonSelector: '.hide-sidebar',
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

});
