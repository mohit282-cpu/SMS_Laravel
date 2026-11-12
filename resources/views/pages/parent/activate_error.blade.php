@extends('layouts.login_master')
@section('content')
<div class="sms-auth-page">
    <div class="sms-auth-card text-center">
        <div class="sms-auth-icon" style="background-color: var(--sms-danger-soft); color: var(--sms-danger);" aria-hidden="true">
            <i class="icon-blocked"></i>
        </div>
        <h1 class="h4 font-weight-bold text-dark mb-2">{{ __('Activation Link Invalid') }}</h1>
        <p class="text-muted mb-4" style="font-size: 0.88rem;">{{ $message ?? __('This activation link is invalid, has expired, or has already been used.') }}</p>

        <div class="alert alert-light border py-2 px-3 text-left font-size-sm mb-4">
            <strong class="text-dark d-block mb-1"><i class="icon-info22 text-primary mr-1" aria-hidden="true"></i>{{ __('Need Assistance?') }}</strong>
            {{ __('Please reach out to the SMS Academy Administration desk to issue a new activation link or verify your linked parent contact details.') }}
        </div>

        <a href="{{ route('login') }}" class="btn btn-primary btn-block font-weight-semibold py-2">
            <i class="icon-arrow-left7 mr-2" aria-hidden="true"></i>{{ __('Return to Login Page') }}
        </a>
    </div>
</div>
@endsection
