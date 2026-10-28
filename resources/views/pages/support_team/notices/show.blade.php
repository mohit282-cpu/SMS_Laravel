@extends('layouts.master')
@section('page_title', $notice->title)
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.15rem;"><i class="icon-newspaper mr-2" style="color: #FF5700;"></i>{{ $notice->title }}</h2>
        <div class="header-elements">
            <a href="{{ route('notices.index') }}" class="btn btn-outline-secondary btn-sm"><i class="icon-arrow-left52 mr-1"></i>{{ __('Back to Notices') }}</a>
            @if(Qs::userIsTeamSA())
                <a href="{{ route('notices.edit', $notice->id) }}" class="btn btn-outline-primary btn-sm ml-2"><i class="icon-pencil mr-1"></i>{{ __('Edit') }}</a>
            @endif
        </div>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap text-muted font-size-sm border-bottom pb-2 mb-3">
            <span class="mr-3"><i class="icon-calendar mr-1"></i> {{ __('Posted') }}: {{ $notice->notice_date_bs ? $notice->notice_date_bs . ' BS' : $notice->notice_date->format('Y-m-d') }}</span>
            @if($notice->expiry_date)
                <span class="mr-3"><i class="icon-calendar52 mr-1"></i> {{ __('Expires') }}: {{ $notice->expiry_date_bs ? $notice->expiry_date_bs . ' BS' : $notice->expiry_date->format('Y-m-d') }}</span>
            @endif
            @if($notice->creator)
                <span class="mr-3"><i class="icon-user mr-1"></i> {{ __('Author') }}: {{ $notice->creator->name }}</span>
            @endif
            <span class="mr-3"><i class="icon-eye mr-1"></i> {{ __('Audience') }}: {{ ucfirst($notice->role_visibility ?? 'All') }}</span>
            @if($notice->my_class)
                <span class="mr-3"><i class="icon-graduation2 mr-1"></i> {{ __('Class') }}: {{ $notice->my_class->name }}</span>
            @endif
        </div>

        <div class="content-group text-dark" style="font-size: 1.05rem; line-height: 1.7; white-space: pre-wrap;">{{ $notice->content }}</div>
    </div>
</div>

@endsection
