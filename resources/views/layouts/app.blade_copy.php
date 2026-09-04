<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $contextLabel ?? config('app.name'))</title>

    <link rel="shortcut icon" href="{{ asset('assets/images/acas-logo.png') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="{{ asset('assets') }}/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/icons/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Alertify -->
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">

    <!-- alertify Css -->
    <link href="{{asset('assets')}}/alertify/css/alertify.min.css" rel="stylesheet" type="text/css">
    <link href="{{asset('assets')}}/alertify/css/themes/default.min.css" rel="stylesheet" type="text/css">

    <link href="{{ asset('assets/css/datatables.min.css') }}" rel="stylesheet">
    <!-- <link href="https://cdn.datatables.net/v/dt/dt-2.3.2/datatables.min.css" rel="stylesheet"> -->

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/shell.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/themes.css') }}" rel="stylesheet">
    <style>
        .company-card {
            transition: all 0.2s ease;
        }

        .company-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08) !important;
        }
    </style>
    {{-- Page-specific assets (e.g. DataTables on an index page) hook
         in here instead of loading globally on every page. --}}
    @stack('styles')
</head>

<body class="app-body @isset($company) theme-{{ $company->themeKey() }} @endisset">

    <div class="app-shell">

        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="logo-mark">
                    <img src="{{ asset('assets/images/acas-logo.png') }}" alt="{{ config('app.name', 'ACAS Group') }}">
                </div>
                <div class="brand-text">
                    <strong>ACAS GROUP</strong>
                    <span>Nigeria</span>
                </div>
            </div>

            <nav class="sidebar-nav-wrap">
                @once
                @php
                if (! function_exists('acasFilterMenuForCompany')) {
                /**
                * Removes any menu item tagged with a 'types' list
                * that doesn't include this company's type — e.g.
                * "Pharmacy stock" never appears for a farm. Recurses
                * into children, and drops a parent entirely if every
                * one of its children got filtered out and it isn't
                * itself a real page (Operations, for a company with
                * no matching vertical, e.g. Kezong Trading).
                *
                * This is a UX/relevance filter, NOT the security
                * boundary — CompanyScope and ResolveCompanyTenant
                * already block cross-company access at the query
                * and URL level regardless of what the sidebar shows.
                */
                function acasFilterMenuForCompany(array $items, ?string $companyType): array
                {
                $filtered = [];

                foreach ($items as $item) {
                if (! empty($item['types']) && ! in_array($companyType, $item['types'], true)) {
                continue;
                }

                if (! empty($item['children'])) {
                $item['children'] = acasFilterMenuForCompany($item['children'], $companyType);

                if (empty($item['children']) && empty($item['route'])) {
                continue;
                }
                }

                $filtered[] = $item;
                }

                return $filtered;
                }
                }
                @endphp
                @endonce
                @php
                $effectiveMenu = $menu ?? [];
                if (isset($company)) {
                $effectiveMenu = acasFilterMenuForCompany($effectiveMenu, $company->type);
                }
                if (isset($company) && auth()->user()->is_super_admin) {
                array_unshift($effectiveMenu, [
                'label' => 'Super Admin overview',
                'icon' => 'bi-arrow-left-circle',
                'route' => 'super-admin.dashboard',
                ]);
                }
                @endphp
                <x-sidebar-menu :items="$effectiveMenu" :default-params="isset($company) ? ['company' => $company->slug] : []" />
            </nav>
        </aside>

        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <div id="sidebarTooltip"></div>

        <div class="app-main">

            <header class="app-header">
                <div class="app-header__left">
                    <a href="{{ isset($company) ? route('company.dashboard', ['company' => $company->slug]) : route('super-admin.dashboard') }}" class="app-header__logo">
                        <img src="{{ asset('assets/images/acas-logo.png') }}" alt="{{ config('app.name', 'ACAS Group') }}">
                    </a>
                    <button type="button" class="app-header__toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-controls="appSidebar" aria-expanded="true">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="app-header__context">{{ $contextLabel ?? '' }}</span>
                </div>

                <div class="app-header__right">
                    {{-- Notifications — UI stub, ready for real data.
                         Empty state shown until a notifications system
                         exists. --}}
                    <div class="dropdown">
                        <button type="button" class="app-header__icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end app-notification-panel">
                            <div class="app-notification-panel__header">Notifications</div>
                            <div class="app-notification-panel__empty">
                                <i class="bi bi-bell-slash"></i>
                                <p>You're all caught up.</p>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button type="button" class="app-user-menu" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="app-user-menu__avatar">{{ auth()->user()->initials() }}</span>
                            <span class="app-user-menu__name">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down app-user-menu__caret"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end app-user-menu__panel">
                            <li class="app-user-menu__header">
                                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                <div class="text-muted small">{{ auth()->user()->email }}</div>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key me-2"></i> Change password
                                </button>
                            </li>
                            @if (auth()->user()->is_super_admin || auth()->user()->hasRole('company_admin'))
                            <li>
                                <a class="dropdown-item" href="#" data-coming-soon="1">
                                    <i class="bi bi-gear me-2"></i> Settings
                                </a>
                            </li>
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item app-user-menu__logout" href="#" id="logoutTrigger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                        <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </header>
            {{-- Change password — available from every page via the
                 header dropdown above. Real form submission; on
                 failure Laravel redirects back to whichever page the
                 user was actually on when they submitted, and the
                 script block near the bottom of this layout reopens
                 this exact modal there (see the
                 session()->hasOldInput('current_password') check —
                 that field name is unique to this form, which is how
                 it's told apart from any other modal's errors on the
                 same page). --}}
            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Change password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current password</label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                        id="currentPassword" name="current_password" required autocomplete="current-password">
                                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New password</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="newPassword" name="password" required autocomplete="new-password">
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-1">
                                    <label for="newPasswordConfirm" class="form-label">Confirm new password</label>
                                    <input type="password" class="form-control"
                                        id="newPasswordConfirm" name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <main class="app-content">
                @yield('content')
            </main>

            <footer class="app-footer text-centerb">
                <span>&copy; {{ date('Y') }} ACAS Group. All rights reserved.</span>
            </footer>

        </div>
    </div>

    <!-- <script src="{{ asset('assets/js/jquery.js') }}"></script> -->
    <script src="{{ asset('assets/js/datatables.min.js') }}"></script>

    <script src="{{ asset('assets') }}/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
    <script src="{{asset('assets')}}/alertify/alertify.min.js"></script>
    <script src="{{ asset('assets/js/sidebar.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <!-- <script src="https://cdn.datatables.net/v/dt/dt-2.3.2/datatables.min.js"></script> -->

    <script>
        acasFlashToast(@json(session('status')), @json($errors -> any() ? $errors -> first() : null));
        window.API_ROOT = "{{ url('/api') }}";
        @isset($company)
        window.API_BASE = "{{ url('/api/companies/'.$company->slug) }}";
        @endisset

        @if(session() -> hasOldInput('current_password'))
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
        });
        @endif
    </script>

    @stack('scripts')
</body>

</html>
