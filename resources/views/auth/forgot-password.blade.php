@extends('layouts.guest')

@section('title', 'Forgot password')
@section('panel-tagline', "We'll get you back in — safely.")

@section('content')
<p class="auth-eyebrow">Password reset</p>
<h1 class="auth-title">Forgot your password?</h1>
<p class="auth-subtitle">Enter your email and we'll send a reset link, if your account is eligible.</p>

<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 auth-submit">
        Send reset link <i class="bi bi-send ms-1"></i>
    </button>
</form>

<a href="{{ route('login') }}" class="auth-back-link mt-4 d-inline-flex align-items-center">
    <i class="bi bi-arrow-left me-1"></i> Back to sign in
</a>
@endsection
