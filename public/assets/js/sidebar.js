/**
 * Sidebar shell behaviour: desktop collapse, mobile off-canvas drawer,
 * collapsed-mode flyout submenus, and collapsed-mode tooltips.
 *
 * Pairs with shell.css — every class toggled here (.collapsed,
 * .mobile-open, .flyout-portal, .flyout-open, #sidebarTooltip.show) is
 * defined there. Bootstrap's own collapse plugin (bootstrap.bundle.min.js,
 * loaded before this file) still drives the inline accordion behaviour
 * for submenus when the sidebar is expanded — this file only adds what
 * Bootstrap doesn't: the responsive drawer, the collapsed flyouts, and
 * the collapsed tooltips.
 */
(function ($) {
    'use strict';

    var MOBILE_BREAKPOINT = 991.98;
    var FLYOUT_CLOSE_DELAY = 150; // grace period so the cursor can travel
                                   // from the trigger link to the detached
                                   // flyout menu without it closing first

    var $sidebar  = $('#appSidebar');
    var $toggle   = $('#sidebarToggle');
    var $backdrop = $('#sidebarBackdrop');
    var $tooltip  = $('#sidebarTooltip');
    var $body     = $('body');

    var STORAGE_KEY = 'acas.sidebar.collapsed';

    function isMobile() {
        return window.matchMedia('(max-width: ' + MOBILE_BREAKPOINT + 'px)').matches;
    }

    /* -----------------------------------------------------------------
     * Desktop: collapsed / expanded, persisted across page loads (this
     * is a classic multi-page Blade app, not an SPA, so the state has
     * to survive a full reload).
     * ------------------------------------------------------------- */
    function applyStoredCollapsedState() {
        if (isMobile()) return;
        var collapsed = localStorage.getItem(STORAGE_KEY) === '1';
        $sidebar.toggleClass('collapsed', collapsed);
        $toggle.attr('aria-expanded', String(!collapsed));
    }

    function toggleDesktopSidebar() {
        var collapsed = $sidebar.toggleClass('collapsed').hasClass('collapsed');
        localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        $toggle.attr('aria-expanded', String(!collapsed));
        closeAnyOpenFlyout(); // collapsing/expanding invalidates any open flyout's position
        $tooltip.removeClass('show');
    }

    /* -----------------------------------------------------------------
     * Mobile: off-canvas drawer + backdrop
     * ------------------------------------------------------------- */
    function openMobileSidebar() {
        $sidebar.addClass('mobile-open');
        $backdrop.addClass('show');
        $body.css('overflow', 'hidden');
        $toggle.attr('aria-expanded', 'true');
    }

    function closeMobileSidebar() {
        $sidebar.removeClass('mobile-open');
        $backdrop.removeClass('show');
        $body.css('overflow', '');
        $toggle.attr('aria-expanded', 'false');
    }

    function toggleMobileSidebar() {
        if ($sidebar.hasClass('mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    $toggle.on('click', function () {
        if (isMobile()) {
            toggleMobileSidebar();
        } else {
            toggleDesktopSidebar();
        }
    });

    $backdrop.on('click', closeMobileSidebar);

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $sidebar.hasClass('mobile-open')) {
            closeMobileSidebar();
        }
    });

    // Tapping an actual destination link (not a submenu toggle) closes
    // the mobile drawer so the user lands on the page instead of
    // staring at an open menu.
    $sidebar.on('click', '.nm-link:not([data-bs-toggle="collapse"])', function () {
        if (isMobile()) {
            closeMobileSidebar();
        }
    });

    $(window).on('resize', function () {
        if (isMobile()) {
            $sidebar.removeClass('collapsed');
            closeAnyOpenFlyout();
            $tooltip.removeClass('show');
        } else {
            closeMobileSidebar();
            applyStoredCollapsedState();
        }
    });

    /* -----------------------------------------------------------------
     * Collapsed-sidebar flyouts.
     *
     * When the sidebar is collapsed on desktop there's no room to
     * expand a submenu inline, so hovering a top-level item with
     * children detaches its .nm-children <ul> and re-parents it to
     * <body> as a fixed-position flyout (see the comment above
     * .nm-children.flyout-portal in shell.css for why it has to be
     * portalled rather than just absolutely positioned in place).
     * ------------------------------------------------------------- */
    var openFlyout = null; // { $ul, $li, $placeholder }
    var closeTimer = null;

    function clearCloseTimer() {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
    }

    function closeAnyOpenFlyout() {
        clearCloseTimer();
        if (!openFlyout) return;

        openFlyout.$ul.removeClass('flyout-portal').removeAttr('style');
        openFlyout.$ul.find('.flyout-open').removeClass('flyout-open');
        openFlyout.$placeholder.replaceWith(openFlyout.$ul);
        openFlyout = null;
    }

    function openFlyoutFor($li) {
        if (openFlyout && openFlyout.$li.is($li)) {
            clearCloseTimer();
            return;
        }
        closeAnyOpenFlyout();

        var $link = $li.children('.nm-link');
        var $ul = $li.children('.nm-children');
        if (!$link.length || !$ul.length) return;

        // Leave a marker so the <ul> can go back exactly where it came
        // from when the flyout closes.
        var $placeholder = $('<span class="d-none" aria-hidden="true"></span>');
        $ul.before($placeholder);

        var rect = $link[0].getBoundingClientRect();
        $ul.addClass('flyout-portal').css({
            top: rect.top + 'px',
            left: rect.right + 'px'
        });
        $body.append($ul);

        openFlyout = { $ul: $ul, $li: $li, $placeholder: $placeholder };
    }

    // Top-level items only — nested levels use .flyout-open (below);
    // they don't get re-portalled themselves, they just get shown via CSS.
    $sidebar.on('mouseenter', '.nm[data-level="1"] > li.has-children', function () {
        if (isMobile() || !$sidebar.hasClass('collapsed')) return;
        clearCloseTimer();
        openFlyoutFor($(this));
    });

    $sidebar.on('mouseleave', '.nm[data-level="1"] > li.has-children', function () {
        if (isMobile() || !$sidebar.hasClass('collapsed')) return;
        clearCloseTimer();
        closeTimer = setTimeout(closeAnyOpenFlyout, FLYOUT_CLOSE_DELAY);
    });

    // Keep it open while the cursor is over the detached flyout itself
    // (it's no longer inside the hovered <li> in the DOM once portalled).
    $(document).on('mouseenter', '.nm-children.flyout-portal', function () {
        clearCloseTimer();
    });
    $(document).on('mouseleave', '.nm-children.flyout-portal', function () {
        closeTimer = setTimeout(closeAnyOpenFlyout, FLYOUT_CLOSE_DELAY);
    });

    // Nested (second-level+) submenus inside an open flyout just toggle
    // a class — shell.css positions them with left:100% off the
    // already-portalled fixed ancestor, no further re-parenting needed.
    $(document).on('mouseenter', '.nm-children.flyout-portal li.has-children', function (e) {
        e.stopPropagation();
        $(this).addClass('flyout-open').siblings().removeClass('flyout-open');
    });
    $(document).on('mouseleave', '.nm-children.flyout-portal li.has-children', function (e) {
        e.stopPropagation();
        $(this).removeClass('flyout-open');
    });

    /* -----------------------------------------------------------------
     * Collapsed-sidebar tooltips for top-level leaf links (items with
     * no children have no flyout to label them, so show their title in
     * the shared #sidebarTooltip on hover instead).
     * ------------------------------------------------------------- */
    $sidebar.on('mouseenter', '.nm[data-level="1"] > li:not(.has-children) > .nm-link', function () {
        if (isMobile() || !$sidebar.hasClass('collapsed')) return;

        var rect = this.getBoundingClientRect();
        var label = $(this).find('.nm-title').text().trim();
        if (!label) return;

        $tooltip
            .text(label)
            .css({
                top: (rect.top + rect.height / 2) + 'px',
                left: (rect.right + 10) + 'px'
            })
            .addClass('show');
    });

    $sidebar.on('mouseleave', '.nm[data-level="1"] > li:not(.has-children) > .nm-link', function () {
        $tooltip.removeClass('show');
    });

    /* -----------------------------------------------------------------
     * Init
     * ------------------------------------------------------------- */
    applyStoredCollapsedState();

})(jQuery);
