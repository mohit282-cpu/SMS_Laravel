@extends('layouts.master')
@section('page_title', __('Children Timetables'))
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-bold"><i class="icon-calendar mr-2"></i>{{ __('Select Child to View Timetable') }}</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <div class="row">
            @forelse($children as $ch)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card border border-primary shadow-sm h-100">
                        <div class="card-body text-center">
                            <img src="{{ $ch->user->photo }}" class="rounded-circle mb-2" width="60" height="60" alt="photo">
                            <h6 class="font-weight-bold mb-1">{{ $ch->user->name }}</h6>
                            <p class="text-muted font-size-sm mb-3">{{ $ch->my_class->name ?? '' }} ({{ $ch->section->name ?? '' }})</p>
                            <a href="{{ route('tt.student_timetable', Qs::hash($ch->user->id)) }}" class="btn btn-primary btn-sm btn-block">
                                <i class="icon-eye mr-1"></i>{{ __('View Timetable') }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-4">
                    <i class="icon-user-cancel icon-2x mb-2 d-block"></i>
                    {{ __('No linked children found.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
