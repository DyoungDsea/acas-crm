@extends('layouts.guest')

@section('title', 'Reset password')
@section('panel-tagline', "One more step and you're back in.")

@section('content')
<p class="auth-eyebrow">Password reset</p>
<h1 class="auth-title">Set a new password</h1>
<p class="auth-subtitle">Choose something you haven't used on this account before.</p>

<form method="POST" action="{{ route('password.store') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', request('email')) }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">New password</label>
        <div class="input-group input-group-lg">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror" required>
            <button class="btn btn-outline-secondary auth-toggle-password" type="button"
                    data-target="#password" aria-label="Show password">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirm new password</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control form-control-lg" required>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 auth-submit">
        Reset password <i class="bi bi-check2 ms-1"></i>
    </button>
</form>
@endsection
