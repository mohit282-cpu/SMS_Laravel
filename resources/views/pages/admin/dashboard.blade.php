@extends('layouts.master')
@section('page_title', __('Administrator Dashboard'))

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
    <div>
        <div class="d-flex align-items-center mb-1">
            <span class="badge badge-primary mr-2">
                <i class="icon-shield-check mr-1" aria-hidden="true"></i> {{ __('System Administrator') }}
            </span>
            <span class="text-muted" style="font-size: 0.82rem;">{{ date('l, F j, Y') }}</span>
        </div>
        <h2 class="h4 font-weight-bold text-dark mb-0">{{ __('Welcome,') }} {{ Auth::user()->name }}</h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">
            {{ __('Overview of your school classes, sections, subjects, and student facilities.') }}
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center mt-3 mt-md-0" style="gap: 0.5rem;">
        <a href="{{ route('users.index') }}" class="btn btn-light">
            <i class="icon-users4 mr-1" aria-hidden="true"></i> {{ __('Manage Users') }}
        </a>
        <a href="{{ route('students.create') }}" class="btn btn-primary">
            <i class="icon-user-plus mr-1" aria-hidden="true"></i> {{ __('Admit Student') }}
        </a>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            :label="__('Total Classes')"
            :value="Qs::formatNumber(\App\Models\MyClass::count())"
            icon="icon-windows2"
            variant="orange"
            :href="route('classes.index')"
        />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            :label="__('Total Sections')"
            :value="Qs::formatNumber(\App\Models\Section::count())"
            icon="icon-fence"
            variant="navy"
            :href="route('sections.index')"
        />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            :label="__('Total Subjects')"
            :value="Qs::formatNumber(\App\Models\Subject::count())"
            icon="icon-pin"
            variant="emerald"
            :href="route('subjects.index')"
        />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card
            :label="__('Dormitories')"
            :value="Qs::formatNumber(\App\Models\Dorm::count())"
            icon="icon-home9"
            variant="indigo"
            :href="route('dorms.index')"
        />
    </div>
</div>
@endsection
