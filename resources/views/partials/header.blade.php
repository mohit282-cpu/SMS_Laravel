<div id="page-header" class="page-header apple-page-header-glass">
    <div class="page-header-content d-flex align-items-center justify-content-between">
        <div class="page-title d-flex align-items-center">
            <div class="header-icon-disc mr-3">
                <i class="icon-grid5"></i>
            </div>
            <div>
                <h4 class="page-title-heading mb-0">
                    @yield('page_title')
                </h4>
                <div class="page-title-meta">
                    <span>{{ Qs::getSystemName() }}</span>
                    <span class="meta-dot">&bull;</span>
                    <span>{{ app(\App\Services\BikramSambatService::class)->today()['bs_date'] }} BS ({{ date('D, M j, Y') }})</span>
                </div>
            </div>
        </div>

        <div class="header-elements d-none d-sm-block">
            <div class="d-flex align-items-center">
                <a href="{{ Qs::userIsSuperAdmin() ? route('academic_years.index') : '#' }}" class="apple-session-pill">
                    <span class="session-icon-dot" style="background: #FF5700;"></span>
                    <span>{{ __('Session') }}: <strong>{{ Qs::formatNumber(Qs::getCurrentSession()) }}</strong></span>
                </a>
            </div>
        </div>
    </div>
</div>
