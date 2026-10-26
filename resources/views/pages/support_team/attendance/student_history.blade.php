@extends('layouts.master')
@section('page_title', __('Student Attendance History') . ' - ' . $sr->user->name)
@section('content')

    {{-- Student Header & Statistics --}}
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $sr->user->photo }}" class="rounded-circle mb-2" style="width: 80px; height: 80px;" alt="Photo">
                    <h5 class="font-weight-bold mb-1">{{ $sr->user->name }}</h5>
                    <p class="text-muted mb-2">{{ __('Adm No') }}: <strong>{{ $sr->adm_no }}</strong></p>
                    <span class="badge badge-primary p-2">{{ $sr->my_class->name }} {{ $sr->section ? '('.$sr->section->name.')' : '' }}</span>
                    <span class="badge badge-secondary p-2">{{ $sr->session }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title font-weight-bold">{{ __('Attendance Summary & Analytics') }}</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="icon-printer mr-1"></i> {{ __('Print History') }}</button>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4 col-md-2 mb-2">
                            <div class="border rounded p-2">
                                <span class="text-muted font-size-sm">{{ __('Total Days') }}</span>
                                <h4 class="font-weight-bold mb-0">{{ $stats['total_days'] }}</h4>
                            </div>
                        </div>
                        <div class="col-4 col-md-2 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <span class="text-success font-size-sm font-weight-bold">{{ __('Present') }}</span>
                                <h4 class="font-weight-bold text-success mb-0">{{ $stats['present'] }}</h4>
                            </div>
                        </div>
                        <div class="col-4 col-md-2 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <span class="text-danger font-size-sm font-weight-bold">{{ __('Absent') }}</span>
                                <h4 class="font-weight-bold text-danger mb-0">{{ $stats['absent'] }}</h4>
                            </div>
                        </div>
                        <div class="col-4 col-md-2 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <span class="text-warning font-size-sm font-weight-bold">{{ __('Late') }}</span>
                                <h4 class="font-weight-bold text-warning mb-0">{{ $stats['late'] }}</h4>
                            </div>
                        </div>
                        <div class="col-4 col-md-2 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <span class="text-info font-size-sm font-weight-bold">{{ __('Leave') }}</span>
                                <h4 class="font-weight-bold text-info mb-0">{{ $stats['leave'] }}</h4>
                            </div>
                        </div>
                        <div class="col-4 col-md-2 mb-2">
                            <div class="border rounded p-2 {{ $stats['percentage'] >= 75 ? 'bg-success text-white' : ($stats['percentage'] >= 60 ? 'bg-warning text-dark' : 'bg-danger text-white') }}">
                                <span class="font-size-sm font-weight-bold">{{ __('Percentage') }}</span>
                                <h4 class="font-weight-bold mb-0">{{ $stats['percentage'] }}%</h4>
                            </div>
                        </div>
                    </div>

                    @if($stats['leave'] > 0)
                        <div class="mt-2 text-muted font-size-sm">
                            <i class="icon-info22 mr-1"></i> {{ __('Excused Attendance Percentage (excluding approved leave): :pct%', ['pct' => $stats['excused_percentage']]) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card d-print-none">
        <div class="card-body">
            <form method="get" action="{{ route('attendance.student_history', $sr->id) }}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="font-weight-bold">{{ __('Start Date (BS)') }}:</label>
                        <input type="text" name="start_date_bs" value="{{ $start_date_bs }}" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">{{ __('End Date (BS)') }}:</label>
                        <input type="text" name="end_date_bs" value="{{ $end_date_bs }}" class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold">{{ __('Status') }}:</label>
                        <select name="status" class="form-control select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option {{ $status_filter === 'present' ? 'selected' : '' }} value="present">{{ __('Present') }}</option>
                            <option {{ $status_filter === 'absent' ? 'selected' : '' }} value="absent">{{ __('Absent') }}</option>
                            <option {{ $status_filter === 'late' ? 'selected' : '' }} value="late">{{ __('Late') }}</option>
                            <option {{ $status_filter === 'leave' ? 'selected' : '' }} value="leave">{{ __('Leave') }}</option>
                            <option {{ $status_filter === 'half_day' ? 'selected' : '' }} value="half_day">{{ __('Half Day') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 pt-md-4">
                        <button type="submit" class="btn btn-primary btn-block"><i class="icon-filter3 mr-1"></i> {{ __('Apply Filter') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Records Table --}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">{{ __('Attendance Log') }} ({{ count($records) }})</h6>
        </div>
        <div class="card-body">
            @if(count($records) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover datatable-button-html5-columns">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Date (BS)') }}</th>
                            <th>{{ __('Date (AD)') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Remarks') }}</th>
                            <th>{{ __('Recorded At') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($records as $rec)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-weight-bold">{{ $rec->attendance_date_bs }} BS</td>
                                <td>{{ $rec->attendance_date ? $rec->attendance_date->format('Y-m-d (D)') : '-' }}</td>
                                <td>
                                    @if($rec->status === 'present')
                                        <span class="badge badge-success p-2">{{ __('Present') }}</span>
                                    @elseif($rec->status === 'absent')
                                        <span class="badge badge-danger p-2">{{ __('Absent') }}</span>
                                    @elseif($rec->status === 'late')
                                        <span class="badge badge-warning p-2">{{ __('Late') }}</span>
                                    @elseif($rec->status === 'leave')
                                        <span class="badge badge-info p-2">{{ __('Leave') }}</span>
                                    @else
                                        <span class="badge badge-secondary p-2">{{ __('Half Day') }}</span>
                                    @endif
                                </td>
                                <td>{{ $rec->remarks ?: '-' }}</td>
                                <td>{{ $rec->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info border-0">
                    <i class="icon-info22 mr-1"></i> {{ __('No attendance records found for this student matching the criteria.') }}
                </div>
            @endif
        </div>
    </div>

@endsection
