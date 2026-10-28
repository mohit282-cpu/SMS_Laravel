@extends('layouts.master')
@section('page_title', __('My Timetable'))
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-calendar mr-2" style="color: #FF5700;"></i>{{ __('Class Timetable') }}</h2>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body text-center py-5">
        <i class="icon-calendar2 icon-3x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">{{ __('No timetable has been published for your class yet.') }}</h5>
        <p class="text-muted">{{ __('Please check back later or contact your class teacher / school administrator.') }}</p>
    </div>
</div>

@endsection
