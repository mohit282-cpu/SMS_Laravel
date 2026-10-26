@extends('layouts.login_master')

@section('content')
    <div class="page-content login-cover">
        <div class="content-wrapper">
            <div class="content d-flex justify-content-center align-items-center py-5">
                <form class="login-form" method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="card login-card-apple mb-0">
                        <div class="card-body p-0">
                            <!-- Header -->
                            <div class="text-center mb-4">
                                <div class="login-brand-icon">
                                    <i class="icon-lock5"></i>
                                </div>
                                <h3 class="font-weight-bold mb-1 text-dark" style="letter-spacing: -0.03em;">
                                    {{ __('Set New Password') }}
                                </h3>
                                <p class="text-muted font-size-sm mb-0">
                                    {{ __('Create a strong password for your account') }}
                                </p>
                            </div>

                            @if($errors->any())
                                <div class="alert alert-danger border-0 alert-dismissible shadow-sm fade show mb-3" role="alert">
                                    <i class="icon-warning2 mr-2"></i>
                                    <span class="font-size-sm">{{ $errors->first() }}</span>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="form-group mb-3">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted" for="email">
                                    {{ __('Email Address') }}
                                </label>
                                <input id="email" type="email" class="form-control" name="email" value="{{ $email ?? old('email') }}" required autofocus autocomplete="email">
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted" for="password">
                                    {{ __('New Password') }}
                                </label>
                                <input id="password" type="password" class="form-control" name="password" required autocomplete="new-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                            </div>

                            <div class="form-group mb-4">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted" for="password-confirm">
                                    {{ __('Confirm New Password') }}
                                </label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                            </div>

                            <div class="form-group mb-2">
                                <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" style="font-size: 0.95rem; border-radius: 12px;">
                                    <i class="icon-checkmark4 mr-2"></i> {{ __('Reset Password') }}
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