@extends('layouts.master')
@section('page_title', __('Library Dashboard & Inventory'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-books icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Library Dashboard & Inventory Management') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Book catalogue, stock allocation, circulation records & borrower reservations') }}
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
                    <span class="badge badge-light border px-3 py-2 text-dark font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-calendar22 mr-1 text-primary"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                    </span>
                    <span class="badge badge-primary px-3 py-2 font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-graduation mr-1"></i> {{ __('Session') }}: {{ Qs::getSetting('current_session') }}
                    </span>
                    @if(Qs::userIsTeamLibrary())
                        <a href="{{ route('books.circulations') }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                            <i class="icon-sync mr-1"></i>{{ __('Circulations') }}
                        </a>
                        <button type="button" class="btn text-white btn-sm font-weight-semibold shadow-sm" onclick="$('a[href=\'#new-book\']').tab('show');" style="background: #FF5700; border-radius: 8px;">
                            <i class="icon-plus2 mr-1"></i>{{ __('Add New Book') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-2 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Titles') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($stats['total_titles'] ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Distinct books') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-books text-indigo" style="font-size: 1.3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #111827 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Copies') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($stats['total_copies'] ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Inventory volumes') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(17, 24, 39, 0.08);">
                            <i class="icon-stack2 text-dark" style="font-size: 1.3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Available') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($stats['available_copies'] ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('On shelves') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-checkmark4 text-success" style="font-size: 1.3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #06b6d4 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Active Loans') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($stats['issued_copies'] ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Currently issued') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-reading text-info" style="font-size: 1.3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Overdue') }}</span>
                            <h3 class="font-weight-bold text-danger mb-0 mt-1">{{ number_format($stats['overdue_count'] ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Late returns') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(239, 68, 68, 0.1);">
                            <i class="icon-alarm text-danger" style="font-size: 1.3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Requests') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($stats['pending_requests'] ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Pending borrow') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-hour-glass2 text-warning" style="font-size: 1.3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Overdue Alert Notification --}}
    @if(($stats['overdue_count'] ?? 0) > 0)
        <div class="alert alert-danger border-0 alert-dismissible shadow-sm mb-4" style="border-radius: 12px;">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <div class="d-flex align-items-center">
                <i class="icon-warning2 mr-2 icon-2x"></i>
                <div>
                    <span class="font-weight-bold font-size-sm">{{ __('Attention: Overdue Library Loans Detected') }}</span>
                    <div class="font-size-xs mt-1">
                        {{ __('There are :count overdue books currently unreturned by students or staff.', ['count' => $stats['overdue_count']]) }}
                        <a href="{{ route('books.circulations') }}#overdue-loans" class="alert-link font-weight-bold ml-1">{{ __('Manage Overdue Loans') }} &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Inventory Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-books mr-2" style="color: #FF5700;"></i>{{ __('Manage Library Books') }}
            </h5>
            <div class="d-flex align-items-center">
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item">
                    <a href="#all-books" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-list mr-1"></i> {{ __('Book Catalogue') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ count($books) }}</span>
                    </a>
                </li>
                @if(Qs::userIsTeamLibrary())
                    <li class="nav-item">
                        <a href="#new-book" class="nav-link font-weight-semibold" data-toggle="tab">
                            <i class="icon-plus2 mr-1"></i> {{ __('Add New Book') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#book-categories" class="nav-link font-weight-semibold" data-toggle="tab">
                            <i class="icon-folder-open mr-1"></i> {{ __('Categories') }}
                            <span class="badge badge-secondary ml-1 font-size-xs">{{ count($categories) }}</span>
                        </a>
                    </li>
                @endif
            </ul>

            <div class="tab-content">
                {{-- Tab 1: All Books Catalogue --}}
                <div class="tab-pane fade show active" id="all-books">
                    {{-- Search & Filter Bar --}}
                    <form method="GET" action="{{ route('books.index') }}" class="mb-4 p-3 bg-light rounded border">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-2">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Search Title / Author / ISBN') }}</label>
                                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="{{ __('Type book name, author or ISBN...') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Category') }}</label>
                                <select name="category_id" class="form-control form-control-sm">
                                    <option value="">{{ __('All Categories') }}</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ $category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Class Specification') }}</label>
                                <select name="my_class_id" class="form-control form-control-sm">
                                    <option value="">{{ __('All Classes / General') }}</option>
                                    @foreach($my_classes as $c)
                                        <option value="{{ $c->id }}" {{ $my_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2 d-flex">
                                <button type="submit" class="btn btn-primary btn-sm btn-block shadow-sm mr-1">
                                    <i class="icon-search4 mr-1"></i>{{ __('Filter') }}
                                </button>
                                <a href="{{ route('books.index') }}" class="btn btn-light btn-sm" title="{{ __('Reset') }}">
                                    <i class="icon-reset"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Book Title') }}</th>
                                <th>{{ __('ISBN') }}</th>
                                <th>{{ __('Author') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Location / Shelf') }}</th>
                                <th>{{ __('Copies (Total / Avail)') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-center" style="width: 100px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($books as $b)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">
                                            <a href="{{ route('books.show', $b->id) }}">{{ $b->name }}</a>
                                        </div>
                                        @if($b->publisher)
                                            <div class="text-muted font-size-xs">{{ $b->publisher }} @if($b->publication_year) ({{ $b->publication_year }}) @endif</div>
                                        @endif
                                    </td>
                                    <td><code>{{ $b->isbn ?: '-' }}</code></td>
                                    <td>{{ $b->author ?: '-' }}</td>
                                    <td>
                                        @if($b->category)
                                            <span class="badge badge-light border">{{ $b->category->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $b->location ?: '-' }}</td>
                                    <td>
                                        <span class="font-weight-bold text-dark">{{ $b->total_copies }}</span> /
                                        <span class="font-weight-bold {{ $b->available_copies > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $b->available_copies }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($b->available_copies > 0)
                                            <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;">{{ __('Available') }}</span>
                                        @else
                                            <span class="badge badge-danger" style="border-radius: 6px; padding: 4px 8px;">{{ __('Out of Stock') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-menu9"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a href="{{ route('books.show', $b->id) }}" class="dropdown-item">
                                                        <i class="icon-eye mr-2"></i> {{ __('View Details') }}
                                                    </a>
                                                    @if(Qs::userIsTeamLibrary())
                                                        <a href="{{ route('books.edit', $b->id) }}" class="dropdown-item">
                                                            <i class="icon-pencil mr-2"></i> {{ __('Edit Book') }}
                                                        </a>
                                                        @if($b->available_copies > 0)
                                                            <a href="{{ route('books.circulations') }}?book_id={{ $b->id }}" class="dropdown-item">
                                                                <i class="icon-reading mr-2"></i> {{ __('Issue This Book') }}
                                                            </a>
                                                        @endif
                                                        @if($b->issued_copies == 0)
                                                            <div class="dropdown-divider"></div>
                                                            <a id="{{ $b->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                                <i class="icon-trash mr-2"></i> {{ __('Delete Book') }}
                                                            </a>
                                                            <form method="post" id="item-delete-{{ $b->id }}" action="{{ route('books.destroy', $b->id) }}" class="hidden">
                                                                @csrf @method('delete')
                                                            </form>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="icon-books icon-2x mb-2 d-block text-muted"></i>
                                        <span class="font-weight-medium">{{ __('No library books found matching the search criteria.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab 2: Add New Book Form --}}
                @if(Qs::userIsTeamLibrary())
                    <div class="tab-pane fade" id="new-book">
                        <form method="post" action="{{ route('books.store') }}" enctype="multipart/form-data" class="p-2">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Book Title / Name') }} <span class="text-danger">*</span></label>
                                    <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Compulsory English Grade 10') }}">
                                    @error('name')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('ISBN') }}</label>
                                    <input name="isbn" value="{{ old('isbn') }}" type="text" class="form-control" placeholder="{{ __('e.g. 978-9937-12345-6') }}">
                                    @error('isbn')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Category') }}</label>
                                    <select class="form-control select" name="category_id">
                                        <option value="">{{ __('Select Category') }}</option>
                                        @foreach($categories as $cat)
                                            <option {{ old('category_id') == $cat->id ? 'selected' : '' }} value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Author') }}</label>
                                    <input name="author" value="{{ old('author') }}" type="text" class="form-control" placeholder="{{ __('e.g. Laxmi Prasad Devkota') }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Publisher') }}</label>
                                    <input name="publisher" value="{{ old('publisher') }}" type="text" class="form-control" placeholder="{{ __('e.g. Sajha Prakashan') }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Class Assignment') }}</label>
                                    <select class="form-control select" name="my_class_id">
                                        <option value="">{{ __('General (All Classes)') }}</option>
                                        @foreach($my_classes as $c)
                                            <option {{ old('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Total Copies') }} <span class="text-danger">*</span></label>
                                    <input name="total_copies" value="{{ old('total_copies', 1) }}" required type="number" min="1" max="1000" class="form-control">
                                    @error('total_copies')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Shelf / Rack Location') }}</label>
                                    <input name="location" value="{{ old('location') }}" type="text" class="form-control" placeholder="{{ __('e.g. Shelf A-3, Rack 2') }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Publication Year') }}</label>
                                    <input name="publication_year" value="{{ old('publication_year') }}" type="number" min="1900" max="2100" class="form-control" placeholder="{{ __('e.g. 2024') }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Cover Image') }}</label>
                                    <input name="cover_image" type="file" class="form-control-file font-size-xs" accept="image/*">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="font-weight-semibold text-dark">{{ __('Description / Synopsis') }}</label>
                                    <textarea name="description" rows="3" class="form-control" placeholder="{{ __('Brief overview of the book, topics covered, edition...') }}">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <div class="text-right border-top pt-3">
                                <button type="submit" class="btn text-white px-4 font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                    <i class="icon-checkmark mr-1"></i> {{ __('Register Book') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Tab 3: Categories Overview --}}
                    <div class="tab-pane fade" id="book-categories">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-dark mb-0">{{ __('Book Categories Overview') }}</h6>
                            <a href="{{ route('books.categories') }}" class="btn btn-outline-primary btn-sm font-weight-semibold" style="border-radius: 8px;">
                                <i class="icon-folder-plus mr-1"></i> {{ __('Manage Full Categories') }}
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Category Name') }}</th>
                                    <th>{{ __('Slug') }}</th>
                                    <th>{{ __('Assigned Books') }}</th>
                                    <th>{{ __('Description') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($categories as $cat)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="font-weight-semibold text-dark">{{ $cat->name }}</td>
                                        <td><code>{{ $cat->slug }}</code></td>
                                        <td>
                                            <span class="badge badge-primary font-weight-bold">
                                                {{ $cat->books_count ?? count($cat->books ?? []) }} {{ __('Books') }}
                                            </span>
                                        </td>
                                        <td class="text-muted font-size-xs">{{ $cat->description ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">{{ __('No book categories created yet.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
