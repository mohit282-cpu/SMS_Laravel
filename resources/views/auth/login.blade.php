@extends('layouts.login_master')

@section('content')
    <div class="page-content login-cover">
        <div class="content-wrapper">
            <div class="content d-flex justify-content-center align-items-center py-4 py-md-5">
                
                {{-- From Uiverse.io by vinodjangid07, adapted for SMS Laravel with responsive craft & Laravel Auth --}}
                <form method="post" action="{{ route('login') }}" class="form_main">
                    @csrf
                    
                    {{-- System Title / Logo --}}
                    <div class="text-center mb-2" style="z-index: 2;">
                        <span class="badge badge-primary px-3 py-1 mb-2" style="background: rgba(162, 104, 255, 0.15) !important; color: rgb(110, 50, 215) !important; border-color: rgba(162, 104, 255, 0.3) !important;">
                            <i class="icon-shield-check mr-1"></i> {{ Qs::getSystemName() }}
                        </span>
                        <h1 class="heading">{{ __('Login') }}</h1>
                        <p class="text-muted font-size-sm mb-3">
                            {{ __('Sign in to access your administrative portal') }}
                        </p>
                    </div>

                    {{-- Error Feedback Alert --}}
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger border-0 alert-dismissible fade show w-100 mb-3" style="z-index: 2; border-radius: 12px; font-size: 0.85rem;" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="icon-warning2 mr-2"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="padding: 0.5rem 0.75rem;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    {{-- Username / Email Field --}}
                    <div class="inputContainer">
                        <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#64748B" viewBox="0 0 16 16">
                            <path d="M13.106 7.222c0-2.967-2.249-5.032-5.482-5.032-3.35 0-5.646 2.318-5.646 5.702 0 3.493 2.235 5.708 5.762 5.708.862 0 1.689-.123 2.304-.335v-.862c-.43.199-1.354.328-2.29.328-2.926 0-4.813-1.88-4.813-4.798 0-2.844 1.921-4.881 4.594-4.881 2.735 0 4.608 1.688 4.608 4.156 0 1.682-.554 2.769-1.416 2.769-.492 0-.772-.28-.772-.76V5.206H8.923v.834h-.11c-.266-.595-.881-.964-1.6-.964-1.4 0-2.378 1.162-2.378 2.823 0 1.737.957 2.906 2.379 2.906.8 0 1.415-.39 1.709-1.087h.11c.081.67.703 1.148 1.503 1.148 1.572 0 2.57-1.415 2.57-3.643zm-7.177.704c0-1.197.54-1.907 1.456-1.907.93 0 1.524.738 1.524 1.907S8.308 9.84 7.371 9.84c-.895 0-1.442-.725-1.442-1.914z"></path>
                        </svg>
                        <input type="text" class="inputField" id="username" name="identity" value="{{ old('identity') }}" placeholder="{{ __('Username or Email') }}" required autofocus autocomplete="username">
                    </div>
                    
                    {{-- Password Field --}}
                    <div class="inputContainer">
                        <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#64748B" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"></path>
                        </svg>
                        <input type="password" class="inputField" id="password" name="password" placeholder="{{ __('Password') }}" required autocomplete="current-password">
                    </div>

                    {{-- Remember Me Option --}}
                    <div class="d-flex align-items-center justify-content-between w-100 my-2" style="z-index: 2;">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="custom-control-label font-size-sm text-muted font-weight-medium" for="remember" style="cursor: pointer;">
                                {{ __('Remember me') }}
                            </label>
                        </div>
                    </div>
                                  
                    {{-- Submit Button --}}
                    <button id="button" type="submit">
                        <span>{{ __('Submit') }}</span>
                        <i class="icon-arrow-right14 ml-1"></i>
                    </button>

                    {{-- Forgot Password Link --}}
                    @if (Route::has('password.request'))
                        <a class="forgotLink" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                    @endif

                    {{-- Security Footnote --}}
                    <div class="text-center mt-3 pt-2 border-top w-100" style="z-index: 2; border-color: rgba(0, 0, 0, 0.06) !important;">
                        <span class="text-muted font-size-xs">
                            <i class="icon-lock2 mr-1"></i> {{ __('End-to-end encrypted session') }}
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

