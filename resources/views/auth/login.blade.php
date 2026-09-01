@extends('layouts.guest')

@section('title', 'Sign in')
@section('panel-tagline', 'Rice to renewables — every company, one login.')

@section('content')
<p class="auth-eyebrow">Welcome back</p>
<h1 class="auth-title">Sign in</h1>
<p class="auth-subtitle">Use the email your company admin set up for you.</p>

<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               required autofocus autocomplete="username">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-2">
        <label for="password" class="form-label">Password</label>
        <div class="input-group input-group-lg">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            <button class="btn btn-outline-secondary auth-toggle-password" type="button"
                    data-target="#password" aria-label="Show password">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="remember">
            <label class="form-check-label small" for="remember">Remember me</label>
        </div>
        <a href="{{ route('password.request') }}" class="small auth-link">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 auth-submit">
        Sign in <i class="bi bi-arrow-right ms-1"></i>
    </button>
</form>

<button type="button" class="btn btn-link auth-help-trigger mt-4"
        data-bs-toggle="modal" data-bs-target="#troubleSigningInModal">
    <i class="bi bi-question-circle me-1"></i> Trouble signing in?
</button>

{{-- Modal used here on purpose: "who do I contact" needs a layer
     above the form, not a full page navigation away from it. --}}
<div class="modal fade" id="troubleSigningInModal" tabindex="-1" aria-labelledby="troubleSigningInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="troubleSigningInModalLabel">Trouble signing in?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="auth-help-list">
                    <li><strong>Forgot your password?</strong> Use the link on the sign-in form to reset it by email.</li>
                    <li><strong>Account not working?</strong> Ask your company admin to check it's active.</li>
                    <li><strong>Super admin account?</strong> Email password resets are turned off for this role — contact whoever manages the server.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
