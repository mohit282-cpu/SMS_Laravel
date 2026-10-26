<div class="navbar navbar-expand-md navbar-dark">
    <div class="navbar-brand-wrapper mr-auto mr-md-4 d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
            <h4 class="text-bold text-white mb-0 app-brand-title">
                {{ Qs::getSystemName() }}
            </h4>
        </a>
    </div>

    <div class="d-md-none d-flex align-items-center">
        <button class="navbar-toggler sidebar-mobile-main-toggle mr-2" type="button" aria-label="Toggle Navigation">
            <i class="icon-paragraph-justify3 text-white"></i>
        </button>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile" aria-label="Toggle Menu">
            <i class="icon-more2 text-white"></i>
        </button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-flex align-items-center" title="Toggle Sidebar">
                    <i class="icon-paragraph-justify3"></i>
                </a>
            </li>
        </ul>

        <div class="navbar-text ml-md-3 mr-md-auto d-none d-lg-block">
            <span class="badge" style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.12);">
                <i class="icon-shield-check mr-1 text-warning"></i> {{ __(ucwords(str_replace('_', ' ', Auth::user()->user_type))) }}
            </span>
        </div>

        <ul class="navbar-nav align-items-md-center">
            {{-- Quick Portal Notice Link --}}
            <li class="nav-item mr-md-2">
                <a href="{{ route('notices.index') }}" class="navbar-nav-link d-flex align-items-center" title="{{ __('Notices') }}">
                    <i class="icon-bell2"></i>
                    <span class="d-md-none ml-2">{{ __('Notices') }}</span>
                </a>
            </li>

            {{-- Language Switcher Pill --}}
            <li class="nav-item dropdown mr-md-2">
                <a href="#" class="navbar-nav-link dropdown-toggle d-flex align-items-center" data-toggle="dropdown">
                    <i class="icon-earth mr-1"></i>
                    <span>{{ app()->getLocale() == 'ne' ? 'नेपाली' : 'English' }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right language-switcher-dropdown">
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
                <a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">
                    <img style="width: 34px; height: 34px; object-fit: cover;" src="{{ Auth::user()->photo }}" class="rounded-circle" alt="{{ Auth::user()->name }}">
                    <span class="font-weight-semibold text-white ml-1">{{ Auth::user()->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-right shadow-lg">
                    <div class="dropdown-header font-weight-bold text-uppercase font-size-xs text-muted">
                        {{ __('Signed in as') }} <strong class="text-dark">{{ Auth::user()->username }}</strong>
                    </div>
                    <a href="{{ Qs::userIsStudent() ? route('students.show', Qs::hash(Qs::findStudentRecord(Auth::user()->id)->id)) : route('users.show', Qs::hash(Auth::user()->id)) }}" class="dropdown-item">
                        <i class="icon-user-check text-muted"></i> {{ __('My profile') }}
                    </a>
                    <a href="{{ route('my_account') }}" class="dropdown-item">
                        <i class="icon-cog5 text-muted"></i> {{ __('Account settings') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="dropdown-item text-danger">
                        <i class="icon-switch2 text-danger"></i> {{ __('Logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>
