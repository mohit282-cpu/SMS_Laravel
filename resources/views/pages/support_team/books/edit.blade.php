@extends('layouts.master')
@section('page_title', __('Edit Book: :name', ['name' => $book->name]))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-semibold"><i class="icon-pencil mr-2"></i>{{ __('Edit Book Information') }}</h6>
            <div class="header-elements">
                <a href="{{ route('books.index') }}" class="btn btn-sm btn-outline-dark">
                    <i class="icon-arrow-left7 mr-1"></i> {{ __('Back to Inventory') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('books.update', $book->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Book Title / Name') }} <span class="text-danger">*</span></label>
                            <input name="name" value="{{ old('name', $book->name) }}" required type="text" class="form-control">
                            @error('name')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('ISBN') }}</label>
                            <input name="isbn" value="{{ old('isbn', $book->isbn) }}" type="text" class="form-control">
                            @error('isbn')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Category') }}</label>
                            <select class="form-control select" name="category_id">
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach($categories as $cat)
                                    <option {{ old('category_id', $book->category_id) == $cat->id ? 'selected' : '' }} value="{{ $cat->id }}">{{ $cat->name }}</option>
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
                            <input name="author" value="{{ old('author', $book->author) }}" type="text" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Publisher') }}</label>
                            <input name="publisher" value="{{ old('publisher', $book->publisher) }}" type="text" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Edition') }}</label>
                            <input name="edition" value="{{ old('edition', $book->edition) }}" type="text" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Publication Year') }}</label>
                            <input name="publication_year" value="{{ old('publication_year', $book->publication_year) }}" type="text" class="form-control">
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
                                    <option {{ old('my_class_id', $book->my_class_id) == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Shelf / Rack Location') }}</label>
                            <input name="location" value="{{ old('location', $book->location) }}" type="text" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Total Copies') }} <span class="text-danger">*</span></label>
                            <input name="total_copies" value="{{ old('total_copies', $book->total_copies) }}" min="{{ $book->issued_copies }}" required type="number" class="form-control">
                            <span class="form-text text-muted font-size-xs">{{ __('Cannot be less than issued copies (:count)', ['count' => $book->issued_copies]) }}</span>
                            @error('total_copies')<span class="text-danger font-size-xs">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Language') }}</label>
                            <input name="language" value="{{ old('language', $book->language) }}" type="text" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Description / Synopsis') }}</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $book->description) }}</textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Book Cover Image') }}</label>
                            @if($book->cover_image)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $book->cover_image) }}" height="60" class="rounded border">
                                </div>
                            @endif
                            <input name="cover_image" type="file" class="form-input-styled" data-fouc>
                            <span class="form-text text-muted">{{ __('Accepted: jpg, png, jpeg. Max: 2MB') }}</span>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <a href="{{ route('books.index') }}" class="btn btn-light mr-2">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="icon-check mr-2"></i>{{ __('Update Book') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
