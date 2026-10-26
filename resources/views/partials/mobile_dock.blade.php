{{-- Authentic Apple iOS Mobile App Floating Navigation Dock --}}
<div class="apple-mobile-dock d-lg-none" role="navigation" aria-label="{{ __('Mobile Dock Navigation') }}">
    <div class="apple-mobile-dock-inner">
        {{-- 1. Dashboard / Home --}}
        <a href="{{ route('dashboard') }}" class="apple-dock-item {{ (Route::is('dashboard') || Route::is('home')) ? 'active' : '' }}">
            <div class="dock-icon-wrapper">
                <i class="icon-home4"></i>
            </div>
            <span class="dock-label">{{ __('Home') }}</span>
        </a>

        {{-- 2. Academics / Timetable / Classes --}}
        @php
            $academicsRoute = route('dashboard');
            $academicsActive = false;
            if(Qs::userIsAcademic()) {
                $academicsRoute = route('tt.index');
                $academicsActive = in_array(Route::currentRouteName(), ['tt.index', 'ttr.edit', 'ttr.show', 'ttr.manage', 'tt.my_timetable']);
            } elseif(Qs::userIsStudent() || Qs::userIsTeacher()) {
                $academicsRoute = route('tt.my_timetable');
                $academicsActive = Route::is('tt.my_timetable');
            } elseif(Qs::userIsParent()) {
                $academicsRoute = route('my_children');
                $academicsActive = Route::is('my_children');
            } elseif(Qs::userIsTeamSA()) {
                $academicsRoute = route('classes.index');
                $academicsActive = in_array(Route::currentRouteName(), ['classes.index', 'classes.edit', 'students.list']);
            }
        @endphp
        <a href="{{ $academicsRoute }}" class="apple-dock-item {{ $academicsActive ? 'active' : '' }}">
            <div class="dock-icon-wrapper">
                <i class="icon-graduation2"></i>
            </div>
            <span class="dock-label">{{ __('Academics') }}</span>
        </a>

        {{-- 3. Notices & Calendar --}}
        <a href="{{ route('notices.index') }}" class="apple-dock-item {{ Route::is('notices.*') ? 'active' : '' }}">
            <div class="dock-icon-wrapper">
                <i class="icon-bell2"></i>
                @php
                    $unreadCount = \App\Models\Notice::visibleTo(Auth::user())->where('is_published', true)->count();
                @endphp
                @if($unreadCount > 0)
                    <span class="dock-badge-dot"></span>
                @endif
            </div>
            <span class="dock-label">{{ __('Notices') }}</span>
        </a>

        {{-- 4. Role Action / Finance / Library --}}
        @php
            $actionRoute = route('dashboard');
            $actionIcon = 'icon-credit-card';
            $actionLabel = __('Finance');
            $actionActive = false;
            if(Qs::userIsTeamAccount()) {
                $actionRoute = route('payments.index');
                $actionIcon = 'icon-cash3';
                $actionLabel = __('Payments');
                $actionActive = in_array(Route::currentRouteName(), ['payments.index', 'payments.create', 'payments.manage']);
            } elseif(Qs::userIsStudent() || Qs::userIsParent()) {
                $actionRoute = route('payments.invoice', [Qs::hash(Auth::user()->id), Qs::getCurrentSession()]);
                $actionIcon = 'icon-cash';
                $actionLabel = __('Fees');
                $actionActive = in_array(Route::currentRouteName(), ['payments.invoice', 'payments.receipts']);
            } elseif(Qs::userIsLibrarian()) {
                $actionRoute = route('books.index');
                $actionIcon = 'icon-books';
                $actionLabel = __('Library');
                $actionActive = Route::is('books.*');
            } else {
                $actionRoute = route('users.index');
                $actionIcon = 'icon-users4';
                $actionLabel = __('Users');
                $actionActive = Route::is('users.*');
            }
        @endphp
        <a href="{{ $actionRoute }}" class="apple-dock-item {{ $actionActive ? 'active' : '' }}">
            <div class="dock-icon-wrapper">
                <i class="{{ $actionIcon }}"></i>
            </div>
            <span class="dock-label">{{ $actionLabel }}</span>
        </a>

        {{-- 5. More (Native iOS Drawer / Sheet Trigger) --}}
        <button type="button" class="apple-dock-item sidebar-mobile-main-toggle" aria-label="{{ __('Open App Navigation') }}">
            <div class="dock-icon-wrapper">
                <i class="icon-grid5"></i>
            </div>
            <span class="dock-label">{{ __('Menu') }}</span>
        </button>
    </div>
</div>
