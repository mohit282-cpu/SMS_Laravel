@extends('layouts.login_master')

@section('content')
<div class="sms-auth-page">
    <div class="sms-auth-card">
        <div class="text-center mb-4">
            <div class="sms-auth-icon" aria-hidden="true">
                <i class="icon-user-plus"></i>
            </div>
            <h1 class="h4 font-weight-bold text-dark mb-1">{{ __('Create Account') }}</h1>
            <p class="text-muted mb-0" style="font-size: 0.84rem;">
                {{ __('Register for SMS Academy portal access') }}
            </p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group mb-3">
                <label for="name">{{ __('Full Name') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="{{ __('John Doe') }}">
                @if ($errors->has('name'))
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $errors->first('name') }}</strong></span>
                @endif
            </div>

            <div class="form-group mb-3">
                <label for="email">{{ __('E-Mail Address') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@example.com">
                @if ($errors->has('email'))
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $errors->first('email') }}</strong></span>
                @endif
            </div>

            <div class="form-group mb-3">
                <label for="password">{{ __('Password') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required autocomplete="new-password" placeholder="{{ __('Minimum 8 characters') }}">
                @if ($errors->has('password'))
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $errors->first('password') }}</strong></span>
                @endif
            </div>

            <div class="form-group mb-4">
                <label for="password-confirm">{{ __('Confirm Password') }} <span class="sms-required-mark" aria-hidden="true">*</span></label>
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="{{ __('Re-enter password') }}">
            </div>

            <button type="submit" class="btn btn-primary btn-block font-weight-semibold py-2">
                <span>{{ __('Register') }}</span>
                <i class="icon-circle-right2 ml-2" aria-hidden="true"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <span class="text-muted" style="font-size: 0.82rem;">{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" class="font-weight-semibold ml-1" style="font-size: 0.82rem;">
                {{ __('Sign In') }}
            </a>
        </div>
    </div>
</div>
@endsection
