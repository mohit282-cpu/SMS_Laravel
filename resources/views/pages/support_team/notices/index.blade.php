@extends('layouts.master')
@section('page_title', __('Noticeboard'))
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-bold"><i class="icon-newspaper mr-2"></i>{{ __('School Noticeboard') }}</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        @if(Qs::userIsTeamSA())
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#all-notices" class="nav-link active" data-toggle="tab"><i class="icon-list mr-1"></i> {{ __('All Notices') }}</a></li>
                <li class="nav-item"><a href="#new-notice" class="nav-link" data-toggle="tab"><i class="icon-plus2 mr-1"></i> {{ __('Create Notice') }}</a></li>
            </ul>
        @endif

        <div class="tab-content">
            <div class="tab-pane fade show active" id="all-notices">
                <div class="row">
                    @forelse($notices as $notice)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border-left-3 {{ $notice->is_published ? 'border-left-primary' : 'border-left-danger' }} shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title font-weight-semibold mb-0">
                                            <a href="{{ route('notices.show', $notice->id) }}" class="text-default">{{ $notice->title }}</a>
                                        </h5>
                                        @if($notice->is_published)
                                            <span class="badge badge-success">{{ __('Published') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Draft') }}</span>
                                        @endif
                                    </div>
                                    <div class="text-muted font-size-xs mb-2">
                                        <i class="icon-calendar mr-1"></i>
                                        {{ $notice->notice_date_bs ? $notice->notice_date_bs . ' BS' : $notice->notice_date->format('Y-m-d') }}
                                        @if($notice->expiry_date)
                                            - {{ $notice->expiry_date_bs ? $notice->expiry_date_bs . ' BS' : $notice->expiry_date->format('Y-m-d') }}
                                        @endif
                                    </div>
                                    <p class="card-text text-muted mb-3 flex-grow-1">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($notice->content), 120) }}
                                    </p>
                                    <div class="font-size-xs text-muted mb-2">
                                        <span class="badge badge-light mr-1"><i class="icon-eye mr-1"></i>{{ ucfirst($notice->role_visibility ?? 'All') }}</span>
                                        @if($notice->my_class)
                                            <span class="badge badge-info">{{ $notice->my_class->name }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <a href="{{ route('notices.show', $notice->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="icon-eye mr-1"></i>{{ __('Read More') }}
                                        </a>
                                        @if(Qs::userIsTeamSA())
                                            <div class="list-icons">
                                                <div class="dropdown">
                                                    <a href="#" class="list-icons-item" data-toggle="dropdown"><i class="icon-menu7"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a href="{{ route('notices.edit', $notice->id) }}" class="dropdown-item"><i class="icon-pencil"></i> {{ __('Edit') }}</a>
                                                        @if(Qs::userIsSuperAdmin())
                                                            <a id="{{ $notice->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger"><i class="icon-trash"></i> {{ __('Delete') }}</a>
                                                            <form method="post" id="item-delete-{{ $notice->id }}" action="{{ route('notices.destroy', $notice->id) }}" class="hidden">@csrf @method('delete')</form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info border-0 alert-dismissible text-center">
                                <i class="icon-info22 mr-2"></i>{{ __('No notices found at this time.') }}
                            </div>
                        </div>
                    @endforelse
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $notices->links() }}
                </div>
            </div>

            @if(Qs::userIsTeamSA())
                <div class="tab-pane fade" id="new-notice">
                    <form method="post" action="{{ route('notices.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Notice Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="{{ __('Enter notice title') }}">
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Content') }} <span class="text-danger">*</span></label>
                                    <textarea name="content" rows="8" class="form-control" required placeholder="{{ __('Enter notice description/content') }}">{{ old('content') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Notice Date (BS)') }}</label>
                                    <input type="text" name="notice_date_bs" class="form-control nepali-datepicker" value="{{ old('notice_date_bs') }}" placeholder="YYYY-MM-DD">
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Expiry Date (BS)') }}</label>
                                    <input type="text" name="expiry_date_bs" class="form-control nepali-datepicker" value="{{ old('expiry_date_bs') }}" placeholder="YYYY-MM-DD">
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Target Role Visibility') }}</label>
                                    <select name="role_visibility" class="form-control select">
                                        <option value="all">{{ __('All Users') }}</option>
                                        <option value="teacher">{{ __('Teachers Only') }}</option>
                                        <option value="student">{{ __('Students Only') }}</option>
                                        <option value="parent">{{ __('Parents Only') }}</option>
                                        <option value="accountant">{{ __('Accountants Only') }}</option>
                                        <option value="librarian">{{ __('Librarians Only') }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-semibold">{{ __('Specific Class (Optional)') }}</label>
                                    <select name="my_class_id" class="form-control select">
                                        <option value="">{{ __('All Classes') }}</option>
                                        @foreach($my_classes as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="form-check form-check-switchery">
                                        <label class="form-check-label font-weight-semibold">
                                            <input type="checkbox" name="is_published" value="1" class="form-check-input-switchery" checked>
                                            {{ __('Publish Immediately') }}
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block"><i class="icon-paperplane mr-2"></i>{{ __('Post Notice') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
