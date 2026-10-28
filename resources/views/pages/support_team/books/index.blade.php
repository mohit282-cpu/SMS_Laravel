@extends('layouts.master')
@section('page_title', __('Library Dashboard & Inventory'))
@section('content')

    {{-- Stats Cards Row --}}
    <div class="row">
        <div class="col-sm-6 col-xl-2">
            <div class="apple-stat-card mb-3">
                <div>
                    <div class="stat-number">{{ $stats['total_titles'] ?? 0 }}</div>
                    <div class="stat-label">{{ __('Total Titles') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-orange">
                    <i class="icon-books"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="apple-stat-card mb-3">
                <div>
                    <div class="stat-number">{{ $stats['total_copies'] ?? 0 }}</div>
                    <div class="stat-label">{{ __('Total Copies') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-navy">
                    <i class="icon-stack2"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="apple-stat-card mb-3">
                <div>
                    <div class="stat-number">{{ $stats['available_copies'] ?? 0 }}</div>
                    <div class="stat-label">{{ __('Available') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="icon-checkmark4"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="apple-stat-card mb-3">
                <div>
                    <div class="stat-number">{{ $stats['issued_copies'] ?? 0 }}</div>
                    <div class="stat-label">{{ __('Active Loans') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-indigo">
                    <i class="icon-reading"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="apple-stat-card mb-3">
                <div>
                    <div class="stat-number text-danger">{{ $stats['overdue_count'] ?? 0 }}</div>
                    <div class="stat-label">{{ __('Overdue') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-rose">
                    <i class="icon-alarm"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="apple-stat-card mb-3">
                <div>
                    <div class="stat-number">{{ $stats['pending_requests'] ?? 0 }}</div>
                    <div class="stat-label">{{ __('Requests') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-amber">
                    <i class="icon-hour-glass2"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Overdue Alert Notification --}}
    @if(($stats['overdue_count'] ?? 0) > 0)
        <div class="alert alert-danger border-0 alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold"><i class="icon-warning2 mr-2"></i>{{ __('Attention:') }}</span>
            {{ __('There are :count overdue books currently unreturned.', ['count' => $stats['overdue_count']]) }}
            <a href="{{ route('books.circulations') }}#overdue-loans" class="alert-link ml-2 font-weight-bold">{{ __('View Overdue Circulations') }} &rarr;</a>
        </div>
    @endif

    {{-- Main Inventory Card --}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-books mr-2" style="color: #FF5700;"></i>{{ __('Manage Library Books') }}</h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item">
                    <a href="#all-books" class="nav-link active" data-toggle="tab">
                        <i class="icon-list mr-2"></i>{{ __('Book Catalogue') }} ({{ count($books) }})
                    </a>
                </li>
                @if(Qs::userIsTeamLibrary())
                    <li class="nav-item">
                        <a href="#new-book" class="nav-link" data-toggle="tab">
                            <i class="icon-plus2 mr-2"></i>{{ __('Add New Book') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#book-categories" class="nav-link" data-toggle="tab">
                            <i class="icon-folder-open mr-2"></i>{{ __('Categories') }} ({{ count($categories) }})
                        </a>
                    </li>
                @endif
            </ul>

            <div class="tab-content">
                {{-- Tab 1: All Books Catalogue --}}
                <div class="tab-pane fade show active" id="all-books">
                    <table class="table datatable-button-html5-columns table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Book Title') }}</th>
                            <th>{{ __('ISBN') }}</th>
                            <th>{{ __('Author') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Location / Shelf') }}</th>
                            <th>{{ __('Copies (Total / Avail)') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($books as $b)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="font-weight-semibold">
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
                                        <span class="badge badge-light border-left-primary badge-striped">{{ $b->category->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $b->location ?: '-' }}</td>
                                <td>
                                    <span class="font-weight-bold">{{ $b->total_copies }}</span> /
                                    <span class="font-weight-bold {{ $b->available_copies > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $b->available_copies }}
                                    </span>
                                </td>
                                <td>
                                    @if($b->available_copies > 0)
                                        <span class="badge badge-success">{{ __('Available') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('Out of Stock') }}</span>
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
                                                    <i class="icon-eye"></i> {{ __('View Details') }}
                                                </a>
                                                @if(Qs::userIsTeamLibrary())
                                                    <a href="{{ route('books.edit', $b->id) }}" class="dropdown-item">
                                                        <i class="icon-pencil"></i> {{ __('Edit Book') }}
                                                    </a>
                                                    @if($b->available_copies > 0)
                                                        <a href="{{ route('books.circulations') }}?book_id={{ $b->id }}" class="dropdown-item">
                                                            <i class="icon-reading"></i> {{ __('Issue This Book') }}
                                                        </a>
                                                    @endif
                                                    @if($b->issued_copies == 0)
                                                        <a id="{{ $b->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                            <i class="icon-trash"></i> {{ __('Delete Book') }}
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
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tab 2: Add New Book Form --}}
                @if(Qs::userIsTeamLibrary())
                    <div class="tab-pane fade" id="new-book">
                        <form method="post" action="{{ route('books.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Book Title / Name') }} <span class="text-danger">*</span></label>
                                        <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Compulsory English Grade 10') }}">
                                        @error('name')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('ISBN') }}</label>
                                        <input name="isbn" value="{{ old('isbn') }}" type="text" class="form-control" placeholder="{{ __('e.g. 978-9937-12345-6') }}">
                                        @error('isbn')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Category') }}</label>
                                        <select class="form-control select" name="category_id">
                                            <option value="">{{ __('Select Category') }}</option>
                                            @foreach($categories as $cat)
                                                <option {{ old('category_id') == $cat->id ? 'selected' : '' }} value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Author') }}</label>
                                        <input name="author" value="{{ old('author') }}" type="text" class="form-control" placeholder="{{ __('e.g. Laxmi Prasad Devkota') }}">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Publisher') }}</label>
                                        <input name="publisher" value="{{ old('publisher') }}" type="text" class="form-control" placeholder="{{ __('e.g. Sajha Prakashan') }}">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Edition') }}</label>
                                        <input name="edition" value="{{ old('edition') }}" type="text" class="form-control" placeholder="{{ __('e.g. 3rd Edition') }}">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Publication Year') }}</label>
                                        <input name="publication_year" value="{{ old('publication_year') }}" type="text" class="form-control" placeholder="{{ __('e.g. 2024') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Class / Grade (Optional)') }}</label>
                                        <select class="form-control select" name="my_class_id">
                                            <option value="">{{ __('General / All Classes') }}</option>
                                            @foreach($my_classes as $c)
                                                <option {{ old('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Shelf / Rack Location') }}</label>
                                        <input name="location" value="{{ old('location') }}" type="text" class="form-control" placeholder="{{ __('e.g. Shelf A-3, Row 2') }}">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Total Copies') }} <span class="text-danger">*</span></label>
                                        <input name="total_copies" value="{{ old('total_copies', 1) }}" min="1" required type="number" class="form-control">
                                        @error('total_copies')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Language') }}</label>
                                        <input name="language" value="{{ old('language', 'Nepali / English') }}" type="text" class="form-control" placeholder="{{ __('e.g. Nepali, English') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Description / Synopsis') }}</label>
                                        <textarea name="description" rows="3" class="form-control" placeholder="{{ __('Brief summary of the book content...') }}">{{ old('description') }}</textarea>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-semibold">{{ __('Book Cover Image') }}</label>
                                        <input name="cover_image" type="file" class="form-input-styled" data-fouc>
                                        <span class="form-text text-muted">{{ __('Accepted formats: jpg, png, jpeg. Max: 2MB') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary font-weight-bold">
                                    <i class="icon-paperplane mr-2"></i>{{ __('Save Book to Catalogue') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Tab 3: Book Categories --}}
                    <div class="tab-pane fade" id="book-categories">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Category Name') }}</th>
                                            <th>{{ __('Slug') }}</th>
                                            <th>{{ __('Books Count') }}</th>
                                            <th class="text-center">{{ __('Action') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($categories as $cat)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="font-weight-semibold">{{ $cat->name }}</td>
                                                <td><code>{{ $cat->slug }}</code></td>
                                                <td>
                                                    <span class="badge badge-primary">{{ $cat->books_count ?? count($cat->books ?? []) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if(($cat->books_count ?? count($cat->books ?? [])) == 0)
                                                        <a id="cat-{{ $cat->id }}" onclick="confirmDelete(this.id)" href="#" class="btn btn-sm btn-outline-danger">
                                                            <i class="icon-trash"></i>
                                                        </a>
                                                        <form method="post" id="item-delete-cat-{{ $cat->id }}" action="{{ route('books.categories.destroy', $cat->id) }}" class="hidden">
                                                            @csrf @method('delete')
                                                        </form>
                                                    @else
                                                        <span class="text-muted font-size-xs" title="{{ __('Has associated books') }}"><i class="icon-lock2"></i> In use</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="card card-body bg-light border-primary">
                                    <h6 class="font-weight-semibold text-primary"><i class="icon-plus-circle2 mr-2"></i>{{ __('Add New Category') }}</h6>
                                    <form method="post" action="{{ route('books.categories.store') }}">
                                        @csrf
                                        <div class="form-group">
                                            <label class="font-weight-semibold">{{ __('Category Name') }} <span class="text-danger">*</span></label>
                                            <input name="name" required type="text" class="form-control" placeholder="{{ __('e.g. Science & Technology') }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-semibold">{{ __('Description') }}</label>
                                            <textarea name="description" rows="2" class="form-control" placeholder="{{ __('Optional category notes...') }}"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="icon-check mr-2"></i>{{ __('Create Category') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
