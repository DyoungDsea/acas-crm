
$(function () {

    const $shell = $('.app-shell');
    const $sidebar = $('.sidebar');
    const $backdrop = $('.sidebar-backdrop');
    const $toggle = $('.app-header__toggle');

    /*
    |--------------------------------------------------------------------------
    | Sidebar Toggle
    |--------------------------------------------------------------------------
    */
    $toggle.on('click', function (e) {
        e.preventDefault();

        /*
         * Desktop:
         * Collapse/expand the sidebar.
         */
        if (window.innerWidth >= 992) {
            $shell.toggleClass('sidebar-collapsed');

            // Remember user's desktop preference
            localStorage.setItem(
                'acas-sidebar-collapsed',
                $shell.hasClass('sidebar-collapsed') ? '1' : '0'
            );

            return;
        }

        /*
         * Mobile:
         * Open/close the sidebar.
         */
        $sidebar.toggleClass('show');
        $backdrop.toggleClass('show');
        $('body').toggleClass('sidebar-open', $sidebar.hasClass('show'));
    });


    /*
    |--------------------------------------------------------------------------
    | Close mobile sidebar when backdrop is clicked
    |--------------------------------------------------------------------------
    */
    $backdrop.on('click', function () {
        closeMobileSidebar();
    });


    /*
    |--------------------------------------------------------------------------
    | Close mobile sidebar when a normal navigation link is clicked
    |--------------------------------------------------------------------------
    */
    $sidebar.on('click', '.nm-link', function () {

        if (window.innerWidth < 992) {

            /*
             * Don't close the sidebar when clicking a parent
             * that is opening/closing a Bootstrap submenu.
             */
            const hasSubmenu = $(this).attr('data-bs-toggle') === 'collapse';

            if (!hasSubmenu) {
                closeMobileSidebar();
            }
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Restore desktop sidebar state
    |--------------------------------------------------------------------------
    */
    if (window.innerWidth >= 992) {

        if (localStorage.getItem('acas-sidebar-collapsed') === '1') {
            $shell.addClass('sidebar-collapsed');
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Handle browser resize
    |--------------------------------------------------------------------------
    */
    $(window).on('resize', function () {

        if (window.innerWidth >= 992) {

            /*
             * Remove mobile state when moving back to desktop.
             */
            $sidebar.removeClass('show');
            $backdrop.removeClass('show');
            $('body').removeClass('sidebar-open');

            /*
             * Restore saved desktop state.
             */
            if (localStorage.getItem('acas-sidebar-collapsed') === '1') {
                $shell.addClass('sidebar-collapsed');
            }

        } else {

            /*
             * Desktop collapsed class should not affect mobile.
             */
            $shell.removeClass('sidebar-collapsed');
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */
    function closeMobileSidebar() {
        $sidebar.removeClass('show');
        $backdrop.removeClass('show');
        $('body').removeClass('sidebar-open');
    }

});
