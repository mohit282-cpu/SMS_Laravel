@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0" style="border-radius: 20px;">
                <div class="card-header bg-transparent border-0 text-center pt-4 pb-0">
                    <h3 class="font-weight-bold text-dark mb-1" style="letter-spacing: -0.03em;">{{ __('Create Account') }}</h3>
                    <p class="text-muted font-size-sm mb-0">{{ __('Register for SMS Academy portal access') }}</p>
                </div>

                <div class="card-body px-4 py-4">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="name" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Full Name') }}</label>
                            <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="{{ __('John Doe') }}">
                            @if ($errors->has('name'))
                                <span class="invalid-feedback d-block font-size-xs" role="alert">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group mb-3">
                            <label for="email" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('E-Mail Address') }}</label>
                            <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="{{ __('name@example.com') }}">
                            @if ($errors->has('email'))
                                <span class="invalid-feedback d-block font-size-xs" role="alert">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group mb-3">
                            <label for="password" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Password') }}</label>
                            <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required autocomplete="new-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                            @if ($errors->has('password'))
                                <span class="invalid-feedback d-block font-size-xs" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group mb-4">
                            <label for="password-confirm" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Confirm Password') }}</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                        </div>

                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" style="font-size: 0.95rem; border-radius: 12px;">
                                <span>{{ __('Register') }}</span>
                                <i class="icon-circle-right2 ml-2"></i>
                            </button>
                        </div>

                        <div class="text-center mt-3 pt-2 border-top">
                            <span class="text-muted font-size-xs">{{ __('Already have an account?') }}</span>
                            <a href="{{ route('login') }}" class="font-size-xs font-weight-semibold ml-1" style="color: #FF5700;">
                                {{ __('Sign In') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
