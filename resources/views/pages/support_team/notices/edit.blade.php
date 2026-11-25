@extends('layouts.master')
@section('page_title', __('Edit Notice'))
@section('content')

    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.1rem;">
                <i class="icon-pencil mr-2" style="color: #FF5700;"></i>{{ __('Edit School Notice') }} &bull; {{ $notice->title }}
            </h5>
            <a href="{{ route('notices.index') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold" style="border-radius: 8px;">
                <i class="icon-arrow-left52 mr-1"></i>{{ __('Back to Notices') }}
            </a>
        </div>

        <div class="card-body p-3 p-md-4">
            <form method="post" action="{{ route('notices.update', $notice->id) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Notice Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required value="{{ old('title', $notice->title) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-semibold text-dark">{{ __('Content') }} <span class="text-danger">*</span></label>
                            <textarea name="content" rows="10" class="form-control" required>{{ old('content', $notice->content) }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border">
                            <div class="form-group mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Notice Date (BS)') }}</label>
                                <input type="text" name="notice_date_bs" class="form-control nepali-date" value="{{ old('notice_date_bs', $notice->notice_date_bs) }}" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Expiry Date (BS)') }}</label>
                                <input type="text" name="expiry_date_bs" class="form-control nepali-date" value="{{ old('expiry_date_bs', $notice->expiry_date_bs) }}" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Target Role Visibility') }}</label>
                                <select name="role_visibility" class="form-control select">
                                    <option {{ $notice->role_visibility == 'all' ? 'selected' : '' }} value="all">{{ __('All Users') }}</option>
                                    <option {{ $notice->role_visibility == 'teacher' ? 'selected' : '' }} value="teacher">{{ __('Teachers Only') }}</option>
                                    <option {{ $notice->role_visibility == 'student' ? 'selected' : '' }} value="student">{{ __('Students Only') }}</option>
                                    <option {{ $notice->role_visibility == 'parent' ? 'selected' : '' }} value="parent">{{ __('Parents Only') }}</option>
                                    <option {{ $notice->role_visibility == 'accountant' ? 'selected' : '' }} value="accountant">{{ __('Accountants Only') }}</option>
                                    <option {{ $notice->role_visibility == 'librarian' ? 'selected' : '' }} value="librarian">{{ __('Librarians Only') }}</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-semibold text-dark">{{ __('Specific Class (Optional)') }}</label>
                                <select name="my_class_id" class="form-control select">
                                    <option value="">{{ __('All Classes') }}</option>
                                    @foreach($my_classes as $c)
                                        <option {{ $notice->my_class_id == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="is_published" value="1" class="custom-control-input" id="edit_notice_is_published" {{ $notice->is_published ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-semibold text-success" for="edit_notice_is_published">{{ __('Published') }}</label>
                                </div>
                            </div>
                            <button type="submit" class="btn text-white btn-block font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-sync mr-2"></i>{{ __('Update Notice') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
