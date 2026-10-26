{{-- Librarian Navigation Menu --}}
<li class="nav-item">
    <a href="{{ route('books.index') }}" class="nav-link {{ Route::is('books.index') ? 'active' : '' }}">
        <i class="icon-books"></i> <span>{{ __('Library Dashboard') }}</span>
    </a>
</li>

<li class="nav-item">
    <a href="{{ route('books.circulations') }}" class="nav-link {{ Route::is('books.circulations') ? 'active' : '' }}">
        <i class="icon-sync"></i> <span>{{ __('Issue & Return') }}</span>
    </a>
</li>

<li class="nav-item">
    <a href="{{ route('book_requests.index') }}" class="nav-link {{ Route::is('book_requests.index') ? 'active' : '' }}">
        <i class="icon-list3"></i> <span>{{ __('Book Requests') }}</span>
    </a>
</li>

<li class="nav-item">
    <a href="{{ route('books.categories') }}" class="nav-link {{ Route::is('books.categories') ? 'active' : '' }}">
        <i class="icon-folder-open"></i> <span>{{ __('Book Categories') }}</span>
    </a>
</li>