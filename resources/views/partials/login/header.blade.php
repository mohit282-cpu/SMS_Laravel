<!-- Main navbar -->
<div class="navbar navbar-expand-md navbar-dark shadow-sm">
    <div class="mr-auto mr-md-4">
        <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
            <h4 class="text-bold text-white mb-0 app-brand-title">{{ Qs::getSystemName() }}</h4>
        </a>
    </div>

    <div class="d-md-none">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile" aria-label="Toggle navigation">
            <i class="icon-more2 text-white"></i>
        </button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav ml-auto align-items-md-center">
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

            <li class="nav-item">
                <a href="{{ route('home') }}" class="navbar-nav-link d-flex align-items-center">
                    <i class="icon-home2 mr-1"></i>
                    <span>{{ __('Portal Home') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('login') }}" class="navbar-nav-link d-flex align-items-center active font-weight-semibold">
                    <i class="icon-user-lock mr-1" style="color: #FF5700;"></i>
                    <span>{{ __('Sign In') }}</span>
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- /main navbar -->
