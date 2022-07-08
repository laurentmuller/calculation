/**! compression tag for ftp-deployment */

/**
 * Gets the navigation state.
 */
function getNavigationState() {
    'use strict';
    const menus = {
        'menu_active': $('.navbar-vertical').hasClass('active')
    };

    let visible;
    let wasVisible = false;
    $('.navbar-vertical .nav-item.nav-item-dropdown[id]').each(function () {
        const $this = $(this);
        visible = $this.find('.navbar-nav:first').is(':visible');
        // check that only one menu is visible
        if (wasVisible && visible) {
            visible = false;
        } else if (!wasVisible && visible) {
            wasVisible = true;
        }
        menus[$(this).attr('id')] = visible;
    });
    return menus;
}

/**
 * Save the navigation state.
 */
function saveNavigationState() {
    'use strict';
    const url = $('.navbar-vertical').data('url');
    if (url) {
        $.ajaxSetup({global: false});
        $.post(url, getNavigationState()).always(function () {
            $.ajaxSetup({global: true});
        });
    }
}

/**
 * Collapse all menus
 */
function collapseAllMenus() {
    'use strict';
    const $toggle = $('.navbar-vertical .nav-item-dropdown .nav-link-toggle.nav-link-toggle-show');
    if ($toggle.length) {
        const title = $('.navbar-vertical').data('show');
        $toggle.removeClass('nav-link-toggle-show').attr('aria-expanded', 'false')
            .attr('title', title);
        $('.navbar-vertical .nav-item-dropdown .navbar-nav:visible').hide(350);
    }
}

/**
 * Toggle the sidebar.
 *
 * @param {Event} e - the event.
 */
function toggleSidebar(e) {
    'use strict';
    e.stopPropagation();
    const $selector = $('.navbar-vertical, .page-content');
    $selector.toggleClass('active');
    const active = $selector.hasClass('active');
    const $navigation = $('.navbar-horizontal');
    // $navigation.toggleClass('pl-0', !active);
    $('.dropdown-menu.show, .dropdown.show').removeClass('show');
    const $toggle = $('.navbar-horizontal .nav-item-horizontal,.navbar-horizontal .navbar-brand');
    if (active) {
        $toggle.show(350);
    } else {
        $toggle.hide(350);
    }
    const title = active ? $navigation.data('show') : $navigation.data('hide');
    $('.sidebar-toggle').attr('title', title);

    // save
    saveNavigationState();
}

/**
 * Toggle a menu visibility. If expanded, all other menus are collapsed.
 *
 * @param {Event} e - the event.
 */
function toggleMenu(e) {
    'use strict';
    e.stopPropagation();
    const $link = $(e.currentTarget);
    const $parent = $link.parents('.nav-item-dropdown');
    const $menu = $parent.find('.navbar-nav');
    const visible = $menu.is(':visible');
    if (!visible) {
        collapseAllMenus();
    }
    $link.toggleClass('nav-link-toggle-show');
    $menu.toggle(350, function () {
        const $navbar = $('.navbar-vertical');
        const title = visible ? $navbar.data('show') : $navbar.data('hide');
        $link.attr('aria-expanded', String(visible)).attr('title', title);
        saveNavigationState();
    });
}

/**
 * Ready function.
 */
(function ($) {
    'use strict';
    $('.sidebar-toggle').on('click', function (e) {
        toggleSidebar(e);
    });
    $('.navbar-vertical .nav-link-toggle').on('click', function (e) {
        toggleMenu(e);
    });
}(jQuery));
