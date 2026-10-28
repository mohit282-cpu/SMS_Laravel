@extends('layouts.master')
@section('page_title', __('Class-wise Attendance Comparison Report (BS)'))
@section('content')

    @if(isset($has_assignment) && !$has_assignment)
        <div class="alert alert-warning border-0">
            <h5 class="alert-heading font-weight-semibold mb-1"><i class="icon-warning22 mr-1"></i> {{ __('No Class / Section Assigned') }}</h5>
            {{ __('You are not currently assigned as a class teacher to any class or section. Student attendance comparison reports cannot be accessed until an administrator assigns you to a section.') }}
        </div>
    @else
        <div class="card d-print-none">
            <div class="card-header header-elements-inline">
                <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-stats-growth mr-2" style="color: #FF5700;"></i>{{ __('Class-wise Attendance Comparison Report') }}</h2>
                {!! Qs::getPanelOptions() !!}
            </div>

            <div class="card-body">
                <form method="get" action="{{ route('attendance.class_report') }}">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="font-weight-bold">{{ __('Select Attendance Date (BS)') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="attendance_date_bs" value="{{ $selected_date }}" required class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            @if(isset($date_info) && $date_info['ad_date'])
                                <span class="form-text text-muted">{{ $date_info['ad_date'] }} AD</span>
                            @endif
                        </div>
                        <div class="col-md-3 pt-md-4">
                            <button type="submit" class="btn btn-primary btn-block"><i class="icon-filter3 mr-1"></i> {{ __('Generate Report') }}</button>
                        </div>
                        <div class="col-md-3 pt-md-4">
                            <button type="button" class="btn btn-outline-secondary btn-block" onclick="window.print()"><i class="icon-printer mr-1"></i> {{ __('Print Report') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($date_info) && $date_info['warning'])
            <div class="alert alert-{{ $date_info['is_holiday'] ? 'danger' : 'warning' }} border-0 alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                <span class="font-weight-semibold"><i class="icon-warning22 mr-1"></i> {{ $date_info['warning'] }}</span>
            </div>
        @endif

        <div class="card">
            <div class="card-header header-elements-inline">
                <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">
                    {{ __('Attendance Overview by Class') }} - [{{ $selected_date }} BS]
                </h2>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Enrolled') }}</th>
                            <th>{{ __('Recorded') }}</th>
                            <th>{{ __('Present') }}</th>
                            <th>{{ __('Absent') }}</th>
                            <th>{{ __('Late') }}</th>
                            <th>{{ __('Leave') }}</th>
                            <th>{{ __('Attendance %') }}</th>
                            <th class="d-print-none">{{ __('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $totEnrolled = 0;
                            $totRecorded = 0;
                            $totPresent = 0;
                            $totAbsent = 0;
                        @endphp
                        @foreach($classes_report as $cr)
                            @php
                                $totEnrolled += $cr['total_students'];
                                $totRecorded += $cr['recorded_attendance'];
                                $totPresent += $cr['present'];
                                $totAbsent += $cr['absent'];
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-weight-bold">{{ $cr['class']->name }}</td>
                                <td>{{ $cr['total_students'] }}</td>
                                <td>
                                    @if($cr['recorded_attendance'] > 0)
                                        <span class="badge badge-success">{{ $cr['recorded_attendance'] }} / {{ $cr['total_students'] }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ __('Not Recorded') }}</span>
                                    @endif
                                </td>
                                <td class="text-success font-weight-semibold">{{ $cr['present'] }}</td>
                                <td class="text-danger font-weight-semibold">{{ $cr['absent'] }}</td>
                                <td class="text-warning font-weight-semibold">{{ $cr['late'] }}</td>
                                <td class="text-info font-weight-semibold">{{ $cr['leave'] }}</td>
                                <td>
                                    @if($cr['recorded_attendance'] > 0)
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 mr-2" style="height: 12px;">
                                                <div class="progress-bar {{ $cr['percentage'] >= 75 ? 'bg-success' : ($cr['percentage'] >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $cr['percentage'] }}%;"></div>
                                            </div>
                                            <span class="font-weight-bold font-size-sm">{{ $cr['percentage'] }}%</span>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="d-print-none">
                                    <a href="{{ route('attendance.index', ['my_class_id' => $cr['class']->id, 'attendance_date_bs' => $selected_date]) }}" class="btn btn-sm btn-outline-primary">{{ __('View / Record') }}</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="2">{{ __('Total School Summary') }}</td>
                            <td>{{ $totEnrolled }}</td>
                            <td>{{ $totRecorded }}</td>
                            <td class="text-success">{{ $totPresent }}</td>
                            <td class="text-danger">{{ $totAbsent }}</td>
                            <td colspan="2"></td>
                            <td>
                                @php
                                    $overallPct = $totRecorded > 0 ? round(($totPresent / $totRecorded) * 100, 1) : 0.0;
                                @endphp
                                <span>{{ $overallPct }}%</span>
                            </td>
                            <td class="d-print-none"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

@endsection
