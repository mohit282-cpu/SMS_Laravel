@extends('layouts.master')
@section('page_title', __('Holiday Calendar'))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">{{ __('School Holiday & Event Calendar (BS & AD)') }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#calendar-view" class="nav-link active" data-toggle="tab"><i class="icon-calendar mr-1"></i> {{ __('Calendar View') }}</a></li>
                <li class="nav-item"><a href="#all-holidays" class="nav-link" data-toggle="tab"><i class="icon-list mr-1"></i> {{ __('All Holidays & Events') }}</a></li>
                <li class="nav-item"><a href="#new-holiday" class="nav-link" data-toggle="tab"><i class="icon-plus2 mr-1"></i> {{ __('Add Holiday / Event') }}</a></li>
            </ul>

            <div class="tab-content">
                {{--FullCalendar Tab--}}
                <div class="tab-pane fade show active" id="calendar-view">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <span class="badge badge-danger p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Public / School Holiday') }}</span>
                            <span class="badge badge-warning p-2 mr-2 text-white"><i class="icon-primitive-dot"></i> {{ __('Examination Period') }}</span>
                            <span class="badge badge-primary p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Academic Event') }}</span>
                            <span class="badge badge-success p-2 mr-2"><i class="icon-primitive-dot"></i> {{ __('Custom Working Day') }}</span>
                        </div>
                    </div>
                    <div class="fullcalendar-basic" id="school-holiday-calendar"></div>
                </div>

                {{--Table Tab--}}
                <div class="tab-pane fade" id="all-holidays">
                    <table class="table datatable-button-html5-columns">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Title (Nepali)') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Dates (BS)') }}</th>
                            <th>{{ __('Dates (AD)') }}</th>
                            <th>{{ __('School Closure') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($holidays as $h)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-weight-semibold">{{ $h->title }}</td>
                                <td>{{ $h->title_ne ?: '-' }}</td>
                                <td>
                                    @if($h->type === 'public_holiday')
                                        <span class="badge badge-danger">{{ __('Public Holiday') }}</span>
                                    @elseif($h->type === 'school_holiday')
                                        <span class="badge badge-danger">{{ __('School Holiday') }}</span>
                                    @elseif($h->type === 'exam_period')
                                        <span class="badge badge-warning text-white">{{ __('Exam Period') }}</span>
                                    @elseif($h->type === 'academic_event')
                                        <span class="badge badge-primary">{{ __('Academic Event') }}</span>
                                    @else
                                        <span class="badge badge-success">{{ __('Working Day') }}</span>
                                    @endif
                                </td>
                                <td>{{ $h->start_date_bs }} @if($h->start_date_bs !== $h->end_date_bs) - {{ $h->end_date_bs }} @endif BS</td>
                                <td>{{ $h->start_date->format('Y-m-d') }} @if($h->start_date->format('Y-m-d') !== $h->end_date->format('Y-m-d')) - {{ $h->end_date->format('Y-m-d') }} @endif</td>
                                <td>
                                    @if($h->is_closure)
                                        <span class="badge badge-danger">{{ __('Closed') }}</span>
                                    @else
                                        <span class="badge badge-info">{{ __('Open') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="post" action="{{ route('holidays.destroy', $h->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this event?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="icon-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{--Add Holiday Tab--}}
                <div class="tab-pane fade" id="new-holiday">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="post" action="{{ route('holidays.store') }}">
                                @csrf
                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Event Title (English)') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="title" value="{{ old('title') }}" required type="text" class="form-control" placeholder="e.g. Dashain Vacation">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Event Title (Nepali)') }}:</label>
                                    <div class="col-lg-9">
                                        <input name="title_ne" value="{{ old('title_ne') }}" type="text" class="form-control" placeholder="उदा: दशैं बिदा">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Event Type') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <select name="type" required class="form-control select">
                                            <option value="public_holiday">{{ __('Public Holiday (सार्वजनिक बिदा)') }}</option>
                                            <option value="school_holiday" selected>{{ __('School Holiday (विद्यालय बिदा)') }}</option>
                                            <option value="exam_period">{{ __('Examination Period (परीक्षा अवधि)') }}</option>
                                            <option value="academic_event">{{ __('Academic Event / Celebration (शैक्षिक कार्यक्रम)') }}</option>
                                            <option value="custom_working_day">{{ __('Custom Working Day (कार्य दिन)') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Start Date (BS)') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="start_date_bs" value="{{ old('start_date_bs', '2083-07-07') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('End Date (BS)') }}: <span class="text-danger">*</span></label>
                                    <div class="col-lg-9">
                                        <input name="end_date_bs" value="{{ old('end_date_bs', '2083-07-07') }}" required type="text" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('Description') }}:</label>
                                    <div class="col-lg-9">
                                        <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Optional details') }}">{{ old('description') }}</textarea>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-lg-3 col-form-label font-weight-semibold">{{ __('School Closure') }}:</label>
                                    <div class="col-lg-9">
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" name="is_closure" value="1" class="form-check-input-styled" checked data-fouc>
                                                {{ __('School remains closed on this day/period') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">{{ __('Add Event / Holiday') }} <i class="icon-paperplane ml-2"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        if ($('#school-holiday-calendar').length) {
            $('#school-holiday-calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: '{{ route("calendar.events") }}',
                editable: false,
                eventLimit: true
            });
        }
    });
</script>
@endsection
