@extends('layouts.master')
@section('page_title', __('Online Classes (Zoom / Meet)'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Virtual Classrooms & Live Sessions') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('Zoom, Google Meet & Jitsi virtual classroom schedules synced with classes and subjects') }}</p>
            </div>
            <div>
                <span class="badge badge-success mr-1">{{ __('Live') }}: {{ $liveCount }}</span>
                <span class="badge badge-info">{{ __('Scheduled') }}: {{ $scheduledCount }}</span>
            </div>
        </div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            @if(Qs::userIsTeamSAT())
                <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                    <li class="nav-item"><a href="#oc-list" class="nav-link active" data-toggle="tab">{{ __('Upcoming & Past Classes') }}</a></li>
                    <li class="nav-item"><a href="#oc-new" class="nav-link" data-toggle="tab">{{ __('Schedule Online Class') }}</a></li>
                </ul>
            @endif
            <div class="tab-content">
                <div class="tab-pane fade show active" id="oc-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Session') }}</th>
                                    <th>{{ __('Platform') }}</th>
                                    <th>{{ __('Class / Subject') }}</th>
                                    <th>{{ __('Date & Time (BS)') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Join / Manage') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($onlineClasses as $oc)
                                    <tr>
                                        <td>
                                            <strong>{{ $oc->title }}</strong>
                                            <div class="text-muted font-size-xs">{{ $oc->teacher->name ?? '-' }} @if($oc->meeting_code) | ID: {{ $oc->meeting_code }} @endif</div>
                                        </td>
                                        <td><span class="badge badge-primary text-uppercase">{{ str_replace('_', ' ', $oc->platform) }}</span></td>
                                        <td>{{ $oc->my_class->name ?? '-' }} / {{ $oc->subject->name ?? __('General') }}</td>
                                        <td>{{ $oc->scheduled_date_bs ?: \App\Helpers\NepaliHelper::formatDualDate($oc->scheduled_date_ad) }} {{ $oc->start_time }} ({{ $oc->duration_minutes }}m)</td>
                                        <td><span class="badge badge-{{ $oc->status === 'live' ? 'success' : ($oc->status === 'completed' ? 'secondary' : 'info') }} text-capitalize">{{ $oc->status }}</span></td>
                                        <td>
                                            <a href="{{ $oc->meeting_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success"><i class="icon-video-camera mr-1"></i>{{ __('Join') }}</a>
                                            @if(Qs::userIsTeamSAT())
                                                <form method="POST" action="{{ route('elearning.classes.status', $oc->id) }}" class="d-inline ml-1">
                                                    @csrf
                                                    <select name="status" onchange="this.form.submit()" class="form-control form-control-sm d-inline-block" style="width: auto;">
                                                        <option value="scheduled" {{ $oc->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                                        <option value="live" {{ $oc->status === 'live' ? 'selected' : '' }}>Live</option>
                                                        <option value="completed" {{ $oc->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                                        <option value="cancelled" {{ $oc->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                    </select>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No virtual classes scheduled.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $onlineClasses->links() }}</div>
                </div>

                @if(Qs::userIsTeamSAT())
                    <div class="tab-pane fade" id="oc-new">
                        <form method="POST" action="{{ route('elearning.classes.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-5 form-group"><label for="oc_title">{{ __('Class Title') }} *</label><input id="oc_title" type="text" name="title" class="form-control" required></div>
                                <div class="col-md-3 form-group">
                                    <label for="oc_platform">{{ __('Platform') }}</label>
                                    <select id="oc_platform" name="platform" class="form-control">
                                        <option value="zoom">Zoom</option>
                                        <option value="google_meet">Google Meet</option>
                                        <option value="ms_teams">MS Teams</option>
                                        <option value="jitsi">Jitsi</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group"><label for="oc_url">{{ __('Meeting URL') }} *</label><input id="oc_url" type="url" name="meeting_url" class="form-control" required placeholder="https://zoom.us/j/..."></div>
                                <div class="col-md-4 form-group">
                                    <label for="oc_class">{{ __('Class') }} *</label>
                                    <select id="oc_class" name="my_class_id" class="form-control" required>
                                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="oc_subject">{{ __('Subject') }}</label>
                                    <select id="oc_subject" name="subject_id" class="form-control">
                                        <option value="">{{ __('General') }}</option>
                                        @foreach($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group"><label for="oc_date_bs">{{ __('Date (BS)') }}</label><input id="oc_date_bs" type="text" name="scheduled_date_bs" class="form-control" value="{{ \App\Helpers\NepaliHelper::adToBs(date('Y-m-d')) }}"></div>
                                <div class="col-md-3 form-group"><label for="oc_time">{{ __('Start Time') }} *</label><input id="oc_time" type="text" name="start_time" class="form-control" value="10:00 AM" required></div>
                                <div class="col-md-3 form-group"><label for="oc_dur">{{ __('Duration (min)') }}</label><input id="oc_dur" type="number" name="duration_minutes" class="form-control" value="45"></div>
                                <div class="col-md-3 form-group"><label for="oc_code">{{ __('Meeting Code') }}</label><input id="oc_code" type="text" name="meeting_code" class="form-control"></div>
                                <div class="col-md-3 form-group"><label for="oc_pass">{{ __('Passcode') }}</label><input id="oc_pass" type="text" name="meeting_passcode" class="form-control"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Schedule Online Class') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
