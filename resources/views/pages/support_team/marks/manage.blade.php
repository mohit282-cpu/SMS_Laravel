@extends('layouts.master')
@section('page_title', 'Manage Marks')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold">Manage Marks Selection</h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @include('pages.support_team.marks.selector')
        </div>
    </div>

    <div class="card">

        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-4 mb-2 mb-md-0"><div class="sms-status-pill"><strong>{{ __('Subject') }}:</strong> {{ $m->subject->name }}</div></div>
                <div class="col-md-4 mb-2 mb-md-0"><div class="sms-status-pill"><strong>{{ __('Class') }}:</strong> {{ $m->my_class->name.' '.$m->section->name }}</div></div>
                <div class="col-md-4"><div class="sms-status-pill"><strong>{{ __('Exam') }}:</strong> {{ $m->exam->name.' - '.$m->year }}</div></div>
            </div>
        </div>

        <div class="card-body">
            @include('pages.support_team.marks.edit')
            {{--@include('pages.support_team.marks.random')--}}
        </div>
    </div>

    {{--Marks Manage End--}}

@endsection
