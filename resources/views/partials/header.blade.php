<div id="page-header" class="page-header page-header-light shadow-sm">
    <div class="page-header-content header-elements-md-inline d-flex align-items-center justify-content-between">
        <div class="page-title d-flex align-items-center">
            <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; border-radius: 12px; background: rgba(255, 87, 0, 0.1); color: #FF5700; font-size: 1.25rem;">
                <i class="icon-grid5"></i>
            </div>
            <div>
                <h4 class="font-weight-bold mb-0 text-dark" style="letter-spacing: -0.025em; font-size: 1.35rem;">
                    @yield('page_title')
                </h4>
                <span class="text-muted font-size-xs font-weight-medium">
                    {{ Qs::getSystemName() }} &bull; {{ date('l, F j, Y') }}
                </span>
            </div>
            <a href="#" class="header-elements-toggle text-default d-md-none ml-auto"><i class="icon-more"></i></a>
        </div>

        <div class="header-elements d-none d-md-block">
            <div class="d-flex align-items-center">
                <a href="{{ Qs::userIsSuperAdmin() ? route('settings') : '#' }}" class="btn btn-link btn-float" style="background: #F3F4F6; border: 1px solid rgba(17,24,39,0.08); border-radius: 9999px; padding: 6px 16px; color: #111827; text-decoration: none; font-weight: 600; font-size: 0.82rem;">
                    <i class="icon-calendar52 mr-1" style="color: #FF5700;"></i>
                    <span>{{ __('Session') }}: <strong>{{ Qs::formatNumber(Qs::getSetting('current_session')) }}</strong></span>
                </a>
            </div>
        </div>
    </div>
</div>
