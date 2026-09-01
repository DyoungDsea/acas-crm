<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') — {{ config('app.name', 'ACAS Group') }}</title>

    <link rel="shortcut icon" href="{{ asset('assets/images/acas-logo.png') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('assets') }}/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">

    <!-- alertify Css -->
    <link href="{{asset('assets')}}/alertify/css/alertify.min.css" rel="stylesheet" type="text/css">
    <link href="{{asset('assets')}}/alertify/css/themes/default.min.css" rel="stylesheet" type="text/css">

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
</head>

<body class="auth-body">

    <div class="auth-shell">
        {{-- Desktop brand panel --}}
        <aside class="auth-panel d-none d-lg-flex">
            <div class="auth-panel__content">
                <div class="auth-mark">
                    <img src="{{ asset('assets/images/acas-logo.png') }}" alt="{{ config('app.name', 'ACAS Group') }}" class="auth-mark__logo img-thumbnail">
                    <div class="">
                        <span class="auth-mark__word">ACAS GROUP <br>Nigeria</span>
                    </div>
                </div>
                <p class="auth-panel__tagline">@yield('panel-tagline', 'Seven businesses. One record of everything.')</p>
            </div>
        </aside>

        {{-- Mobile: brand collapses to a compact top band --}}
        <div class="auth-panel-mobile d-lg-none">
            <img src="{{ asset('assets/images/acas-logo.png') }}" alt="{{ config('app.name', 'ACAS Group') }}" class="auth-mark__logo auth-mark__logo--sm">
            <span class="auth-mark__word auth-mark__word--sm">ACAS GROUP <br>Nigeria</span>
        </div>

        <main class="auth-form-area">
            <div class="auth-form-area__inner">
                @yield('content')
            </div>
        </main>
    </div>

    <script src="{{ asset('assets/js/jquery.js') }}"></script>
    <script src="{{ asset('assets') }}/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
    <script src="{{asset('assets')}}/alertify/alertify.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    {{-- Every flashed status/error becomes a toast automatically —
         a controller doesn't need to know toastr exists, it just
         flashes `status` or a validation error the normal Laravel way. --}}
    <script>
        acasFlashToast(@json(session('status')), @json($errors -> first() ? : null));
    </script>

    @yield('scripts')
</body>

</html>
