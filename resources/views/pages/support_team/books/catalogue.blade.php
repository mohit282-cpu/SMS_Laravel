@extends('layouts.master')
@section('page_title', __('Library Catalogue'))
@section('content')

    {{-- Search and Filter Filter Card --}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-semibold"><i class="icon-search4 mr-2"></i>{{ __('Search Library Catalogue') }}</h6>
            <div class="header-elements">
                <a href="{{ route('book_requests.my_requests') }}" class="btn btn-outline-primary btn-sm font-weight-bold">
                    <i class="icon-books mr-1"></i> {{ __('My Borrowed & Requested Books') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="get" action="{{ route('books.catalogue') }}">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Keyword / Title / Author / ISBN') }}</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by title, author name, or ISBN...') }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Category') }}</label>
                            <select name="category_id" class="form-control select">
                                <option value="">{{ __('All Categories') }}</option>
                                @foreach($categories as $cat)
                                    <option {{ request('category_id') == $cat->id ? 'selected' : '' }} value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Grade / Class') }}</label>
                            <select name="my_class_id" class="form-control select">
                                <option value="">{{ __('All Classes') }}</option>
                                @foreach($my_classes as $c)
                                    <option {{ request('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                                <i class="icon-filter3 mr-1"></i> {{ __('Filter') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Books Grid / List --}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-semibold">{{ __('Books in Library') }}</h6>
            <span class="badge badge-light badge-striped font-size-sm">{{ count($books) }} {{ __('Titles Found') }}</span>
        </div>

        <div class="card-body">
            @if(count($books) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Book Title') }}</th>
                            <th>{{ __('Author') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Shelf Location') }}</th>
                            <th>{{ __('Availability') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($books as $b)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('books.show', $b->id) }}" class="font-weight-semibold">
                                        {{ $b->name }}
                                    </a>
                                    @if($b->isbn)
                                        <div class="text-muted font-size-xs">ISBN: {{ $b->isbn }}</div>
                                    @endif
                                </td>
                                <td>{{ $b->author ?: '-' }}</td>
                                <td>
                                    @if($b->category)
                                        <span class="badge badge-light border-left-primary badge-striped">{{ $b->category->name }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $b->my_class ? $b->my_class->name : __('General') }}</td>
                                <td><span class="badge badge-info">{{ $b->location ?: '-' }}</span></td>
                                <td>
                                    @if($b->available_copies > 0)
                                        <span class="badge badge-success font-weight-semibold">
                                            {{ $b->available_copies }} {{ __('Available') }}
                                        </span>
                                    @else
                                        <span class="badge badge-danger">{{ __('Out of Stock') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('books.show', $b->id) }}" class="btn btn-sm btn-outline-primary mr-1">
                                            <i class="icon-eye"></i>
                                        </a>

                                        @if($b->available_copies > 0)
                                            <form method="post" action="{{ route('book_requests.store') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="book_id" value="{{ $b->id }}">
                                                <button type="submit" class="btn btn-sm btn-success font-weight-bold" onclick="return confirm('{{ __('Request to borrow: :title?', ['title' => $b->name]) }}')">
                                                    <i class="icon-paperplane mr-1"></i> {{ __('Request') }}
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-light text-muted" disabled>
                                                {{ __('Unavailable') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted py-5">
                    <i class="icon-books icon-3x mb-3 text-muted"></i>
                    <h5>{{ __('No Books Found') }}</h5>
                    <p class="text-muted">{{ __('Try adjusting your search criteria or clearing filters.') }}</p>
                </div>
            @endif
        </div>
    </div>

@endsection
