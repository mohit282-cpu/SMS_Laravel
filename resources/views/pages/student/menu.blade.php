{{--Marksheet--}}
<li class="nav-item">
    <a href="{{ route('marks.year_selector', Qs::hash(Auth::user()->id)) }}" class="nav-link {{ in_array(Route::currentRouteName(), ['marks.show', 'marks.year_selector', 'pins.enter']) ? 'active' : '' }}"><i class="icon-book"></i> <span>{{ __('Marksheet') }}</span></a>
</li>

{{--Class Timetable--}}
<li class="nav-item">
    <a href="{{ route('tt.my_timetable') }}" class="nav-link {{ Route::is('tt.my_timetable') ? 'active' : '' }}"><i class="icon-calendar"></i> <span>{{ __('Class Timetable') }}</span></a>
</li>

@php
    $studentRec = App\Models\StudentRecord::where('user_id', Auth::user()->id)->first();
@endphp
@if($studentRec)
<li class="nav-item">
    <a href="{{ route('attendance.student_history', $studentRec->id) }}" class="nav-link {{ Route::is('attendance.student_history') ? 'active' : '' }}"><i class="icon-user-check"></i> <span>{{ __('My Attendance') }}</span></a>
</li>
@endif

{{--Fees & Invoices--}}
<li class="nav-item">
    <a href="{{ route('payments.invoice', [Qs::hash(Auth::user()->id), Qs::getCurrentSession()]) }}" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.invoice', 'payments.receipts']) ? 'active' : '' }}"><i class="icon-cash"></i> <span>{{ __('Fees & Payments') }}</span></a>
</li>

{{--Library--}}
<li class="nav-item">
    <a href="{{ route('books.index') }}" class="nav-link {{ Route::is('books.index') ? 'active' : '' }}"><i class="icon-books"></i> <span>{{ __('Library Catalogue') }}</span></a>
</li>
<li class="nav-item">
    <a href="{{ route('book_requests.index') }}" class="nav-link {{ Route::is('book_requests.index') ? 'active' : '' }}"><i class="icon-list3"></i> <span>{{ __('My Book Requests') }}</span></a>
</li>
