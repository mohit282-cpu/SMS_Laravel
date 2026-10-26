@extends('layouts.login_master')

@section('content')
    <div class="page-content login-cover">
        <div class="content-wrapper">
            <div class="content d-flex justify-content-center align-items-center py-5">
                <form class="login-form" method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="card login-card-apple mb-0">
                        <div class="card-body p-0">
                            <!-- Header -->
                            <div class="text-center mb-4">
                                <div class="login-brand-icon">
                                    <i class="icon-key"></i>
                                </div>
                                <h3 class="font-weight-bold mb-1 text-dark" style="letter-spacing: -0.03em;">
                                    {{ __('Password Recovery') }}
                                </h3>
                                <p class="text-muted font-size-sm mb-0">
                                    {{ __('Enter your registered email to receive reset instructions') }}
                                </p>
                            </div>

                            @if (session('status'))
                                <div class="alert alert-success border-0 alert-dismissible shadow-sm fade show mb-3" role="alert">
                                    <i class="icon-checkmark-circle mr-2"></i>
                                    <span class="font-size-sm">{{ session('status') }}</span>
                                </div>
                            @endif

                            @if ($errors->has('email'))
                                <div class="alert alert-danger border-0 alert-dismissible shadow-sm fade show mb-3" role="alert">
                                    <i class="icon-warning2 mr-2"></i>
                                    <span class="font-size-sm">{{ $errors->first('email') }}</span>
                                </div>
                            @endif

                            <div class="form-group mb-4">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted" for="email">
                                    {{ __('Email Address') }}
                                </label>
                                <input id="email" name="email" required type="email" class="form-control" value="{{ old('email') }}" placeholder="{{ __('name@example.com') }}" autofocus>
                            </div>

                            <div class="form-group mb-3">
                                <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" style="font-size: 0.95rem; border-radius: 12px;">
                                    <i class="icon-mail5 mr-2"></i> {{ __('Send Reset Link') }}
                                </button>
                            </div>

                            <div class="text-center mt-3 pt-2 border-top">
                                <a href="{{ route('login') }}" class="font-size-sm font-weight-semibold" style="color: #FF5700;">
                                    <i class="icon-arrow-left7 mr-1"></i> {{ __('Back to Sign In') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
