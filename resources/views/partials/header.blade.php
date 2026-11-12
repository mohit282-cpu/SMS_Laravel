<div id="page-header" class="page-header sms-page-header">
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="sms-page-title">@yield('page_title')</h1>
            <nav aria-label="{{ __('Breadcrumb') }}">
                <ol class="sms-breadcrumb">
                    <li><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    @if(!Route::is('dashboard') && !Route::is('home'))
                        <li aria-hidden="true">/</li>
                        <li aria-current="page" class="font-weight-medium text-dark">@yield('page_title')</li>
                    @endif
                    <li aria-hidden="true" class="d-none d-md-inline">&bull;</li>
                    <li class="d-none d-md-inline">{{ app(\App\Services\BikramSambatService::class)->today()['bs_date'] }} BS ({{ date('M j, Y') }})</li>
                </ol>
            </nav>
        </div>

        <div class="d-none d-sm-flex align-items-center mt-2 mt-sm-0">
            @if(Qs::userIsSuperAdmin())
                <a href="{{ route('academic_years.index') }}" class="sms-session-pill">
                    <span class="sms-session-dot" aria-hidden="true"></span>
                    <span>{{ __('Session') }}: <strong>{{ Qs::formatNumber(Qs::getCurrentSession()) }}</strong></span>
                </a>
            @else
                <span class="sms-session-pill">
                    <span class="sms-session-dot" aria-hidden="true"></span>
                    <span>{{ __('Session') }}: <strong>{{ Qs::formatNumber(Qs::getCurrentSession()) }}</strong></span>
                </span>
            @endif
        </div>
    </div>
</div>
