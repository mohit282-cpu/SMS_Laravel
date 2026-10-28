@extends('layouts.master')
@section('page_title', __('Daily Staff Attendance (BS)'))
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-user-check mr-2" style="color: #FF5700;"></i>{{ __('Daily Staff Attendance (Bikram Sambat)') }}</h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <form method="get" action="{{ route('staff_attendance.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">{{ __('Attendance Date (BS)') }}: <span class="text-danger">*</span></label>
                            <input type="text" name="attendance_date_bs" value="{{ $selected_date }}" required class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            @if(isset($date_info) && $date_info['ad_date'])
                                <span class="form-text text-muted">{{ $date_info['ad_date'] }} AD</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3 pt-md-4">
                        <button type="submit" class="btn btn-primary btn-block"><i class="icon-filter3 mr-1"></i> {{ __('Load Staff List') }}</button>
                    </div>
                    <div class="col-md-3 pt-md-4">
                        <a href="{{ route('staff_attendance.monthly') }}" class="btn btn-outline-info btn-block"><i class="icon-calendar52 mr-1"></i> {{ __('Staff Monthly Summary') }}</a>
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

    @if($is_already_recorded)
        <div class="alert alert-info border-0 alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <i class="icon-info22 mr-1"></i> {{ __('Staff attendance has already been recorded for this date. Modifying the entries below will update the records and log an audit trail.') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;">
                {{ __('Staff Members List') }} ({{ count($staff_users) }})
                <span class="text-muted ml-2 font-size-sm">[{{ $selected_date }} BS]</span>
            </h2>
            <div class="header-elements">
                <button type="button" class="btn btn-sm btn-outline-success mr-2" onclick="markAllStatus('present')"><i class="icon-checkmark4 mr-1"></i> {{ __('Mark All Present') }}</button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAllStatus('absent')"><i class="icon-cross2 mr-1"></i> {{ __('Mark All Absent') }}</button>
            </div>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('staff_attendance.store') }}">
                @csrf
                <input type="hidden" name="attendance_date_bs" value="{{ $selected_date }}">

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
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
                                <td><img class="rounded-circle" style="height: 38px; width: 38px;" src="{{ $u->photo }}" alt="img"></td>
                                <td>
                                    <span class="font-weight-bold">{{ $u->name }}</span>
                                    <br>
                                    <span class="text-muted font-size-sm">{{ $u->email }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-info p-1">{{ ucfirst(str_replace('_', ' ', $u->user_type)) }}</span>
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

                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('{{ __('Are you sure you want to save staff attendance?') }}')">
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
