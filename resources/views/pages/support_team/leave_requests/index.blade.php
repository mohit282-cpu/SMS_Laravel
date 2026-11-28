@extends('layouts.master')
@section('page_title', __('Leave Management'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Digital Leave Applications (Students & Staff)') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('Submit leave notes with Bikram Sambat dates and review approval workflow') }}</p>
            </div>
            <span class="badge badge-light border px-3 py-2">{{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Pending Review') }}</span><h3 class="font-weight-bold text-warning mb-0">{{ $pendingCount }}</h3></div></div></div>
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Approved Leaves') }}</span><h3 class="font-weight-bold text-success mb-0">{{ $approvedCount }}</h3></div></div></div>
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Rejected Leaves') }}</span><h3 class="font-weight-bold text-danger mb-0">{{ $rejectedCount }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#lv-list" class="nav-link active" data-toggle="tab">{{ __('Leave Requests') }}</a></li>
                <li class="nav-item"><a href="#lv-apply" class="nav-link" data-toggle="tab">{{ __('Apply for Leave') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="lv-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Applicant / Student') }}</th>
                                    <th>{{ __('Scope / Type') }}</th>
                                    <th>{{ __('Period (BS)') }}</th>
                                    <th>{{ __('Days') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Review Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaves as $lv)
                                    <tr>
                                        <td>
                                            @if($lv->leave_scope === 'student' && $lv->studentRecord)
                                                <strong>{{ $lv->studentRecord->user->name ?? '-' }}</strong>
                                                <div class="text-muted font-size-xs">{{ $lv->studentRecord->my_class->name ?? '' }} • By {{ $lv->applicant->name ?? '' }}</div>
                                            @else
                                                <strong>{{ $lv->applicant->name ?? '-' }}</strong>
                                                <div class="text-muted font-size-xs">{{ __('Staff Member') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info text-capitalize">{{ $lv->leave_scope }}</span>
                                            <span class="badge badge-light border text-capitalize">{{ $lv->leave_type }}</span>
                                        </td>
                                        <td>{{ $lv->from_date_bs ?: $lv->from_date_ad->format('Y-m-d') }} &rarr; {{ $lv->to_date_bs ?: $lv->to_date_ad->format('Y-m-d') }}</td>
                                        <td>{{ $lv->total_days }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($lv->reason, 60) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $lv->status === 'approved' ? 'success' : ($lv->status === 'rejected' ? 'danger' : 'warning') }} text-capitalize">{{ $lv->status }}</span>
                                            @if($lv->review_remarks)<div class="text-muted font-size-xs">{{ $lv->review_remarks }}</div>@endif
                                        </td>
                                        <td>
                                            @if($lv->status === 'pending' && (($lv->leave_scope === 'staff' && Qs::userIsTeamSA()) || ($lv->leave_scope === 'student' && Qs::userIsTeamSAT())))
                                                <form method="POST" action="{{ route('leaves.review', $lv->id) }}" class="form-inline" style="gap: 0.35rem;">
                                                    @csrf
                                                    <input type="text" name="review_remarks" class="form-control form-control-sm" placeholder="{{ __('Remarks') }}" style="width: 110px;">
                                                    <button type="submit" name="status" value="approved" class="btn btn-sm btn-success">{{ __('Approve') }}</button>
                                                    <button type="submit" name="status" value="rejected" class="btn btn-sm btn-danger">{{ __('Reject') }}</button>
                                                </form>
                                            @else
                                                <span class="text-muted font-size-xs">{{ $lv->reviewer->name ?? __('Awaiting review') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No leave applications recorded.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $leaves->links() }}</div>
                </div>

                <div class="tab-pane fade" id="lv-apply">
                    <form method="POST" action="{{ route('leaves.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="lv_scope">{{ __('Leave Category') }}</label>
                                <select id="lv_scope" name="leave_scope" class="form-control" required>
                                    @if(Qs::userIsStaff())
                                        <option value="staff">{{ __('My Staff Leave') }}</option>
                                    @endif
                                    <option value="student">{{ __('Student Leave Note') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="lv_student">{{ __('Student (for Student Leave)') }}</label>
                                <select id="lv_student" name="student_record_id" class="form-control">
                                    <option value="">{{ __('Select Student (if applicable)') }}</option>
                                    @foreach($students as $st)
                                        <option value="{{ $st->id }}">{{ $st->user->name ?? '' }} ({{ $st->my_class->name ?? '' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="lv_type">{{ __('Leave Type') }}</label>
                                <select id="lv_type" name="leave_type" class="form-control" required>
                                    <option value="sick">{{ __('Sick / Medical') }}</option>
                                    <option value="casual">{{ __('Casual') }}</option>
                                    <option value="urgent">{{ __('Urgent Family Work') }}</option>
                                    <option value="annual">{{ __('Annual') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group"><label for="lv_from_bs">{{ __('From Date (BS)') }}</label><input id="lv_from_bs" type="text" name="from_date_bs" class="form-control" value="{{ \App\Helpers\NepaliHelper::adToBs(date('Y-m-d')) }}"></div>
                            <div class="col-md-3 form-group"><label for="lv_to_bs">{{ __('To Date (BS)') }}</label><input id="lv_to_bs" type="text" name="to_date_bs" class="form-control" value="{{ \App\Helpers\NepaliHelper::adToBs(date('Y-m-d')) }}"></div>
                            <div class="col-md-3 form-group"><label for="lv_from_ad">{{ __('Or From (AD)') }}</label><input id="lv_from_ad" type="date" name="from_date_ad" class="form-control"></div>
                            <div class="col-md-3 form-group"><label for="lv_to_ad">{{ __('Or To (AD)') }}</label><input id="lv_to_ad" type="date" name="to_date_ad" class="form-control"></div>
                            <div class="col-md-12 form-group"><label for="lv_reason">{{ __('Reason') }} *</label><textarea id="lv_reason" name="reason" rows="3" class="form-control" required></textarea></div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Submit Leave Request') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
