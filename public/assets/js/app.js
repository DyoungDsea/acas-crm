/**
 * ACAS Group — shared front-end setup.
 * Loaded on every page. Configures Toastr (notifications) and
 * AlertifyJS (confirm dialogs / action prompts) once, so individual
 * pages just call toastr.success(...) or alertify.confirm(...)
 * without repeating setup.
 */
$(function () {
    if (window.toastr) {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
            extendedTimeOut: 1500,
            newestOnTop: true,
            preventDuplicates: true,
        };
    }

    if (window.alertify) {
        // AlertifyJS's real job starts on the data pages — confirming
        // destructive actions like voiding a receipt, deactivating a
        // user, or deleting a record. Configured here so it's ready;
        // the auth pages below don't have anything destructive enough
        // to need it.
        alertify.defaults.glossary.title = 'ACAS Group';
        alertify.defaults.theme.ok = 'btn btn-primary btn-sm';
        alertify.defaults.theme.cancel = 'btn btn-outline-secondary btn-sm';
    }

    // Password show/hide toggle — reused on any page with a password
    // field (login, reset-password, change-password).
    $(document).on('click', '.auth-toggle-password', function () {
        var target = $($(this).data('target'));
        var icon = $(this).find('i');
        var isPassword = target.attr('type') === 'password';
        target.attr('type', isPassword ? 'text' : 'password');
        icon.toggleClass('bi-eye bi-eye-slash');
    });
});

/**
 * Every POST/PUT/DELETE to /api/* goes through Laravel's CSRF check
 * since those routes run through Sanctum's "stateful" (session-based)
 * mode — see AUTH_FLOW.md. Setting this once here means individual
 * pages never have to remember to attach the token themselves.
 */
$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
});

/**
 * Fires a toastr notification for whatever Laravel flashed to the
 * session on this request. Called from layouts/guest.blade.php so
 * every auth page gets consistent toast behavior for free — a
 * controller only needs `->with('status', '...')` or a validation
 * error, nothing page-specific.
 */
function acasFlashToast(status, firstError) {
    $(function () {
        if (status && window.toastr) {
            toastr.success(status);
        }
        if (firstError && window.toastr) {
            toastr.error(firstError);
        }
    });
}

/**
 * App shell behavior — sidebar collapse, logout confirmation, and
 * placeholder links. Only runs where these elements exist, so it's
 * harmless to load on pages (like the auth screens) that don't have
 * a sidebar at all.
 */
$(function () {
    // Logout — confirm via AlertifyJS before actually submitting.
    // This is the kind of action-with-consequence AlertifyJS is for,
    // as opposed to Toastr's passive notifications.
    $('#logoutTrigger').on('click', function (e) {
        e.preventDefault();

        if (window.alertify) {
            alertify.confirm(
                'Log out',
                'Are you sure you want to log out?',
                function () { $('#logoutForm').trigger('submit'); },
                function () { }
            );
        } else {
            $('#logoutForm').trigger('submit');
        }
    });

    // Any link explicitly marked as not-yet-built (see config/menus.php
    // and the sidebar-menu component) gets a clear "not ready" notice
    // instead of silently doing nothing.
    $(document).on('click', '[data-coming-soon]', function (e) {
        e.preventDefault();
        if (window.alertify) {
            alertify.notify('This page is still being built.', 'message', 3);
        }
    });
});
