@extends('layouts.login_master')

@section('content')
    <div class="page-content login-cover">
        <div class="content-wrapper">
            <div class="content d-flex justify-content-center align-items-center py-5">
                <form class="login-form" method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="card login-card-apple mb-0">
                        <div class="card-body p-0">
                            <!-- Brand & Welcome Header -->
                            <div class="text-center mb-4">
                                <div class="login-brand-icon">
                                    <i class="icon-shield-check"></i>
                                </div>
                                <h3 class="font-weight-bold mb-1 text-dark" style="letter-spacing: -0.03em;">
                                    {{ __('Welcome Back') }}
                                </h3>
                                <p class="text-muted font-size-sm mb-0">
                                    {{ __('Sign in to access your school dashboard') }}
                                </p>
                            </div>

                            <!-- Error Feedback -->
                            @if (isset($errors) && $errors->any())
                                <div class="alert alert-danger border-0 alert-dismissible shadow-sm fade show mb-3" role="alert">
                                    <i class="icon-warning2 mr-2"></i>
                                    <span class="font-size-sm">{{ $errors->first() }}</span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <!-- Identity Field -->
                            <div class="form-group mb-3">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted" for="identity">
                                    {{ __('Login ID, Username, or Email') }}
                                </label>
                                <div class="position-relative">
                                    <input type="text" id="identity" class="form-control pl-3 pr-4" name="identity" value="{{ old('identity') }}" placeholder="{{ __('e.g. admin or admin@admin.com') }}" required autofocus autocomplete="username">
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-weight-semibold font-size-xs text-uppercase text-muted mb-0" for="password">
                                        {{ __('Password') }}
                                    </label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="font-size-xs font-weight-semibold" style="color: #FF5700;">
                                            {{ __('Forgot password?') }}
                                        </a>
                                    @endif
                                </div>
                                <div class="position-relative">
                                    <input required id="password" name="password" type="password" class="form-control pl-3 pr-4" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autocomplete="current-password">
                                </div>
                            </div>

                            <!-- Remember Me -->
                            <div class="form-group d-flex align-items-center mb-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="custom-control-label font-size-sm text-muted font-weight-medium" for="remember">
                                        {{ __('Keep me signed in') }}
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group mb-2">
                                <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" style="font-size: 0.95rem; border-radius: 12px;">
                                    <span>{{ __('Sign In') }}</span>
                                    <i class="icon-arrow-right14 ml-2"></i>
                                </button>
                            </div>

                            <!-- Portal Info Footnote -->
                            <div class="text-center mt-4 pt-2 border-top">
                                <span class="text-muted font-size-xs">
                                    <i class="icon-lock2 mr-1"></i> {{ __('End-to-end encrypted school administration session') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
