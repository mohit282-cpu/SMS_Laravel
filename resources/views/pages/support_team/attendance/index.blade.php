@extends('layouts.master')
@section('page_title', __('Student Daily Attendance (BS)'))
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
                            {{ __('Student Daily Attendance (Bikram Sambat)') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Daily class-wise roll call, offline sync support & Bikram Sambat holiday validation') }}
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
                    <a href="{{ route('attendance.monthly', ['my_class_id' => $selected_class, 'section_id' => $selected_section]) }}" class="btn btn-outline-primary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-calendar52 mr-1"></i>{{ __('Monthly Sheet') }}
                    </a>
                    @if(Qs::userIsTeamSA())
                        <a href="{{ route('attendance.class_report') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold shadow-sm" style="border-radius: 8px;">
                            <i class="icon-stats-growth mr-1"></i>{{ __('Class Report') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($has_assignment) && !$has_assignment)
        <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 12px;">
            <h5 class="alert-heading font-weight-semibold mb-1"><i class="icon-warning22 mr-1"></i> {{ __('No Class / Section Assigned') }}</h5>
            {{ __('You are not currently assigned as a class teacher to any class or section. Student attendance cannot be accessed or recorded until an administrator assigns you to a section.') }}
        </div>
    @else
        {{-- Offline Attendance Synchronization Widget --}}
        <div id="pwa-attendance-offline-widget" class="card border-left-3 border-left-warning shadow-sm mb-4" style="display: none; border-radius: 12px;">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <h6 class="font-weight-bold mb-0 text-warning-800">
                    <i class="icon-cloud-check mr-2"></i> {{ __('Offline Attendance Batches') }}
                    <span id="pwa-pending-count-badge" class="badge badge-warning ml-2">0</span>
                </h6>
                <div>
                    <button type="button" class="btn btn-sm btn-primary" id="pwa-btn-sync-all" onclick="PwaAttendanceUI.syncAll()">
                        <i class="icon-sync mr-1"></i> {{ __('Sync All to Server') }}
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="p-2 bg-light border-bottom font-size-xs text-muted">
                    <i class="icon-info22 mr-1"></i> {{ __('Notice: Offline attendance batches are stored locally and are NOT yet confirmed by the server. Connect to the internet and click "Sync" to finalize.') }}
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr class="table-active">
                                <th>{{ __('Date (BS)') }}</th>
                                <th>{{ __('Class & Section') }}</th>
                                <th>{{ __('Records') }}</th>
                                <th>{{ __('Sync Status') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="pwa-drafts-tbody">
                            {{-- Dynamically populated by PwaAttendanceUI --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
            <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
                <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                    <i class="icon-filter3 mr-2" style="color: #FF5700;"></i>{{ __('Select Class & Attendance Date') }}
                </h5>
                {!! Qs::getPanelOptions() !!}
            </div>

            <div class="card-body p-3 p-md-4">
                <form method="get" action="{{ route('attendance.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label for="my_class_id" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Select Class') }} <span class="text-danger">*</span></label>
                            <select onchange="getAttendanceSections(this.value)" required id="my_class_id" name="my_class_id" class="form-control select">
                                <option value="">{{ __('Select Class') }}</option>
                                @foreach($my_classes as $c)
                                    <option {{ ($selected_class == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-2 mb-md-0">
                            <label for="section_id" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Select Section') }}</label>
                            <select id="section_id" name="section_id" class="form-control select">
                                @if(Auth::user()->user_type !== 'teacher' || count($sections) > 1)
                                    <option value="">{{ __('All Sections') }}</option>
                                @endif
                                @foreach($sections as $s)
                                    <option {{ ($selected_section == $s->id) ? 'selected' : '' }} value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-2 mb-md-0">
                            <label for="attendance_date_bs" class="font-weight-semibold font-size-xs text-uppercase text-muted">{{ __('Attendance Date (BS)') }} <span class="text-danger">*</span></label>
                            <input type="text" id="attendance_date_bs" name="attendance_date_bs" value="{{ $selected_date }}" required class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                            @if(isset($date_info) && $date_info['ad_date'])
                                <span class="form-text text-muted font-size-xs mb-0">{{ $date_info['ad_date'] }} AD</span>
                            @endif
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn text-white btn-block font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;">
                                <i class="icon-filter3 mr-1"></i> {{ __('Filter / Load Students') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($selected_class)
            @php
                $totalRoster = count($students);
                $presentCount = $existing_records->where('status', 'present')->count();
                $absentCount = $existing_records->where('status', 'absent')->count();
                $otherCount = $existing_records->whereIn('status', ['late', 'leave', 'half_day'])->count();
            @endphp

            {{-- 4 Metric KPI Stat Cards --}}
            <div class="row mb-4">
                <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
                    <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Students') }}</span>
                                    <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totalRoster) }}</h3>
                                    <span class="font-size-xs text-muted">{{ __('Enrolled in selection') }}</span>
                                </div>
                                <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                                    <i class="icon-users icon-2x" style="color: #4f46e5;"></i>
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
                                    <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($presentCount) }}</h3>
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
                                    <h3 class="font-weight-bold text-danger mb-0 mt-1">{{ number_format($absentCount) }}</h3>
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
                                    <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($otherCount) }}</h3>
                                    <span class="font-size-xs text-muted">{{ __('Special attendance statuses') }}</span>
                                </div>
                                <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                                    <i class="icon-alarm icon-2x" style="color: #f59e0b;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Holiday / Non-working Day / Audit Warnings --}}
            @if(isset($date_info) && $date_info['warning'])
                <div class="alert alert-{{ $date_info['is_holiday'] ? 'danger' : 'warning' }} border-0 alert-dismissible shadow-sm" style="border-radius: 12px;">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"><i class="icon-warning22 mr-1"></i> {{ $date_info['warning'] }}</span>
                </div>
            @endif

            @if($is_already_recorded)
                <div class="alert alert-info border-0 alert-dismissible shadow-sm" style="border-radius: 12px;">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <i class="icon-info22 mr-1"></i> {{ __('Attendance has already been recorded for this date. Modifying the entries below will update the records and log an audit trail.') }}
                </div>
            @endif

            @if(!$can_manage)
                <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 12px;">
                    <i class="icon-blocked mr-1"></i> {{ __('Read-Only: You are not assigned as the class teacher for this class or section. Attendance cannot be submitted.') }}
                </div>
            @endif

            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between bg-white border-bottom py-3 gap-2">
                    <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                        <i class="icon-list mr-2" style="color: #FF5700;"></i>{{ __('Students List') }}
                        <span class="badge badge-secondary ml-1">{{ count($students) }}</span>
                        <span class="text-muted ml-2 font-size-sm">[{{ $selected_date }} BS]</span>
                    </h5>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($can_manage && count($students) > 0)
                            <button type="button" class="btn btn-sm btn-outline-success font-weight-semibold" style="border-radius: 6px;" onclick="markAllStatus('present')"><i class="icon-checkmark4 mr-1"></i> {{ __('Mark All Present') }}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger font-weight-semibold" style="border-radius: 6px;" onclick="markAllStatus('absent')"><i class="icon-cross2 mr-1"></i> {{ __('Mark All Absent') }}</button>
                        @endif
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    @if(count($students) > 0)
                        <form method="post" action="{{ route('attendance.store') }}" id="attendance-entry-form">
                            @csrf
                            <input type="hidden" name="my_class_id" value="{{ $selected_class }}">
                            <input type="hidden" name="section_id" value="{{ $selected_section }}">
                            <input type="hidden" name="attendance_date_bs" value="{{ $selected_date }}">

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="bg-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>{{ __('Adm No') }}</th>
                                        <th>{{ __('Photo') }}</th>
                                        <th>{{ __('Student Name') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Remarks / Reason') }}</th>
                                        <th class="text-center">{{ __('History') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($students as $sr)
                                        @php
                                            $existing = $existing_records->get($sr->id);
                                            $curStatus = $existing ? $existing->status : 'present';
                                            $curRemarks = $existing ? $existing->remarks : '';
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><code class="font-weight-semibold">{{ $sr->adm_no }}</code></td>
                                            <td><img class="rounded-circle border" style="height: 38px; width: 38px; object-fit: cover;" src="{{ $sr->user->photo }}" alt="img"></td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $sr->user->name }}</div>
                                                <div class="text-muted font-size-xs">{{ $sr->my_class->name }} {{ $sr->section ? '- '.$sr->section->name : '' }}</div>
                                            </td>
                                            <td>
                                                @if($can_manage)
                                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                        <label class="btn btn-sm btn-outline-success {{ $curStatus === 'present' ? 'active' : '' }}">
                                                            <input type="radio" name="students[{{ $sr->id }}][status]" value="present" class="att-status" {{ $curStatus === 'present' ? 'checked' : '' }}> {{ __('Present') }}
                                                        </label>
                                                        <label class="btn btn-sm btn-outline-danger {{ $curStatus === 'absent' ? 'active' : '' }}">
                                                            <input type="radio" name="students[{{ $sr->id }}][status]" value="absent" class="att-status" {{ $curStatus === 'absent' ? 'checked' : '' }}> {{ __('Absent') }}
                                                        </label>
                                                        <label class="btn btn-sm btn-outline-warning {{ $curStatus === 'late' ? 'active' : '' }}">
                                                            <input type="radio" name="students[{{ $sr->id }}][status]" value="late" class="att-status" {{ $curStatus === 'late' ? 'checked' : '' }}> {{ __('Late') }}
                                                        </label>
                                                        <label class="btn btn-sm btn-outline-info {{ $curStatus === 'leave' ? 'active' : '' }}">
                                                            <input type="radio" name="students[{{ $sr->id }}][status]" value="leave" class="att-status" {{ $curStatus === 'leave' ? 'checked' : '' }}> {{ __('Leave') }}
                                                        </label>
                                                        <label class="btn btn-sm btn-outline-secondary {{ $curStatus === 'half_day' ? 'active' : '' }}">
                                                            <input type="radio" name="students[{{ $sr->id }}][status]" value="half_day" class="att-status" {{ $curStatus === 'half_day' ? 'checked' : '' }}> {{ __('Half Day') }}
                                                        </label>
                                                    </div>
                                                @else
                                                    <span class="badge badge-{{ $curStatus === 'present' ? 'success' : ($curStatus === 'absent' ? 'danger' : 'warning') }} p-2">{{ ucfirst($curStatus) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($can_manage)
                                                    <input type="text" name="students[{{ $sr->id }}][remarks]" value="{{ old('students.'.$sr->id.'.remarks', $curRemarks) }}" class="form-control form-control-sm" placeholder="{{ __('Optional remarks (e.g. sick, doctor visit)') }}">
                                                @else
                                                    <span>{{ $curRemarks ?: '-' }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('attendance.student_history', $sr->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 6px;" title="{{ __('View Attendance History') }}"><i class="icon-history"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($can_manage)
                                <div class="text-right border-top pt-3 mt-3">
                                    <button type="submit" class="btn text-white px-4 font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 8px;" onclick="return confirm('{{ __('Are you sure you want to save attendance for this date?') }}')">
                                        <i class="icon-paperplane mr-2"></i> {{ __('Save Attendance') }}
                                    </button>
                                </div>
                            @endif
                        </form>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="icon-users icon-2x mb-2 d-block text-muted"></i>
                            <span class="font-weight-medium">{{ __('No students found in this class or section.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

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

        function getAttendanceSections(classId) {
            if (!classId) return;
            var url = '{{ route('attendance.get_sections', ':id') }}'.replace(':id', classId);
            var sectionSelect = $('#section_id');
            $.ajax({
                dataType: 'json',
                url: url,
                success: function(resp) {
                    sectionSelect.empty();
                    if (resp.length > 1 || {{ Auth::user()->user_type !== 'teacher' ? 'true' : 'false' }}) {
                        sectionSelect.append($('<option>', { value: '', text: '{{ __('All Sections') }}' }));
                    }
                    $.each(resp, function(i, s) {
                        sectionSelect.append($('<option>', { value: s.id, text: s.name }));
                    });
                }
            });
        }

        // Offline Attendance Interception
        var attForm = document.getElementById('attendance-entry-form');
        if (attForm) {
            attForm.addEventListener('submit', function (e) {
                if (!navigator.onLine) {
                    e.preventDefault();

                    var form = this;
                    var classId = form.querySelector('input[name="my_class_id"]').value;
                    var sectionInput = form.querySelector('input[name="section_id"]');
                    var sectionId = sectionInput ? sectionInput.value : null;
                    var dateBs = form.querySelector('input[name="attendance_date_bs"]').value;

                    var studentsData = {};
                    var checkedRadios = form.querySelectorAll('.att-status:checked');
                    checkedRadios.forEach(function (radio) {
                        var match = radio.name.match(/students\[(\d+)\]\[status\]/);
                        if (match) {
                            var stId = match[1];
                            var remarksInput = form.querySelector('input[name="students[' + stId + '][remarks]"]');
                            studentsData[stId] = {
                                status: radio.value,
                                remarks: remarksInput ? remarksInput.value : ''
                            };
                        }
                    });

                    if (Object.keys(studentsData).length === 0) {
                        alert('{{ __("Please select attendance status for at least one student.") }}');
                        return;
                    }

                    if (!window.PwaAttendance) {
                        alert('{{ __("PWA offline storage module is not loaded.") }}');
                        return;
                    }

                    window.PwaAttendance.saveDraft({
                        my_class_id: classId,
                        class_name: '{{ $selected_class ? ($my_classes->where("id", $selected_class)->first()->name ?? "") : "" }}',
                        section_id: sectionId,
                        section_name: '{{ $selected_section ? ($sections->where("id", $selected_section)->first()->name ?? "") : "" }}',
                        attendance_date_bs: dateBs,
                        students: studentsData
                    }, function (err, saved) {
                        if (err) {
                            alert('{{ __("Failed to save offline draft: ") }}' + err.message);
                            return;
                        }

                        if (window.swal) {
                            swal({
                                title: '{{ __("Offline Draft Stored Locally!") }}',
                                text: '{{ __("Attendance has NOT yet been confirmed by the server. Please reconnect to the internet and click \"Sync All to Server\" to commit it to the school database.") }}',
                                type: 'warning',
                                confirmButtonClass: 'btn btn-primary',
                                confirmButtonText: '{{ __("Acknowledge") }}'
                            });
                        } else {
                            alert('{{ __("Offline Draft Stored Locally! Attendance is NOT yet saved to the server. Please click Sync when back online.") }}');
                        }

                        if (window.PwaAttendanceUI) {
                            window.PwaAttendanceUI.refreshWidget();
                        }
                    });
                }
            });
        }
    </script>

@endsection
