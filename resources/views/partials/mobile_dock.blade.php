{{-- Mobile Bottom Navigation Dock --}}
@php
    $noticeCount = $shellNoticeCount ?? 0;

    $academicsRoute = route('dashboard');
    $academicsLabel = __('Academics');
    $academicsActive = false;

    if (Qs::userIsTeamSA()) {
        $academicsRoute = route('classes.index');
        $academicsActive = in_array(Route::currentRouteName(), ['classes.index', 'classes.edit', 'students.list']);
    } elseif (Qs::userIsTeacher() || Qs::userIsStudent()) {
        $academicsRoute = route('tt.my_timetable');
        $academicsLabel = __('Timetable');
        $academicsActive = Route::is('tt.my_timetable');
    } elseif (Qs::userIsAcademic()) {
        $academicsRoute = route('tt.index');
        $academicsActive = in_array(Route::currentRouteName(), ['tt.index', 'ttr.edit', 'ttr.show', 'ttr.manage', 'tt.my_timetable']);
    } elseif (Qs::userIsParent()) {
        $academicsRoute = route('my_children');
        $academicsLabel = __('Children');
        $academicsActive = Route::is('my_children');
    } elseif (Qs::userIsLibrarian()) {
        $academicsRoute = route('books.circulations');
        $academicsLabel = __('Circulation');
        $academicsActive = Route::is('books.circulations');
    }

    $actionRoute = route('my_account');
    $actionIcon = 'icon-user';
    $actionLabel = __('Account');
    $actionActive = Route::is('my_account');

    if (Qs::userIsTeamAccount()) {
        $actionRoute = route('payments.index');
        $actionIcon = 'icon-cash3';
        $actionLabel = __('Payments');
        $actionActive = Route::is('payments.*');
    } elseif (Qs::userIsStudent()) {
        $actionRoute = route('payments.invoice', [Qs::hash(Auth::id()), Qs::getCurrentSession()]);
        $actionIcon = 'icon-cash';
        $actionLabel = __('Fees');
        $actionActive = in_array(Route::currentRouteName(), ['payments.invoice', 'payments.receipts']);
    } elseif (Qs::userIsParent()) {
        $actionRoute = route('my_children');
        $actionIcon = 'icon-cash';
        $actionLabel = __('Fees');
        $actionActive = in_array(Route::currentRouteName(), ['my_children', 'payments.invoice', 'payments.receipts']);
    } elseif (Qs::userIsTeacher()) {
        $actionRoute = route('attendance.index');
        $actionIcon = 'icon-calendar2';
        $actionLabel = __('Attendance');
        $actionActive = Route::is('attendance.*');
    } elseif (Qs::userIsLibrarian()) {
        $actionRoute = route('books.index');
        $actionIcon = 'icon-books';
        $actionLabel = __('Library');
        $actionActive = Route::is('books.index');
    }
@endphp

<nav class="sms-mobile-dock d-lg-none no-print" aria-label="{{ __('Mobile Navigation') }}">
    <div class="sms-mobile-dock-inner">
        <a href="{{ route('dashboard') }}" class="sms-dock-item {{ (Route::is('dashboard') || Route::is('home')) ? 'active' : '' }}" @if(Route::is('dashboard') || Route::is('home')) aria-current="page" @endif>
            <i class="icon-home4" aria-hidden="true"></i>
            <span>{{ __('Home') }}</span>
        </a>

        <a href="{{ $academicsRoute }}" class="sms-dock-item {{ $academicsActive ? 'active' : '' }}" @if($academicsActive) aria-current="page" @endif>
            <i class="icon-graduation2" aria-hidden="true"></i>
            <span>{{ $academicsLabel }}</span>
        </a>

        <a href="{{ route('notices.index') }}" class="sms-dock-item {{ Route::is('notices.*') ? 'active' : '' }}" @if(Route::is('notices.*')) aria-current="page" @endif>
            <i class="icon-bell2" aria-hidden="true"></i>
            @if($noticeCount > 0)
                <span class="dock-badge-dot" aria-label="{{ $noticeCount }} {{ __('notices') }}"></span>
            @endif
            <span>{{ __('Notices') }}</span>
        </a>

        <a href="{{ $actionRoute }}" class="sms-dock-item {{ $actionActive ? 'active' : '' }}" @if($actionActive) aria-current="page" @endif>
            <i class="{{ $actionIcon }}" aria-hidden="true"></i>
            <span>{{ $actionLabel }}</span>
        </a>

        <button type="button" class="sms-dock-item sidebar-mobile-main-toggle" aria-label="{{ __('Open Navigation Menu') }}">
            <i class="icon-grid5" aria-hidden="true"></i>
            <span>{{ __('Menu') }}</span>
        </button>
    </div>
</nav>
