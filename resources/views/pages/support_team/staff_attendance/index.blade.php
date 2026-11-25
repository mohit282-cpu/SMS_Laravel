@extends('layouts.master')
@section('page_title', __('Daily Staff Attendance (BS)'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-user-check icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Daily Staff Attendance (Bikram Sambat)') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Faculty, administrative & support personnel daily attendance register with audit logging') }}
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
                    <a href="{{ route('staff_attendance.monthly') }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-calendar52 mr-1"></i>{{ __('Staff Monthly Summary') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    @php
        $totalStaff = count($staff_users);
        $presentStaff = $existing_records->where('status', 'present')->count();
        $absentStaff = $existing_records->where('status', 'absent')->count();
        $otherStaff = $existing_records->whereIn('status', ['late', 'leave', 'half_day'])->count();
    @endphp
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Staff') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totalStaff) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Active institutional roster') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-users4 icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Present') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($presentStaff) }}</h3>
                            <span class="font-size-xs text-muted">{{ $is_already_recorded ? __('Recorded on date') : __('Pending save') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-checkmark4 icon-2x" style="color: #10b981;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Absent') }}</span>
                            <h3 class="font-weight-bold text-danger mb-0 mt-1">{{ number_format($absentStaff) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Marked absent') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239, 68, 68, 0.1);">
                            <i class="icon-cross2 icon-2x" style="color: #ef4444;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Late / Leave / Half') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($otherStaff) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Special statuses') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-alarm icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter Card --}}
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-filter3 mr-2" style="color: #FF5700;"></i>{{ __('Select Attendance Date') }}
            </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-3 p-md-4">
            <form method="get" action="{{ route('staff_attendance.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Attendance Date (BS)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="attendance_date_bs" value="{{ $selected_date }}" required class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                        @if(isset($date_info) && $date_info['ad_date'])
                            <span class="form-text text-muted font-size-xs mb-0">{{ $date_info['ad_date'] }} AD</span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn text-white btn-block font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                            <i class="icon-filter3 mr-1"></i> {{ __('Load Staff List') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(isset($date_info) && $date_info['warning'])
        <div class="alert alert-{{ $date_info['is_holiday'] ? 'danger' : 'warning' }} border-0 alert-dismissible shadow-sm" style="border-radius: 12px;">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold"><i class="icon-warning22 mr-1"></i> {{ $date_info['warning'] }}</span>
        </div>
    @endif

    @if($is_already_recorded)
        <div class="alert alert-info border-0 alert-dismissible shadow-sm" style="border-radius: 12px;">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <i class="icon-info22 mr-1"></i> {{ __('Staff attendance has already been recorded for this date. Modifying the entries below will update the records and log an audit trail.') }}
        </div>
    @endif

    {{-- Staff Attendance Sheet Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between bg-white border-bottom py-3 gap-2">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-list mr-2" style="color: #FF5700;"></i>{{ __('Staff Members List') }}
                <span class="badge badge-secondary ml-1">{{ count($staff_users) }}</span>
                <span class="text-muted ml-2 font-size-sm">[{{ $selected_date }} BS]</span>
            </h5>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-success font-weight-semibold" style="border-radius: 6px;" onclick="markAllStatus('present')"><i class="icon-checkmark4 mr-1"></i> {{ __('Mark All Present') }}</button>
                <button type="button" class="btn btn-sm btn-outline-danger font-weight-semibold" style="border-radius: 6px;" onclick="markAllStatus('absent')"><i class="icon-cross2 mr-1"></i> {{ __('Mark All Absent') }}</button>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <form method="post" action="{{ route('staff_attendance.store') }}">
                @csrf
                <input type="hidden" name="attendance_date_bs" value="{{ $selected_date }}">

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>{{ __('Photo') }}</th>
                            <th>{{ __('Staff Name') }}</th>
                            <th>{{ __('Role / Type') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Remarks / Reason') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($staff_users as $u)
                            @php
                                $existing = $existing_records->get($u->id);
                                $curStatus = $existing ? $existing->status : 'present';
                                $curRemarks = $existing ? $existing->remarks : '';
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><img class="rounded-circle border" style="height: 38px; width: 38px; object-fit: cover;" src="{{ $u->photo }}" alt="img"></td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $u->name }}</div>
                                    <div class="text-muted font-size-xs">{{ $u->email }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-info" style="border-radius: 6px; padding: 4px 8px;">{{ ucfirst(str_replace('_', ' ', $u->user_type)) }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                        <label class="btn btn-sm btn-outline-success {{ $curStatus === 'present' ? 'active' : '' }}">
                                            <input type="radio" name="staff[{{ $u->id }}][status]" value="present" class="att-status" {{ $curStatus === 'present' ? 'checked' : '' }}> {{ __('Present') }}
                                        </label>
                                        <label class="btn btn-sm btn-outline-danger {{ $curStatus === 'absent' ? 'active' : '' }}">
                                            <input type="radio" name="staff[{{ $u->id }}][status]" value="absent" class="att-status" {{ $curStatus === 'absent' ? 'checked' : '' }}> {{ __('Absent') }}
                                        </label>
                                        <label class="btn btn-sm btn-outline-warning {{ $curStatus === 'late' ? 'active' : '' }}">
                                            <input type="radio" name="staff[{{ $u->id }}][status]" value="late" class="att-status" {{ $curStatus === 'late' ? 'checked' : '' }}> {{ __('Late') }}
                                        </label>
                                        <label class="btn btn-sm btn-outline-info {{ $curStatus === 'leave' ? 'active' : '' }}">
                                            <input type="radio" name="staff[{{ $u->id }}][status]" value="leave" class="att-status" {{ $curStatus === 'leave' ? 'checked' : '' }}> {{ __('Leave') }}
                                        </label>
                                        <label class="btn btn-sm btn-outline-secondary {{ $curStatus === 'half_day' ? 'active' : '' }}">
                                            <input type="radio" name="staff[{{ $u->id }}][status]" value="half_day" class="att-status" {{ $curStatus === 'half_day' ? 'checked' : '' }}> {{ __('Half Day') }}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="staff[{{ $u->id }}][remarks]" value="{{ old('staff.'.$u->id.'.remarks', $curRemarks) }}" class="form-control form-control-sm" placeholder="{{ __('Optional notes') }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-right border-top pt-3 mt-3">
                    <button type="submit" class="btn text-white px-4 font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;" onclick="return confirm('{{ __('Are you sure you want to save staff attendance?') }}')">
                        <i class="icon-paperplane mr-2"></i> {{ __('Save Staff Attendance') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function markAllStatus(status) {
            var radios = document.querySelectorAll('.att-status[value="' + status + '"]');
            radios.forEach(function(radio) {
                radio.checked = true;
                var parentGroup = radio.closest('.btn-group');
                if (parentGroup) {
                    var labels = parentGroup.querySelectorAll('label');
                    labels.forEach(function(l) { l.classList.remove('active'); });
                    radio.parentElement.classList.add('active');
                }
            });
        }
    </script>

@endsection
