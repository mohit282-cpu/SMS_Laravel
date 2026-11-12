@extends('layouts.login_master')

@section('content')
<div class="sms-auth-page">
    <div class="sms-auth-card">
        <div class="text-center mb-4">
            <div class="sms-auth-icon" aria-hidden="true">
                <i class="icon-key"></i>
            </div>
            <h1 class="h4 font-weight-bold text-dark mb-1">{{ __('Password Recovery') }}</h1>
            <p class="text-muted mb-0" style="font-size: 0.84rem;">
                {{ __('Enter your registered email to receive reset instructions') }}
            </p>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="icon-checkmark-circle mr-2" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->has('email'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="icon-warning2 mr-2" aria-hidden="true"></i>
                <span>{{ $errors->first('email') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-group mb-4">
                <label for="email">
                    {{ __('Email Address') }} <span class="sms-required-mark" aria-hidden="true">*</span>
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="name@example.com"
                    required
                    autofocus
                    autocomplete="email"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block font-weight-semibold py-2">
                <i class="icon-mail5 mr-2" aria-hidden="true"></i> {{ __('Send Reset Link') }}
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
