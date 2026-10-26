<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta id="csrf-token" name="csrf-token" content="{{ csrf_token() }}">
    <meta name="author" content="SMS Academy">

    <title> @yield('page_title') | {{ Qs::getSystemName() ?: config('app.name', 'School Management System Academy') }} </title>

    @include('partials.inc_top')
</head>

<body class="{{ in_array(Route::currentRouteName(), ['payments.invoice', 'marks.tabulation', 'marks.show', 'ttr.manage', 'ttr.show']) ? 'sidebar-xs' : '' }}">

@include('partials.top_menu')
<div class="page-content">
    @include('partials.menu')
    <div class="content-wrapper">
        @include('partials.header')

        <div class="content">
            {{-- Flash Alert Area --}}
            @if(session('flash_success'))
                <div class="alert alert-success border-0 alert-dismissible shadow-sm fade show" role="alert">
                    <i class="icon-checkmark-circle mr-2 text-success" style="font-size: 1.15rem;"></i>
                    <span class="font-weight-medium">{{ session('flash_success') }}</span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('flash_danger') || session('flash_error'))
                <div class="alert alert-danger border-0 alert-dismissible shadow-sm fade show" role="alert">
                    <i class="icon-cross2 mr-2 text-danger" style="font-size: 1.15rem;"></i>
                    <span class="font-weight-medium">{{ session('flash_danger') ?: session('flash_error') }}</span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            {{-- Validation Error Alert Area --}}
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger border-0 alert-dismissible shadow-sm fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div>
                        <div class="font-weight-bold mb-1"><i class="icon-warning2 mr-1"></i> {{ __('Please resolve the following:') }}</div>
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $er)
                                <li>{{ $er }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div id="ajax-alert" style="display: none"></div>

            @yield('content')
        </div>
    </div>
</div>

@include('partials.inc_bottom')
@yield('scripts')
</body>
</html>
