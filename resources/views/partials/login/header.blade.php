<!-- Main navbar -->
<div class="navbar navbar-expand-md navbar-dark">
    <div class="mt-2 mr-5">
        <a href="{{ route('dashboard') }}" class="d-inline-block">
            <h4 class="text-bold text-white">{{ Qs::getSystemName() }}</h4>
        </a>
    </div>

    <div class="d-md-none">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
            <i class="icon-tree5"></i>
        </button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav ml-auto">
            {{-- Language Switcher --}}
            <li class="nav-item dropdown mr-md-2">
                <a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">
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

            <li class="nav-item dropdown">
                <a href="{{ route('home') }}" class="navbar-nav-link">
                    <i class="icon-home"></i>
                    <span class="d-md-none ml-2">{{ __('Dashboard') }}</span>
                </a>
            </li>

            <li class="nav-item dropdown">
                <a href="{{ route('login') }}" class="navbar-nav-link">
                    <i class="icon-user-tie"></i>
                    <span class="d-md-none ml-2">{{ __('Sign in') }}</span>
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- /main navbar -->
