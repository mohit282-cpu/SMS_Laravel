{{-- Favicon and PWA Meta --}}
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('assets/images/icons/icon-maskable-512x512.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/images/icons/icon-192x192.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('global_assets/images/favicon.png') }}">
<link rel="shortcut icon" href="{{ asset('assets/images/icons/icon-maskable-512x512.png') }}">
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#1e2b37">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ Qs::getSystemTitle() ?: config('app.name', 'SMS Academy') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/images/icons/apple-touch-icon.png') }}">

<!-- Global stylesheets -->
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" type="text/css">
<link href="{{ asset('global_assets/css/icons/icomoon/styles.css') }}" rel="stylesheet" type="text/css">
<link href=" {{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
<link href=" {{ asset('assets/css/bootstrap_limitless.min.css') }}" rel="stylesheet" type="text/css">
<link href=" {{ asset('assets/css/layout.min.css') }}" rel="stylesheet" type="text/css">
<link href=" {{ asset('assets/css/components.min.css') }}" rel="stylesheet" type="text/css">
<link href=" {{ asset('assets/css/colors.min.css') }}" rel="stylesheet" type="text/css">
<link href=" {{ asset('assets/css/qs.css') }}" rel="stylesheet" type="text/css">
<link href=" {{ asset('assets/css/pwa.css') }}" rel="stylesheet" type="text/css">
<!-- /global stylesheets -->

<!-- Core JS files -->
<script src="{{ asset('global_assets/js/main/jquery.min.js') }} "></script>
<script src="{{ asset('global_assets/js/main/bootstrap.bundle.min.js') }} "></script>
<script src="{{ asset('global_assets/js/plugins/loaders/blockui.min.js') }} "></script>
<!-- /core JS files -->

<!-- Theme JS files -->
<script src="{{ asset('global_assets/js/plugins/forms/styling/uniform.min.js') }} "></script>
<script src="{{ asset('assets/js/app.js') }} "></script>
<script src="{{ asset('global_assets/js/demo_pages/login.js') }} "></script>
<!-- /theme JS files -->