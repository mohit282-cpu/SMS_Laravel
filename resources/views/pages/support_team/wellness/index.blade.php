@extends('layouts.master')
@section('page_title', __('Student Wellness, ECA & Merit Logs'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('Student Wellness, Infirmary, ECA & Student Groups') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Merit & demerit points, infirmary/health check logs, ECA participation, and student clubs/houses') }}</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Merit Points Awarded') }}</span><h3 class="font-weight-bold text-success mb-0">{{ $meritPoints }}</h3></div></div></div>
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Infirmary / Health Visits') }}</span><h3 class="font-weight-bold text-info mb-0">{{ $infirmaryCount }}</h3></div></div></div>
        <div class="col-md-4"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('ECA Records / Groups') }}</span><h3 class="font-weight-bold text-primary mb-0">{{ $ecaCount }} / {{ $groups->count() }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#wl-logs" class="nav-link active" data-toggle="tab">{{ __('Merit, Health & ECA Logs') }}</a></li>
                <li class="nav-item"><a href="#wl-groups" class="nav-link" data-toggle="tab">{{ __('Student Groups & Clubs') }}</a></li>
                @if(Qs::userIsTeamSAT())
                    <li class="nav-item"><a href="#wl-new" class="nav-link" data-toggle="tab">{{ __('Record New Entry') }}</a></li>
                @endif
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="wl-logs">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Category') }}</th><th>{{ __('Title') }}</th><th>{{ __('Points / Vitals') }}</th><th>{{ __('Date (BS)') }}</th></tr></thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td><strong>{{ $log->studentRecord->user->name ?? '-' }}</strong><div class="text-muted font-size-xs">{{ $log->studentRecord->my_class->name ?? '' }}</div></td>
                                        <td><span class="badge badge-{{ $log->category === 'merit' ? 'success' : ($log->category === 'demerit' ? 'danger' : 'info') }} text-uppercase">{{ str_replace('_', ' ', $log->category) }}</span></td>
                                        <td><strong>{{ $log->title }}</strong><div class="text-muted font-size-xs">{{ $log->description }} {{ $log->action_taken ? '• Action: '.$log->action_taken : '' }}</div></td>
                                        <td>{{ $log->points ? ($log->points > 0 ? '+' . $log->points : $log->points) . ' pts' : ($log->vitals_summary ?: '-') }}</td>
                                        <td>{{ $log->log_date_bs ?: $log->log_date_ad->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No wellness or merit entries found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $logs->links() }}</div>
                </div>

                <div class="tab-pane fade" id="wl-groups">
                    @if(Qs::userIsTeamSA())
                        <form method="POST" action="{{ route('wellness.groups.store') }}" class="mb-4 p-3 bg-light rounded border">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-4 form-group mb-md-0"><label>{{ __('Group / Club Name') }} *</label><input type="text" name="name" class="form-control" required placeholder="Red Cross / Football Team"></div>
                                <div class="col-md-3 form-group mb-md-0">
                                    <label>{{ __('Category') }}</label>
                                    <select name="category" class="form-control"><option value="club">Club</option><option value="house">House</option><option value="sports">Sports</option><option value="bus">Bus Group</option><option value="academic">Academic</option></select>
                                </div>
                                <div class="col-md-3 form-group mb-md-0">
                                    <label>{{ __('In-charge') }}</label>
                                    <select name="incharge_user_id" class="form-control"><option value="">{{ __('Optional') }}</option>@foreach($staffUsers as $su)<option value="{{ $su->id }}">{{ $su->name }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-block">{{ __('Create Group') }}</button></div>
                            </div>
                        </form>
                    @endif

                    <div class="row">
                        @forelse($groups as $grp)
                            <div class="col-md-6 mb-3">
                                <div class="card border shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <h6 class="font-weight-bold mb-0">{{ $grp->name }}</h6>
                                            <span class="badge badge-info text-capitalize">{{ $grp->category }} ({{ $grp->members->count() }})</span>
                                        </div>
                                        <p class="text-muted font-size-xs mb-2">{{ __('In-charge') }}: {{ $grp->incharge->name ?? __('Unassigned') }}</p>
                                        @if(Qs::userIsTeamSA())
                                            <form method="POST" action="{{ route('wellness.groups.add_member', $grp->id) }}" class="form-inline mb-2" style="gap: 0.35rem;">
                                                @csrf
                                                <select name="student_record_id" class="form-control form-control-sm" required>
                                                    @foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? '' }}</option>@endforeach
                                                </select>
                                                <input type="text" name="role_in_group" class="form-control form-control-sm" placeholder="{{ __('Role') }}" style="width: 90px;">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Add') }}</button>
                                            </form>
                                        @endif
                                        <div class="font-size-xs">
                                            @foreach($grp->members as $m)
                                                <span class="badge badge-light border mr-1 mb-1">{{ $m->studentRecord->user->name ?? '' }}@if($m->role_in_group) ({{ $m->role_in_group }})@endif</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-4">{{ __('No student groups configured yet.') }}</div>
                        @endforelse
                    </div>
                </div>

                @if(Qs::userIsTeamSAT())
                    <div class="tab-pane fade" id="wl-new">
                        <form method="POST" action="{{ route('wellness.logs.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? '' }} ({{ $st->my_class->name ?? '' }})</option>@endforeach</select></div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('Category') }} *</label>
                                    <select name="category" class="form-control" required>
                                        <option value="merit">{{ __('Merit') }}</option>
                                        <option value="demerit">{{ __('Demerit') }}</option>
                                        <option value="infirmary">{{ __('Infirmary / Clinic Visit') }}</option>
                                        <option value="health_check">{{ __('Health Screening') }}</option>
                                        <option value="eca">{{ __('ECA Achievement') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group"><label>{{ __('Points (+/-)') }}</label><input type="number" name="points" class="form-control" value="5"></div>
                                <div class="col-md-6 form-group"><label>{{ __('Title') }} *</label><input type="text" name="title" class="form-control" required></div>
                                <div class="col-md-3 form-group"><label>{{ __('Vitals / Health Summary') }}</label><input type="text" name="vitals_summary" class="form-control" placeholder="Temp / Height / Weight"></div>
                                <div class="col-md-3 form-group"><label>{{ __('Date (BS)') }}</label><input type="text" name="log_date_bs" class="form-control" value="{{ \App\Helpers\NepaliHelper::adToBs(date('Y-m-d')) }}"></div>
                                <div class="col-md-12 form-group"><label>{{ __('Notes / Action Taken') }}</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Save Entry') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
