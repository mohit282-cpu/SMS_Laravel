@extends('layouts.master')
@section('page_title', __('Noticeboard'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-newspaper icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('School Noticeboard') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Official announcements, circulars, target-audience notices & Bikram Sambat schedules') }}
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
                    @if(Qs::userIsTeamSA())
                        <button type="button" class="btn text-white btn-sm font-weight-semibold shadow-sm ml-md-2" onclick="$('#tab-new-notice-link').tab('show');" style="background: #FF5700; border-radius: 8px;">
                            <i class="icon-plus2 mr-1"></i>{{ __('Create Notice') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Notices') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($total_notices ?? $notices->total()) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Visible circulars') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-newspaper icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Published') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($published_count ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Live on noticeboard') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-checkmark4 icon-2x" style="color: #10b981;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #06b6d4 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Active Notices') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($active_count ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Current & non-expired') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(6, 182, 212, 0.1);">
                            <i class="icon-megaphone icon-2x" style="color: #06b6d4;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Drafts') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($draft_count ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Unpublished items') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-pencil icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Noticeboard Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-newspaper mr-2" style="color: #FF5700;"></i>{{ __('Announcements & Circulars') }}
            </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-3 p-md-4">
            @if(Qs::userIsTeamSA())
                <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                    <li class="nav-item">
                        <a href="#all-notices" class="nav-link active font-weight-semibold" data-toggle="tab">
                            <i class="icon-list mr-1"></i> {{ __('All Notices') }}
                            <span class="badge badge-secondary ml-1 font-size-xs">{{ $notices->total() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#new-notice" id="tab-new-notice-link" class="nav-link font-weight-semibold" data-toggle="tab">
                            <i class="icon-plus2 mr-1"></i> {{ __('Create Notice') }}
                        </a>
                    </li>
                </ul>
            @endif

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-notices">
                    {{-- Filter Bar --}}
                    <form method="GET" action="{{ route('notices.index') }}" class="mb-4 p-3 bg-light rounded border">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Search Title or Content') }}</label>
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('Search notices...') }}">
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Target Audience') }}</label>
                                <select name="role_visibility" class="form-control form-control-sm">
                                    <option value="">{{ __('All Audiences') }}</option>
                                    <option value="all" {{ request('role_visibility') === 'all' ? 'selected' : '' }}>{{ __('All Users') }}</option>
                                    <option value="teacher" {{ request('role_visibility') === 'teacher' ? 'selected' : '' }}>{{ __('Teachers Only') }}</option>
                                    <option value="student" {{ request('role_visibility') === 'student' ? 'selected' : '' }}>{{ __('Students Only') }}</option>
                                    <option value="parent" {{ request('role_visibility') === 'parent' ? 'selected' : '' }}>{{ __('Parents Only') }}</option>
                                    <option value="accountant" {{ request('role_visibility') === 'accountant' ? 'selected' : '' }}>{{ __('Accountants Only') }}</option>
                                    <option value="librarian" {{ request('role_visibility') === 'librarian' ? 'selected' : '' }}>{{ __('Librarians Only') }}</option>
                                </select>
                            </div>
                            @if(Qs::userIsTeamSA())
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Status') }}</label>
                                    <select name="is_published" class="form-control form-control-sm">
                                        <option value="">{{ __('All Statuses') }}</option>
                                        <option value="1" {{ request('is_published') === '1' ? 'selected' : '' }}>{{ __('Published') }}</option>
                                        <option value="0" {{ request('is_published') === '0' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-2 d-flex">
                                <button type="submit" class="btn btn-primary btn-sm btn-block shadow-sm mr-1">
                                    <i class="icon-search4 mr-1"></i>{{ __('Filter') }}
                                </button>
                                <a href="{{ route('notices.index') }}" class="btn btn-light btn-sm" title="{{ __('Reset') }}">
                                    <i class="icon-reset"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Notices Grid --}}
                    <div class="row">
                        @forelse($notices as $notice)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid {{ $notice->is_published ? '#4f46e5' : '#ef4444' }} !important;">
                                    <div class="card-body d-flex flex-column p-3 p-md-4">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title font-weight-bold mb-0 pr-2" style="font-size: 1.05rem;">
                                                <a href="{{ route('notices.show', $notice->id) }}" class="text-dark">{{ $notice->title }}</a>
                                            </h5>
                                            @if($notice->is_published)
                                                <span class="badge badge-success flex-shrink-0" style="border-radius: 6px; padding: 4px 8px;">{{ __('Published') }}</span>
                                            @else
                                                <span class="badge badge-secondary flex-shrink-0" style="border-radius: 6px; padding: 4px 8px;">{{ __('Draft') }}</span>
                                            @endif
                                        </div>

                                        <div class="text-muted font-size-xs mb-3">
                                            <i class="icon-calendar22 mr-1 text-primary"></i>
                                            {{ $notice->notice_date_bs ? $notice->notice_date_bs . ' BS' : ($notice->notice_date ? $notice->notice_date->format('Y-m-d') : '-') }}
                                            @if($notice->expiry_date)
                                                <span class="mx-1">&rarr;</span>
                                                {{ $notice->expiry_date_bs ? $notice->expiry_date_bs . ' BS' : $notice->expiry_date->format('Y-m-d') }}
                                            @endif
                                        </div>

                                        <p class="card-text text-muted font-size-sm mb-3 flex-grow-1" style="line-height: 1.55;">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($notice->content), 120) }}
                                        </p>

                                        <div class="d-flex flex-wrap align-items-center font-size-xs text-muted mb-3 gap-1">
                                            <span class="badge badge-light border mr-1">
                                                <i class="icon-eye mr-1"></i>{{ ucfirst($notice->role_visibility ?? 'All') }}
                                            </span>
                                            @if($notice->my_class)
                                                <span class="badge badge-info">{{ $notice->my_class->name }}</span>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                            <a href="{{ route('notices.show', $notice->id) }}" class="btn btn-outline-primary btn-sm font-weight-semibold" style="border-radius: 6px;">
                                                <i class="icon-eye mr-1"></i>{{ __('Read More') }}
                                            </a>
                                            @if(Qs::userIsTeamSA())
                                                <div class="list-icons">
                                                    <div class="dropdown">
                                                        <a href="#" class="list-icons-item" data-toggle="dropdown"><i class="icon-menu7"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <a href="{{ route('notices.edit', $notice->id) }}" class="dropdown-item"><i class="icon-pencil mr-2"></i> {{ __('Edit') }}</a>
                                                            @if(Qs::userIsSuperAdmin())
                                                                <a id="{{ $notice->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger"><i class="icon-trash mr-2"></i> {{ __('Delete') }}</a>
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
                                <div class="text-center py-5 text-muted">
                                    <i class="icon-newspaper icon-2x mb-2 d-block text-muted"></i>
                                    <span class="font-weight-medium">{{ __('No notices found at this time.') }}</span>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <div class="text-muted font-size-sm">
                            {{ __('Showing :from to :to of :total notices', [
                                'from' => $notices->firstItem() ?? 0,
                                'to' => $notices->lastItem() ?? 0,
                                'total' => $notices->total()
                            ]) }}
                        </div>
                        <div>
                            {{ $notices->links() }}
                        </div>
                    </div>
                </div>

                @if(Qs::userIsTeamSA())
                    <div class="tab-pane fade" id="new-notice">
                        <form method="post" action="{{ route('notices.store') }}" class="p-2">
                            @csrf
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-semibold text-dark">{{ __('Notice Title') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="{{ __('Enter notice title') }}">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="font-weight-semibold text-dark">{{ __('Content') }} <span class="text-danger">*</span></label>
                                        <textarea name="content" rows="9" class="form-control" required placeholder="{{ __('Enter notice description/content') }}">{{ old('content') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold text-dark">{{ __('Notice Date (BS)') }}</label>
                                            <input type="text" name="notice_date_bs" class="form-control nepali-date" value="{{ old('notice_date_bs', $today_bs ?? '') }}" placeholder="YYYY-MM-DD">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold text-dark">{{ __('Expiry Date (BS)') }}</label>
                                            <input type="text" name="expiry_date_bs" class="form-control nepali-date" value="{{ old('expiry_date_bs') }}" placeholder="YYYY-MM-DD">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold text-dark">{{ __('Target Role Visibility') }}</label>
                                            <select name="role_visibility" class="form-control select">
                                                <option value="all">{{ __('All Users') }}</option>
                                                <option value="teacher">{{ __('Teachers Only') }}</option>
                                                <option value="student">{{ __('Students Only') }}</option>
                                                <option value="parent">{{ __('Parents Only') }}</option>
                                                <option value="accountant">{{ __('Accountants Only') }}</option>
                                                <option value="librarian">{{ __('Librarians Only') }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-semibold text-dark">{{ __('Specific Class (Optional)') }}</label>
                                            <select name="my_class_id" class="form-control select">
                                                <option value="">{{ __('All Classes') }}</option>
                                                @foreach($my_classes as $c)
                                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="is_published" value="1" class="custom-control-input" id="notice_is_published" checked>
                                                <label class="custom-control-label font-weight-semibold text-success" for="notice_is_published">{{ __('Publish Immediately') }}</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn text-white btn-block font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                            <i class="icon-paperplane mr-2"></i>{{ __('Post Notice') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
