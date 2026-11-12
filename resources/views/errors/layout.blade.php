<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('code') — @yield('title') | {{ config('app.name', 'SMS Academy') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/sms-design-system.css') }}" rel="stylesheet">
</head>
<body>
<main class="sms-error-page">
    <div class="sms-error-card">
        <div class="sms-error-code">@yield('code')</div>
        <h1 class="h4 font-weight-bold text-dark mb-2">@yield('title')</h1>
        <p class="text-muted mb-4" style="font-size: 0.9rem;">@yield('message')</p>
        <div class="d-flex flex-wrap justify-content-center" style="gap: 0.65rem;">
            @if(auth()->check())
                <a href="{{ url('/dashboard') }}" class="btn btn-primary">{{ __('Return to Dashboard') }}</a>
            @else
                <a href="{{ url('/') }}" class="btn btn-primary">{{ __('Return to Homepage') }}</a>
                <a href="{{ url('/login') }}" class="btn btn-light">{{ __('Sign In') }}</a>
            @endif
        </div>
        <div class="mt-4 pt-3 border-top text-muted" style="font-size: 0.76rem;">
            SMS Academy &bull; Powered by <strong>Sovryx Tech Pvt. Ltd.</strong>
        </div>
    </div>
</main>
</body>
</html>
