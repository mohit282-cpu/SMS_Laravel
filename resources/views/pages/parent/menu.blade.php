{{--My Children--}}
<li class="nav-item">
    <a href="{{ route('my_children') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['my_children']) ? 'active' : '' }}"><i class="icon-users4"></i> <span>{{ __('My Children') }}</span></a>
</li>

{{--Children Timetables--}}
<li class="nav-item">
    <a href="{{ route('tt.my_timetable') }}" class="nav-link {{ Route::is('tt.my_timetable') ? 'active' : '' }}"><i class="icon-calendar"></i> <span>{{ __('Children Timetables') }}</span></a>
</li>

{{-- Library Catalogue --}}
<li class="nav-item">
    <a href="{{ route('books.catalogue') }}" class="nav-link {{ Route::is('books.catalogue') ? 'active' : '' }}"><i class="icon-books"></i> <span>{{ __('Library Catalogue') }}</span></a>
</li>