@extends('layouts.master')
@section('page_title', __('Manage Book Categories'))
@section('content')

    <div class="row">
        {{-- Categories Table Column --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title font-weight-semibold"><i class="icon-folder-open mr-2"></i>{{ __('Book Categories') }}</h6>
                    {!! Qs::getPanelOptions() !!}
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Category Name') }}</th>
                                <th>{{ __('Slug') }}</th>
                                <th>{{ __('Books Count') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($categories as $cat)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="font-weight-semibold text-primary">{{ $cat->name }}</td>
                                    <td><code>{{ $cat->slug }}</code></td>
                                    <td>
                                        <span class="badge badge-primary font-weight-bold">
                                            {{ $cat->books_count ?? count($cat->books ?? []) }}
                                        </span>
                                    </td>
                                    <td>{{ $cat->description ?: '-' }}</td>
                                    <td class="text-center">
                                        @if(($cat->books_count ?? count($cat->books ?? [])) == 0)
                                            <a id="del-cat-{{ $cat->id }}" onclick="confirmDelete(this.id)" href="#" class="btn btn-sm btn-outline-danger">
                                                <i class="icon-trash"></i>
                                            </a>
                                            <form method="post" id="item-delete-del-cat-{{ $cat->id }}" action="{{ route('books.categories.destroy', $cat->id) }}" class="hidden">
                                                @csrf @method('delete')
                                            </form>
                                        @else
                                            <span class="badge badge-light text-muted" title="{{ __('Protected: Has books assigned') }}">
                                                <i class="icon-lock2"></i> {{ __('In use') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Category Form Column --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header header-elements-inline bg-light">
                    <h6 class="card-title font-weight-semibold text-primary"><i class="icon-plus-circle2 mr-2"></i>{{ __('Add New Category') }}</h6>
                </div>

                <div class="card-body">
                    <form method="post" action="{{ route('books.categories.store') }}">
                        @csrf
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Category Name') }} <span class="text-danger">*</span></label>
                            <input name="name" value="{{ old('name') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Science & Technology') }}">
                            @error('name')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Description (Optional)') }}</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="{{ __('Optional notes or classification details...') }}">{{ old('description') }}</textarea>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary font-weight-bold">
                                <i class="icon-check mr-2"></i>{{ __('Save Category') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
