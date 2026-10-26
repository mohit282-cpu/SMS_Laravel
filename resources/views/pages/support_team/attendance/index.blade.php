@extends('layouts.master')
@section('page_title', __('Student Daily Attendance (BS)'))
@section('content')

    @if(isset($has_assignment) && !$has_assignment)
        <div class="alert alert-warning border-0">
            <h5 class="alert-heading font-weight-semibold mb-1"><i class="icon-warning22 mr-1"></i> {{ __('No Class / Section Assigned') }}</h5>
            {{ __('You are not currently assigned as a class teacher to any class or section. Student attendance cannot be accessed or recorded until an administrator assigns you to a section.') }}
        </div>
    @else
        {{-- Offline Attendance Synchronization Widget --}}
        <div id="pwa-attendance-offline-widget" class="card border-left-3 border-left-warning mb-3" style="display: none;">
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

        <div class="card">
            <div class="card-header header-elements-inline">
                <h6 class="card-title font-weight-bold">{{ __('Student Daily Attendance (Bikram Sambat)') }}</h6>
                {!! Qs::getPanelOptions() !!}
            </div>

            <div class="card-body">
                {{-- Filter Form --}}
                <form method="get" action="{{ route('attendance.index') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="my_class_id" class="font-weight-bold">{{ __('Select Class') }}: <span class="text-danger">*</span></label>
                                <select onchange="getAttendanceSections(this.value)" required id="my_class_id" name="my_class_id" class="form-control select">
                                    <option value="">{{ __('Select Class') }}</option>
                                    @foreach($my_classes as $c)
                                        <option {{ ($selected_class == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="section_id" class="font-weight-bold">{{ __('Select Section') }}:</label>
                                <select id="section_id" name="section_id" class="form-control select">
                                    @if(Auth::user()->user_type !== 'teacher' || count($sections) > 1)
                                        <option value="">{{ __('All Sections') }}</option>
                                    @endif
                                    @foreach($sections as $s)
                                        <option {{ ($selected_section == $s->id) ? 'selected' : '' }} value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="attendance_date_bs" class="font-weight-bold">{{ __('Attendance Date (BS)') }}: <span class="text-danger">*</span></label>
                                <input type="text" id="attendance_date_bs" name="attendance_date_bs" value="{{ $selected_date }}" required class="form-control nepali-date" placeholder="YYYY-MM-DD (BS)">
                                @if(isset($date_info) && $date_info['ad_date'])
                                    <span class="form-text text-muted">{{ $date_info['ad_date'] }} AD</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group pt-md-4">
                                <button type="submit" class="btn btn-primary btn-block"><i class="icon-filter3 mr-1"></i> {{ __('Filter / Load Students') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($selected_class)
            {{-- Holiday / Non-working Day / Audit Warnings --}}
            @if(isset($date_info) && $date_info['warning'])
                <div class="alert alert-{{ $date_info['is_holiday'] ? 'danger' : 'warning' }} border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"><i class="icon-warning22 mr-1"></i> {{ $date_info['warning'] }}</span>
                </div>
            @endif

            @if($is_already_recorded)
                <div class="alert alert-info border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <i class="icon-info22 mr-1"></i> {{ __('Attendance has already been recorded for this date. Modifying the entries below will update the records and log an audit trail.') }}
                </div>
            @endif

            @if(!$can_manage)
                <div class="alert alert-danger border-0">
                    <i class="icon-blocked mr-1"></i> {{ __('Read-Only: You are not assigned as the class teacher for this class or section. Attendance cannot be submitted.') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title font-weight-bold">
                        {{ __('Students List') }} ({{ count($students) }})
                        <span class="text-muted ml-2 font-size-sm">[{{ $selected_date }} BS]</span>
                    </h6>
                    <div class="header-elements">
                        @if($can_manage && count($students) > 0)
                            <button type="button" class="btn btn-sm btn-outline-success mr-2" onclick="markAllStatus('present')"><i class="icon-checkmark4 mr-1"></i> {{ __('Mark All Present') }}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger mr-2" onclick="markAllStatus('absent')"><i class="icon-cross2 mr-1"></i> {{ __('Mark All Absent') }}</button>
                        @endif
                        <a href="{{ route('attendance.monthly', ['my_class_id' => $selected_class, 'section_id' => $selected_section]) }}" class="btn btn-sm btn-info"><i class="icon-calendar52 mr-1"></i> {{ __('Monthly Sheet') }}</a>
                    </div>
                </div>

                <div class="card-body">
                    @if(count($students) > 0)
                        <form method="post" action="{{ route('attendance.store') }}" id="attendance-entry-form">
                            @csrf
                            <input type="hidden" name="my_class_id" value="{{ $selected_class }}">
                            <input type="hidden" name="section_id" value="{{ $selected_section }}">
                            <input type="hidden" name="attendance_date_bs" value="{{ $selected_date }}">

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Adm No') }}</th>
                                        <th>{{ __('Photo') }}</th>
                                        <th>{{ __('Student Name') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Remarks / Reason') }}</th>
                                        <th>{{ __('Action') }}</th>
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
                                            <td class="font-weight-semibold">{{ $sr->adm_no }}</td>
                                            <td><img class="rounded-circle" style="height: 38px; width: 38px;" src="{{ $sr->user->photo }}" alt="img"></td>
                                            <td>
                                                <span class="font-weight-bold">{{ $sr->user->name }}</span>
                                                <br>
                                                <span class="text-muted font-size-sm">{{ $sr->my_class->name }} {{ $sr->section ? '- '.$sr->section->name : '' }}</span>
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
                                            <td>
                                                <a href="{{ route('attendance.student_history', $sr->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="{{ __('View Attendance History') }}"><i class="icon-history"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($can_manage)
                                <div class="text-right mt-3">
                                    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('{{ __('Are you sure you want to save attendance for this date?') }}')">
                                        <i class="icon-paperplane mr-2"></i> {{ __('Save Attendance') }}
                                    </button>
                                </div>
                            @endif
                        </form>
                    @else
                        <div class="alert alert-warning border-0">
                            <i class="icon-info22 mr-1"></i> {{ __('No students found in this class or section.') }}
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
