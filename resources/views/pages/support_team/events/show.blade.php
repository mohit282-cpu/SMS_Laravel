@extends('layouts.master')
@section('page_title', $event->title)
@section('content')

    <div class="card shadow-sm border-0" style="border-radius: 16px;">
        <div class="card-header d-flex align-items-center justify-content-between" style="background: #111827; color: #FFFFFF; border-radius: 16px 16px 0 0;">
            <div class="d-flex align-items-center">
                <span class="badge mr-2 px-3 py-1 font-size-sm" style="background: {{ $event->category_color }}; color: #FFFFFF;">
                    {{ $event->category_name }}
                </span>
                <h2 class="card-title font-weight-bold mb-0 text-white" style="font-size: 1.15rem;">{{ $event->title }}</h2>
            </div>
            <div class="d-flex align-items-center">
                @if(in_array(auth()->user()->user_type, ['super_admin', 'admin'], true))
                    <a href="{{ route('events.edit', $event->id) }}" class="btn btn-sm btn-light mr-2 font-weight-semibold" style="border-radius: 8px;">
                        <i class="icon-pencil mr-1"></i>{{ __('Edit') }}
                    </a>
                @endif
                <a href="{{ route('events.index') }}" class="btn btn-sm text-white font-weight-semibold" style="background: rgba(255,255,255,0.15); border-radius: 8px;">
                    <i class="icon-arrow-left52 mr-1"></i>{{ __('Back to Calendar') }}
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            @if($event->title_ne)
                <h6 class="text-muted font-weight-semibold mb-3">{{ $event->title_ne }}</h6>
            @endif

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="p-3 bg-light rounded border">
                        <div class="text-muted font-size-xs text-uppercase font-weight-semibold mb-1">{{ __('Bikram Sambat (BS) Date') }}</div>
                        <div class="font-weight-bold text-dark font-size-lg">{{ $event->start_date_bs }}</div>
                        @if($event->end_date_bs && $event->end_date_bs !== $event->start_date_bs)
                            <div class="text-muted font-size-xs">{{ __('to') }} {{ $event->end_date_bs }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="p-3 bg-light rounded border">
                        <div class="text-muted font-size-xs text-uppercase font-weight-semibold mb-1">{{ __('Gregorian (AD) Date') }}</div>
                        <div class="font-weight-bold text-dark font-size-lg">{{ $event->start_date ? $event->start_date->format('d M Y') : '-' }}</div>
                        @if($event->end_date && $event->end_date->format('Y-m-d') !== $event->start_date->format('Y-m-d'))
                            <div class="text-muted font-size-xs">{{ __('to') }} {{ $event->end_date->format('d M Y') }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="p-3 bg-light rounded border">
                        <div class="text-muted font-size-xs text-uppercase font-weight-semibold mb-1">{{ __('Timing & Audience') }}</div>
                        <div class="font-weight-bold text-dark font-size-lg">
                            @if($event->is_all_day)
                                {{ __('All-Day Event') }}
                            @elseif($event->start_time)
                                {{ $event->start_time }} {{ $event->end_time ? '- ' . $event->end_time : '' }}
                            @else
                                {{ __('Not specified') }}
                            @endif
                        </div>
                        <div class="text-muted font-size-xs">
                            @if($event->target_audience === 'all')
                                {{ __('Entire School') }}
                            @elseif($event->target_audience === 'classes')
                                {{ $event->my_class->name ?? 'Class' }} {{ $event->section ? '(' . $event->section->name . ')' : '' }}
                            @else
                                {{ ucfirst($event->target_audience) }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($event->location)
                <div class="mb-4">
                    <h6 class="font-weight-bold text-dark mb-1"><i class="icon-location4 mr-1 text-primary"></i> {{ __('Venue / Location') }}</h6>
                    <p class="text-dark mb-0">{{ $event->location }}</p>
                </div>
            @endif

            @if($event->description)
                <div class="mb-4">
                    <h6 class="font-weight-bold text-dark mb-1"><i class="icon-file-text2 mr-1 text-primary"></i> {{ __('Description') }}</h6>
                    <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; line-height: 1.6;">{{ $event->description }}</div>
                </div>
            @endif

            @if($event->instructions)
                <div class="mb-4">
                    <h6 class="font-weight-bold text-dark mb-1"><i class="icon-info22 mr-1 text-warning"></i> {{ __('Special Instructions') }}</h6>
                    <div class="p-3 rounded border text-dark" style="background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.2) !important; white-space: pre-wrap; line-height: 1.6;">{{ $event->instructions }}</div>
                </div>
            @endif

            <div class="border-top pt-3 d-flex justify-content-between text-muted font-size-xs">
                <span>{{ __('Academic Session:') }} <strong>{{ $event->session }}</strong></span>
                <span>{{ __('Status:') }} <strong>{{ $event->is_published ? __('Published') : __('Draft') }}</strong></span>
                <span>{{ __('Last updated:') }} {{ $event->updated_at ? $event->updated_at->diffForHumans() : '-' }}</span>
            </div>
        </div>
    </div>

@endsection
