@extends('layouts.master')
@section('page_title', 'Manage Exam Marks')
@section('content')
    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-books mr-2" style="color: #FF5700;"></i> {{ __('Manage Exam Marks') }}</h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @include('pages.support_team.marks.selector')
        </div>
    </div>
    @endsection
