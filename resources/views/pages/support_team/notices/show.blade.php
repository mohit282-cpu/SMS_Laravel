@extends('layouts.master')
@section('page_title', $notice->title)
@section('content')

    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between bg-white border-bottom py-3 gap-2">
            <div class="d-flex align-items-center">
                <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px; border-radius: 10px; background: rgba(255, 87, 0, 0.12);">
                    <i class="icon-newspaper" style="color: #FF5700; font-size: 1.25rem;"></i>
                </div>
                <div>
                    <h4 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.15rem;">{{ $notice->title }}</h4>
                    <span class="badge {{ $notice->is_published ? 'badge-success' : 'badge-secondary' }} mt-1">
                        {{ $notice->is_published ? __('Published') : __('Draft') }}
                    </span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('notices.index') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold" style="border-radius: 8px;">
                    <i class="icon-arrow-left52 mr-1"></i>{{ __('Back to Notices') }}
                </a>
                @if(Qs::userIsTeamSA())
                    <a href="{{ route('notices.edit', $notice->id) }}" class="btn btn-outline-primary btn-sm font-weight-semibold ml-1" style="border-radius: 8px;">
                        <i class="icon-pencil mr-1"></i>{{ __('Edit') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap text-muted font-size-sm bg-light p-3 rounded border mb-4 gap-3">
                <span class="mr-3"><i class="icon-calendar22 mr-1 text-primary"></i> <strong>{{ __('Posted') }}:</strong> {{ $notice->notice_date_bs ? $notice->notice_date_bs . ' BS' : ($notice->notice_date ? $notice->notice_date->format('Y-m-d') : '-') }}</span>
                @if($notice->expiry_date)
                    <span class="mr-3"><i class="icon-calendar52 mr-1 text-danger"></i> <strong>{{ __('Expires') }}:</strong> {{ $notice->expiry_date_bs ? $notice->expiry_date_bs . ' BS' : $notice->expiry_date->format('Y-m-d') }}</span>
                @endif
                @if($notice->creator)
                    <span class="mr-3"><i class="icon-user mr-1"></i> <strong>{{ __('Author') }}:</strong> {{ $notice->creator->name }}</span>
                @endif
                <span class="mr-3"><i class="icon-eye mr-1"></i> <strong>{{ __('Audience') }}:</strong> {{ ucfirst($notice->role_visibility ?? 'All') }}</span>
                @if($notice->my_class)
                    <span class="mr-3"><i class="icon-graduation2 mr-1"></i> <strong>{{ __('Class') }}:</strong> {{ $notice->my_class->name }}</span>
                @endif
            </div>

            <div class="content-group text-dark p-2" style="font-size: 1.05rem; line-height: 1.75; white-space: pre-wrap;">{{ $notice->content }}</div>
        </div>
    </div>

@endsection
