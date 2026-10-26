<div class="navbar navbar-expand-md navbar-dark apple-navbar-glass">
    <div class="navbar-brand-wrapper mr-auto mr-md-4 d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
            <span class="brand-liquid-pill mr-2">
                <i class="icon-graduation2"></i>
            </span>
            <h4 class="text-bold text-white mb-0 app-brand-title">
                {{ Qs::getSystemName() }}
            </h4>
        </a>
    </div>

    {{-- Mobile Top Bar Quick Actions (iOS Style) --}}
    <div class="d-md-none d-flex align-items-center ml-auto">
        {{-- Mobile Language Toggle --}}
        <a href="{{ route('set_locale', app()->getLocale() == 'ne' ? 'en' : 'ne') }}" class="btn-glass-icon mr-2" title="{{ __('Switch Language') }}">
            <span class="font-weight-bold" style="font-size: 0.72rem; color: #FFFFFF;">{{ app()->getLocale() == 'ne' ? 'EN' : 'ने' }}</span>
        </a>

        {{-- Mobile Notices Bell --}}
        <a href="{{ route('notices.index') }}" class="btn-glass-icon mr-2 position-relative" title="{{ __('Notices') }}">
            <i class="icon-bell2 text-white"></i>
            @php
                $mobileNoticeCount = \App\Models\Notice::visibleTo(Auth::user())->where('is_published', true)->count();
            @endphp
            @if($mobileNoticeCount > 0)
                <span class="mobile-notification-dot"></span>
            @endif
        </a>

        {{-- Mobile User Avatar Dropdown --}}
        <div class="dropdown">
            <a href="#" class="d-inline-block position-relative" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img style="width: 36px; height: 36px; object-fit: cover; border: 2px solid #FF5700; box-shadow: 0 0 10px rgba(255, 87, 0, 0.4);" src="{{ Auth::user()->photo }}" class="rounded-circle" alt="{{ Auth::user()->name }}">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow-lg apple-glass-dropdown">
                <div class="dropdown-header font-weight-bold text-uppercase font-size-xs text-muted">
                    {{ __('Signed in as') }} <strong class="text-white">{{ Auth::user()->username }}</strong>
                </div>
                <a href="{{ Qs::userIsStudent() ? route('students.show', Qs::hash(Qs::findStudentRecord(Auth::user()->id)->id)) : route('users.show', Qs::hash(Auth::user()->id)) }}" class="dropdown-item">
                    <i class="icon-user-check text-muted mr-2"></i> {{ __('My Profile') }}
                </a>
                <a href="{{ route('my_account') }}" class="dropdown-item">
                    <i class="icon-cog5 text-muted mr-2"></i> {{ __('Account Settings') }}
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();" class="dropdown-item text-danger">
                    <i class="icon-switch2 text-danger mr-2"></i> {{ __('Logout') }}
                </a>
                <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    {{-- Desktop Header Elements --}}
    <div class="collapse navbar-collapse d-none d-md-flex" id="navbar-mobile">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-flex align-items-center" title="{{ __('Toggle Sidebar') }}">
                    <i class="icon-paragraph-justify3"></i>
                </a>
            </li>
        </ul>

        <div class="navbar-text ml-md-3 mr-md-auto d-none d-lg-block">
            <span class="badge apple-role-badge">
                <i class="icon-shield-check mr-1" style="color: #FF5700;"></i> {{ __(ucwords(str_replace('_', ' ', Auth::user()->user_type))) }}
            </span>
        </div>

        <ul class="navbar-nav align-items-md-center">
            {{-- Quick Portal Notice Link --}}
            <li class="nav-item mr-md-2">
                <a href="{{ route('notices.index') }}" class="navbar-nav-link d-flex align-items-center apple-nav-icon-link" title="{{ __('Notices') }}">
                    <i class="icon-bell2"></i>
                    @if(isset($mobileNoticeCount) && $mobileNoticeCount > 0)
                        <span class="badge badge-pill ml-1" style="background: #FF5700; color: #FFF; font-size: 0.65rem;">{{ $mobileNoticeCount }}</span>
                    @endif
                </a>
            </li>

            {{-- Language Switcher Pill --}}
            <li class="nav-item dropdown mr-md-2">
                <a href="#" class="navbar-nav-link dropdown-toggle d-flex align-items-center apple-lang-pill" data-toggle="dropdown">
                    <i class="icon-earth mr-1"></i>
                    <span>{{ app()->getLocale() == 'ne' ? 'नेपाली' : 'English' }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow-lg apple-glass-dropdown">
                    <a href="{{ route('set_locale', 'ne') }}" class="dropdown-item {{ app()->getLocale() == 'ne' ? 'active' : '' }}">
                        <i class="icon-check {{ app()->getLocale() == 'ne' ? '' : 'invisible' }}"></i> नेपाली (Nepali)
                    </a>
                    <a href="{{ route('set_locale', 'en') }}" class="dropdown-item {{ app()->getLocale() == 'en' ? 'active' : '' }}">
                        <i class="icon-check {{ app()->getLocale() == 'en' ? '' : 'invisible' }}"></i> English
                    </a>
                </div>
            </li>

            {{-- User Profile Pill --}}
            <li class="nav-item dropdown dropdown-user">
                <a href="#" class="navbar-nav-link dropdown-toggle apple-user-pill" data-toggle="dropdown">
                    <img style="width: 34px; height: 34px; object-fit: cover;" src="{{ Auth::user()->photo }}" class="rounded-circle" alt="{{ Auth::user()->name }}">
                    <span class="font-weight-semibold text-white ml-2">{{ Auth::user()->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-right shadow-lg apple-glass-dropdown">
                    <div class="dropdown-header font-weight-bold text-uppercase font-size-xs text-muted">
                        {{ __('Signed in as') }} <strong class="text-white">{{ Auth::user()->username }}</strong>
                    </div>
                    <a href="{{ Qs::userIsStudent() ? route('students.show', Qs::hash(Qs::findStudentRecord(Auth::user()->id)->id)) : route('users.show', Qs::hash(Auth::user()->id)) }}" class="dropdown-item">
                        <i class="icon-user-check text-muted mr-2"></i> {{ __('My Profile') }}
                    </a>
                    <a href="{{ route('my_account') }}" class="dropdown-item">
                        <i class="icon-cog5 text-muted mr-2"></i> {{ __('Account Settings') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="dropdown-item text-danger">
                        <i class="icon-switch2 text-danger mr-2"></i> {{ __('Logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>
