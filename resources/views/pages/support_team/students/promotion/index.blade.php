@extends('layouts.master')
@section('page_title', 'Student Promotion')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-stairs-up mr-2" style="color: #FF5700;"></i>{{ __('Student Promotion') }} {{ __('From') }} <span class="text-danger">{{ $old_year }}</span> {{ __('TO') }} <span class="text-success">{{ $new_year }}</span> {{ __('Session') }}</h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @include('pages.support_team.students.promotion.selector')
        </div>
    </div>

    @if($selected)
    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">{{ __('Promote Students From') }} <span class="text-teal">{{ $my_classes->where('id', $fc)->first()->name.' '.$sections->where('id', $fs)->first()->name }}</span> {{ __('TO') }} <span class="text-purple">{{ $my_classes->where('id', $tc)->first()->name.' '.$sections->where('id', $ts)->first()->name }}</span> </h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @include('pages.support_team.students.promotion.promote')
        </div>
    </div>
    @endif


    {{--Student Promotion End--}}

@endsection
