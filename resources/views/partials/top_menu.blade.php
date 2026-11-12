@php
    $currentUser = Auth::user();
    $studentRec = ($currentUser && Qs::userIsStudent()) ? ($shellStudentRecord ?? Qs::findStudentRecord($currentUser->id)) : null;
    $myProfileUrl = $studentRec ? route('students.show', Qs::hash($studentRec->id)) : ($currentUser ? route('users.show', Qs::hash($currentUser->id)) : route('my_account'));
    $noticeCount = $shellNoticeCount ?? 0;
@endphp
<nav class="navbar navbar-expand-md navbar-dark sms-navbar" aria-label="{{ __('Top Navigation') }}">
    <div class="navbar-brand-wrapper mr-auto mr-md-4 d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
            <span class="sms-brand-mark mr-2" aria-hidden="true">
                <i class="icon-graduation2"></i>
            </span>
            <span class="sms-brand-title">
                {{ Qs::getSystemName() }}
            </span>
        </a>
    </div>

    {{-- Mobile Top Bar Quick Actions --}}
    <div class="d-md-none d-flex align-items-center ml-auto">
        <a href="{{ route('set_locale', app()->getLocale() == 'ne' ? 'en' : 'ne') }}"
           class="sms-nav-pill mr-1"
           aria-label="{{ __('Switch Language') }}"
           title="{{ __('Switch Language') }}">
            <span class="font-weight-bold" style="font-size: 0.75rem;">{{ app()->getLocale() == 'ne' ? 'EN' : 'ने' }}</span>
        </a>

        <a href="{{ route('notices.index') }}"
           class="sms-nav-pill mr-2 position-relative"
           aria-label="{{ __('Notices') }}"
           title="{{ __('Notices') }}">
            <i class="icon-bell2" aria-hidden="true"></i>
            @if($noticeCount > 0)
                <span class="mobile-notification-dot" aria-label="{{ $noticeCount }} {{ __('notices') }}"></span>
            @endif
        </a>

        <div class="dropdown">
            <a href="#" class="d-inline-flex align-items-center" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('User menu') }}">
                <img src="{{ Auth::user()->photo }}" class="sms-avatar-sm" alt="{{ Auth::user()->name }}">
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-header font-weight-semibold text-uppercase font-size-xs text-muted">
                    {{ __('Signed in as') }} <strong class="text-dark">{{ Auth::user()->username }}</strong>
                </div>
                <a href="{{ $myProfileUrl }}" class="dropdown-item">
                    <i class="icon-user-check text-muted mr-2" aria-hidden="true"></i> {{ __('My Profile') }}
                </a>
                <a href="{{ route('my_account') }}" class="dropdown-item">
                    <i class="icon-cog5 text-muted mr-2" aria-hidden="true"></i> {{ __('Account Settings') }}
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();" class="dropdown-item text-danger">
                    <i class="icon-switch2 text-danger mr-2" aria-hidden="true"></i> {{ __('Logout') }}
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
                <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-flex align-items-center" aria-label="{{ __('Toggle Sidebar') }}" title="{{ __('Toggle Sidebar') }}">
                    <i class="icon-paragraph-justify3" aria-hidden="true"></i>
                </a>
            </li>
        </ul>

        <div class="navbar-text ml-md-3 mr-md-auto d-none d-lg-block">
            <span class="sms-role-badge">
                <i class="icon-shield-check" style="color: var(--sms-primary);" aria-hidden="true"></i>
                <span>{{ __(ucwords(str_replace('_', ' ', Auth::user()->user_type))) }}</span>
            </span>
        </div>

        <ul class="navbar-nav align-items-md-center">
            <li class="nav-item mr-md-2">
                <a href="{{ route('notices.index') }}" class="sms-nav-pill position-relative" aria-label="{{ __('Notices') }}" title="{{ __('Notices') }}">
                    <i class="icon-bell2" aria-hidden="true"></i>
                    @if($noticeCount > 0)
                        <span class="badge badge-primary badge-pill ml-1" style="font-size: 0.68rem;">{{ $noticeCount }}</span>
                    @endif
                </a>
            </li>

            <li class="nav-item dropdown mr-md-2">
                <a href="#" class="sms-nav-pill dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="icon-earth mr-1" aria-hidden="true"></i>
                    <span>{{ app()->getLocale() == 'ne' ? 'नेपाली' : 'English' }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('set_locale', 'ne') }}" class="dropdown-item {{ app()->getLocale() == 'ne' ? 'active' : '' }}">
                        <i class="icon-check mr-2 {{ app()->getLocale() == 'ne' ? '' : 'invisible' }}" aria-hidden="true"></i> नेपाली (Nepali)
                    </a>
                    <a href="{{ route('set_locale', 'en') }}" class="dropdown-item {{ app()->getLocale() == 'en' ? 'active' : '' }}">
                        <i class="icon-check mr-2 {{ app()->getLocale() == 'en' ? '' : 'invisible' }}" aria-hidden="true"></i> English
                    </a>
                </div>
            </li>

            <li class="nav-item dropdown dropdown-user">
                <a href="#" class="sms-nav-pill dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="{{ Auth::user()->photo }}" class="sms-avatar-sm mr-2" alt="{{ Auth::user()->name }}">
                    <span class="font-weight-medium text-white">{{ Auth::user()->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-right">
                    <div class="dropdown-header font-weight-semibold text-uppercase font-size-xs text-muted">
                        {{ __('Signed in as') }} <strong class="text-dark">{{ Auth::user()->username }}</strong>
                    </div>
                    <a href="{{ $myProfileUrl }}" class="dropdown-item">
                        <i class="icon-user-check text-muted mr-2" aria-hidden="true"></i> {{ __('My Profile') }}
                    </a>
                    <a href="{{ route('my_account') }}" class="dropdown-item">
                        <i class="icon-cog5 text-muted mr-2" aria-hidden="true"></i> {{ __('Account Settings') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="dropdown-item text-danger">
                        <i class="icon-switch2 text-danger mr-2" aria-hidden="true"></i> {{ __('Logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>
