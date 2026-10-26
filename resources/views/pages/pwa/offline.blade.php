<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#1e2b37">
    <title>{{ __('Offline Mode') }} | {{ Qs::getSystemName() ?: config('app.name', 'School Management System Academy') }}</title>

    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('assets/images/icons/icon-maskable-512x512.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/images/icons/icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('global_assets/images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/icons/icon-maskable-512x512.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/pwa.css') }}" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #1e2b37 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Roboto', 'Noto Sans Devanagari', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .offline-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            max-width: 580px;
            width: 100%;
            padding: 40px 32px;
            text-align: center;
            animation: fadeIn 0.4s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .icon-wrapper {
            width: 88px;
            height: 88px;
            margin: 0 auto 24px;
            background: #fff3e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e65100;
            font-size: 42px;
        }
        .offline-badge {
            display: inline-block;
            background: #ffebee;
            color: #c62828;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }
        .feature-box {
            background: #f8f9fa;
            border-left: 4px solid #1e88e5;
            padding: 16px;
            border-radius: 8px;
            text-align: left;
            margin: 24px 0;
            font-size: 14px;
        }
        .feature-box ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }
        .feature-box li {
            margin-bottom: 6px;
        }
        .btn-retry {
            background: #1e88e5;
            color: #fff;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 8px;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-retry:hover {
            background: #1565c0;
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-outline-back {
            border: 1px solid #ced4da;
            color: #495057;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-outline-back:hover {
            background: #e9ecef;
            color: #212529;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="icon-wrapper">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="1" y1="1" x2="23" y2="23"></line>
                <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
                <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
                <path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path>
                <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                <line x1="12" y1="20" x2="12.01" y2="20"></line>
            </svg>
        </div>

        <span class="offline-badge">
            ● {{ __('Offline Mode / अफलाइन मोड') }}
        </span>

        <h3 class="font-weight-bold mb-2">{{ Qs::getSystemName() ?: config('app.name', 'School Management System Academy') }}</h3>
        <p class="text-muted">
            {{ __('You are currently disconnected from the internet. The portal has preserved your offline shell and cached resources.') }}
        </p>

        <div class="feature-box">
            <strong>{{ __('Important School System Guidelines / महत्त्वपूर्ण जानकारी:') }}</strong>
            <ul>
                <li>{{ __('Offline Shell: Core layout design and branding assets remain available while disconnected.') }}</li>
                <li>{{ __('Live Records Required: Student records, examination marks, and attendance data are never stored in browser caches to protect student privacy.') }}</li>
                <li>{{ __('Audit Safety: Recording attendance, publishing marks, generating invoices, and processing fee payments strictly require a live server connection.') }}</li>
            </ul>
        </div>

        <div class="mt-4">
            <button class="btn btn-retry" onclick="checkAndReload()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: sub; margin-right: 6px;">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <polyline points="23 20 23 14 17 14"></polyline>
                    <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                </svg>
                {{ __('Retry Connection / पुनः प्रयास') }}
            </button>
            <a href="{{ url('/') }}" class="btn-outline-back">
                {{ __('Go to Home / गृहपृष्ठ') }}
            </a>
        </div>

        <p id="connection-status" class="small text-muted mt-3 mb-0">
            {{ __('Checking network connection...') }}
        </p>
    </div>

    <script>
        function updateStatus() {
            var statusEl = document.getElementById('connection-status');
            if (navigator.onLine) {
                statusEl.innerHTML = '<span class="text-success font-weight-bold">● {{ __("Internet connection restored! Reloading...") }}</span>';
                setTimeout(function() {
                    window.location.reload();
                }, 1200);
            } else {
                statusEl.innerHTML = '<span class="text-danger">● {{ __("Still offline. Please check your WiFi or mobile network.") }}</span>';
            }
        }

        function checkAndReload() {
            var statusEl = document.getElementById('connection-status');
            statusEl.innerText = '{{ __("Reconnecting...") }}';
            if (navigator.onLine) {
                window.location.reload();
            } else {
                setTimeout(updateStatus, 800);
            }
        }

        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
        updateStatus();
    </script>
</body>
</html>
