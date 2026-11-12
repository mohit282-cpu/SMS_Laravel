@extends('layouts.login_master')

@section('content')
<div class="sms-auth-page">
    <div class="sms-auth-card">
        <div class="text-center mb-4">
            <div class="sms-auth-icon" aria-hidden="true">
                <i class="icon-lock5"></i>
            </div>
            <h1 class="h4 font-weight-bold text-dark mb-1">{{ __('Set New Password') }}</h1>
            <p class="text-muted mb-0" style="font-size: 0.84rem;">
                {{ __('Create a strong password for your account') }}
            </p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="icon-warning2 mr-2" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group mb-3">
                <label for="email">
                    {{ __('Email Address') }} <span class="sms-required-mark" aria-hidden="true">*</span>
                </label>
                <input id="email" type="email" class="form-control" name="email" value="{{ $email ?? old('email') }}" required autofocus autocomplete="email">
            </div>

            <div class="form-group mb-3">
                <label for="password">
                    {{ __('New Password') }} <span class="sms-required-mark" aria-hidden="true">*</span>
                </label>
                <input id="password" type="password" class="form-control" name="password" required autocomplete="new-password" placeholder="{{ __('Minimum 8 characters') }}">
            </div>

            <div class="form-group mb-4">
                <label for="password-confirm">
                    {{ __('Confirm New Password') }} <span class="sms-required-mark" aria-hidden="true">*</span>
                </label>
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="{{ __('Re-enter new password') }}">
            </div>

            <button type="submit" class="btn btn-primary btn-block font-weight-semibold py-2">
                <i class="icon-checkmark4 mr-2" aria-hidden="true"></i> {{ __('Reset Password') }}
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <a href="{{ route('login') }}" class="font-weight-semibold" style="font-size: 0.84rem;">
                <i class="icon-arrow-left7 mr-1" aria-hidden="true"></i> {{ __('Back to Sign In') }}
            </a>
        </div>
    </div>
</div>
@endsection
