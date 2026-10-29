{{--Manage Settings--}}
<li class="nav-item">
    <a href="{{ route('settings') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['settings',]) ? 'active' : '' }}"><i class="icon-gear"></i> <span>{{ __('Settings') }}</span></a>
</li>

{{--Activity & Audit Logs (Super Admin Only)--}}
<li class="nav-item">
    <a href="{{ route('audit_logs.index') }}" class="nav-link {{ Route::is('audit_logs.*') ? 'active' : '' }}">
        <i class="icon-shield-check"></i> <span>{{ __('Activity & Audit Logs') }}</span>
    </a>
</li>

{{--Landing Page & Admissions (Super Admin Only)--}}
<li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['landing_settings.index', 'landing_settings.inquiries', 'landing_settings.entrance_results']) ? 'nav-item-expanded nav-item-open' : '' }} ">
    <a href="#" class="nav-link"><i class="icon-browser"></i> <span> {{ __('Landing Page & Admissions') }}</span></a>

    <ul class="nav nav-group-sub" data-submenu-title="{{ __('Landing Page & Admissions') }}">
        <li class="nav-item">
            <a href="{{ route('landing_settings.index') }}" class="nav-link {{ Route::is('landing_settings.index') ? 'active' : '' }}">{{ __('Site & Banner Controls') }}</a>
        </li>
        <li class="nav-item">
            <a href="{{ route('landing_settings.inquiries') }}" class="nav-link {{ Route::is('landing_settings.inquiries') ? 'active' : '' }}">{{ __('Admissions Inquiries') }}</a>
        </li>
        <li class="nav-item">
            <a href="{{ route('landing_settings.entrance_results') }}" class="nav-link {{ Route::is('landing_settings.entrance_results') ? 'active' : '' }}">{{ __('Entrance Exam Results') }}</a>
        </li>
    </ul>
</li>

{{--Pins--}}
<li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['pins.create', 'pins.index']) ? 'nav-item-expanded nav-item-open' : '' }} ">
    <a href="#" class="nav-link"><i class="icon-lock2"></i> <span> {{ __('Pins') }}</span></a>

    <ul class="nav nav-group-sub" data-submenu-title="{{ __('Manage Pins') }}">
        {{--Generate Pins--}}
            <li class="nav-item">
                <a href="{{ route('pins.create') }}"
                   class="nav-link {{ (Route::is('pins.create')) ? 'active' : '' }}">{{ __('Generate Pins') }}</a>
            </li>

        {{--    Valid/Invalid Pins  --}}
        <li class="nav-item">
            <a href="{{ route('pins.index') }}"
               class="nav-link {{ (Route::is('pins.index')) ? 'active' : '' }}">{{ __('View Pins') }}</a>
        </li>
    </ul>
</li>