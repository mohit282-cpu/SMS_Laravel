<div class="navbar navbar-expand-md navbar-dark">
    <div class="navbar-brand-wrapper mt-2 mr-auto mr-md-5">
        <a href="{{ route('dashboard') }}" class="d-inline-block">
            <h4 class="text-bold text-white mb-0 app-brand-title">{{ Qs::getSystemName() }}</h4>
        </a>
    </div>
  {{--  <div class="navbar-brand">
        <a href="index.html" class="d-inline-block">
            <img src="{{ asset('global_assets/images/logo_light.png') }}" alt="">
        </a>
    </div>--}}

    <div class="d-md-none">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
            <i class="icon-tree5"></i>
        </button>
        <button class="navbar-toggler sidebar-mobile-main-toggle" type="button">
            <i class="icon-paragraph-justify3"></i>
        </button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block">
                    <i class="icon-paragraph-justify3"></i>
                </a>
            </li>


        </ul>

			<span class="navbar-text ml-md-3 mr-md-auto"></span>

        <ul class="navbar-nav">

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

            <li class="nav-item dropdown dropdown-user">
                <a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">
                    <img style="width: 38px; height:38px;" src="{{ Auth::user()->photo }}" class="rounded-circle" alt="photo">
                    <span>{{ Auth::user()->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ Qs::userIsStudent() ? route('students.show', Qs::hash(Qs::findStudentRecord(Auth::user()->id)->id)) : route('users.show', Qs::hash(Auth::user()->id)) }}" class="dropdown-item"><i class="icon-user-plus"></i> {{ __('My profile') }}</a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('my_account') }}" class="dropdown-item"><i class="icon-cog5"></i> {{ __('Account settings') }}</a>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault();
          document.getElementById('logout-form').submit();" class="dropdown-item"><i class="icon-switch2"></i> {{ __('Logout') }}</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>
