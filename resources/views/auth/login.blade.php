@extends('layouts.login_master')

@section('content')
<div class="sms-auth-page">
    <div class="sms-auth-card">
        <div class="text-center mb-4">
            <div class="sms-auth-icon" aria-hidden="true">
                <i class="icon-graduation2"></i>
            </div>
            <div class="text-uppercase font-weight-bold text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                {{ Qs::getSystemName() ?: 'SMS Academy' }}
            </div>
            <h1 class="h4 font-weight-bold text-dark mb-1">{{ __('Sign in to your account') }}</h1>
            <p class="text-muted mb-0" style="font-size: 0.84rem;">
                {{ __('Smart School Management. Simplified.') }}
            </p>
        </div>

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="icon-warning2 mr-2" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="sms-login-form" novalidate>
            @csrf

            <div class="form-group mb-3">
                <label for="username">
                    {{ __('Username or Email') }} <span class="sms-required-mark" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    class="form-control @error('identity') is-invalid @enderror"
                    id="username"
                    name="identity"
                    value="{{ old('identity') }}"
                    placeholder="{{ __('Enter your username or email') }}"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="form-group mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label for="password" class="mb-0">
                        {{ __('Password') }} <span class="sms-required-mark" aria-hidden="true">*</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size: 0.78rem; font-weight: 500;">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        placeholder="{{ __('Enter your password') }}"
                        required
                        autocomplete="current-password"
                    >
                    <div class="input-group-append">
                        <button
                            type="button"
                            class="btn btn-light border"
                            id="toggle-password-btn"
                            aria-label="{{ __('Show password') }}"
                            title="{{ __('Show password') }}"
                        >
                            <i class="icon-eye" id="toggle-password-icon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="custom-control-label text-muted" for="remember" style="font-size: 0.84rem; cursor: pointer;">
                        {{ __('Remember me on this device') }}
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block font-weight-semibold py-2" id="login-submit-btn">
                <span class="btn-label">{{ __('Sign In') }}</span>
                <i class="icon-arrow-right14 ml-1" aria-hidden="true"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top text-muted" style="font-size: 0.76rem;">
            <div><i class="icon-lock2 mr-1" aria-hidden="true"></i> {{ __('Encrypted school portal session') }}</div>
            <div class="mt-1">Powered by <strong>Sovryx Tech Pvt. Ltd.</strong></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('toggle-password-btn');
    var passwordInput = document.getElementById('password');
    var toggleIcon = document.getElementById('toggle-password-icon');
    var loginForm = document.getElementById('sms-login-form');
    var submitBtn = document.getElementById('login-submit-btn');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
            var isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleIcon.className = isHidden ? 'icon-eye-blocked' : 'icon-eye';
            toggleBtn.setAttribute('aria-label', isHidden ? '{{ __('Hide password') }}' : '{{ __('Show password') }}');
        });
    }

    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function () {
            if (typeof loginForm.checkValidity === 'function' && !loginForm.checkValidity()) {
                return;
            }
            submitBtn.disabled = true;
            var label = submitBtn.querySelector('.btn-label');
            if (label) {
                label.textContent = '{{ __('Signing in...') }}';
            }
        });
    }
});
</script>
@endsection
