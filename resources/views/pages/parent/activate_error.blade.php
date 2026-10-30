@extends('layouts.login_master')
@section('content')
    <div class="page-content">
        <div class="content-wrapper">
            <div class="content d-flex justify-content-center align-items-center">
                <div class="card mb-0 shadow-lg text-center p-4" style="width: 100%; max-width: 480px; border-radius: 18px;">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 72px; height: 72px; border-radius: 50%; background: rgba(239, 68, 68, 0.1);">
                        <i class="icon-blocked icon-2x text-danger"></i>
                    </div>
                    <h4 class="font-weight-bold text-dark mb-2">{{ __('Activation Link Invalid') }}</h4>
                    <p class="text-muted mb-4">{{ $message ?? __('This activation link is invalid, has expired, or has already been used.') }}</p>

                    <div class="alert alert-light border py-2 px-3 text-left font-size-sm mb-4">
                        <strong class="text-dark d-block mb-1"><i class="icon-info22 text-primary mr-1"></i>{{ __('Need Assistance?') }}</strong>
                        {{ __('Please reach out to the SMS Academy Administration desk to issue a new activation link or verify your linked parent contact details.') }}
                    </div>

                    <div>
                        <a href="{{ route('login') }}" class="btn btn-primary btn-block text-white font-weight-semibold py-2">
                            <i class="icon-arrow-left7 mr-2"></i>{{ __('Return to Login Page') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
