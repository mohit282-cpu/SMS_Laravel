@extends('layouts.master')
@section('page_title', 'Edit Subject — ' . $s->name . ' (' . $s->my_class->name . ')')
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <a href="{{ route('subjects.index') }}" class="btn btn-light btn-icon mr-3 shadow-sm" style="border-radius: 10px;" title="{{ __('Back to Subjects List') }}">
                        <i class="icon-arrow-left7"></i>
                    </a>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Edit Subject') }}: {{ $s->name }} ({{ $s->my_class->name }})
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            <span class="badge badge-light border text-muted mr-1">{{ $s->my_class->name ?? '' }}</span>
                            @if($s->slug)
                                <span class="badge badge-primary py-0 px-2 font-weight-semibold mr-1">{{ $s->slug }}</span>
                            @endif
                            <span>{{ __('Update curriculum course title, short code or instructor') }}</span>
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
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card sms-card border-0 shadow-sm" style="border-radius: 14px;">
                <div class="card-header bg-white py-3 border-bottom" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <h5 class="font-weight-bold mb-0 text-dark">
                        <i class="icon-pencil5 mr-2 text-primary"></i>{{ __('Subject Information') }}
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form class="ajax-update" method="post" action="{{ route('subjects.update', $s->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold">{{ __('Subject Title') }}: <span class="text-danger">*</span></label>
                            <input name="name" value="{{ $s->name }}" required type="text" class="form-control" placeholder="{{ __('Name of Subject') }}">
                            <span class="form-text text-muted font-size-xs">{{ __('Course name as printed on report cards.') }}</span>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-semibold">{{ __('Short Code / Abbreviation') }}:</label>
                            <input name="slug" value="{{ $s->slug }}" type="text" class="form-control" placeholder="{{ __('e.g. MATH, ENG') }}">
                            <span class="form-text text-muted font-size-xs">{{ __('Short abbreviation for timetable displays.') }}</span>
                        </div>

                        <div class="form-group mb-3">
                            <label for="my_class_id" class="font-weight-semibold">{{ __('Academic Class') }}: <span class="text-danger">*</span></label>
                            <select required data-placeholder="{{ __('Select Class') }}" class="form-control select" name="my_class_id" id="my_class_id">
                                @foreach($my_classes as $c)
                                    <option {{ $s->my_class_id == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted font-size-xs">{{ __('Associated grade level.') }}</span>
                        </div>

                        <div class="form-group mb-4">
                            <label for="teacher_id" class="font-weight-semibold">{{ __('Subject Teacher') }}:</label>
                            <select data-placeholder="{{ __('Select Teacher') }}" class="form-control select-search" name="teacher_id" id="teacher_id">
                                <option value="">{{ __('None (Unassigned)') }}</option>
                                @foreach($teachers as $t)
                                    <option {{ $s->teacher_id == $t->id ? 'selected' : '' }} value="{{ Qs::hash($t->id) }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted font-size-xs">{{ __('Designated faculty instructor for this course.') }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <a href="{{ route('subjects.index') }}" class="btn btn-light font-weight-semibold shadow-sm" style="border-radius: 8px;">
                                <i class="icon-arrow-left7 mr-1"></i>{{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary font-weight-semibold px-4 shadow-sm" style="border-radius: 8px;">
                                <i class="icon-checkmark4 mr-1"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
