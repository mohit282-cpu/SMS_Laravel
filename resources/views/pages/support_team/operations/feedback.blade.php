@extends('layouts.master')
@section('page_title', __('Feedback, Complaints & Cases'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('Feedback, Grievances & Disciplinary Case Desk') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Parent/student feedback, appreciation, complaints & school resolution tracking') }}</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Open / In Progress') }}</span><h3 class="font-weight-bold text-warning mb-0">{{ $openCount }}</h3></div></div></div>
        <div class="col-md-6"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Resolved / Closed') }}</span><h3 class="font-weight-bold text-success mb-0">{{ $resolvedCount }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#fb-list" class="nav-link active" data-toggle="tab">{{ __('All Tickets') }}</a></li>
                <li class="nav-item"><a href="#fb-new" class="nav-link" data-toggle="tab">{{ __('Submit Feedback / Complaint') }}</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="fb-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Subject') }}</th><th>{{ __('Type / Priority') }}</th><th>{{ __('Submitted By') }}</th><th>{{ __('Status & Notes') }}</th>@if(Qs::userIsTeamSA())<th>{{ __('Manage') }}</th>@endif</tr></thead>
                            <tbody>
                                @forelse($entries as $en)
                                    <tr>
                                        <td><strong>{{ $en->subject }}</strong> <div class="text-muted font-size-xs">{{ \Illuminate\Support\Str::limit($en->description, 90) }}</div></td>
                                        <td><span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $en->entry_type) }}</span> <span class="badge badge-light border text-capitalize">{{ $en->priority }}</span></td>
                                        <td>{{ $en->submitter->name ?? '-' }}</td>
                                        <td><span class="badge badge-{{ in_array($en->status, ['resolved','closed']) ? 'success' : 'warning' }} text-capitalize">{{ str_replace('_', ' ', $en->status) }}</span> @if($en->resolution_notes)<div class="text-muted font-size-xs">{{ $en->resolution_notes }}</div>@endif</td>
                                        @if(Qs::userIsTeamSA())
                                            <td>
                                                <form method="POST" action="{{ route('feedback.status', $en->id) }}" class="form-inline" style="gap:0.35rem;">
                                                    @csrf
                                                    <select name="status" class="form-control form-control-sm">
                                                        <option value="open" {{ $en->status === 'open' ? 'selected' : '' }}>Open</option>
                                                        <option value="in_progress" {{ $en->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                        <option value="resolved" {{ $en->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                                        <option value="closed" {{ $en->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                                    </select>
                                                    <input type="text" name="resolution_notes" value="{{ $en->resolution_notes }}" class="form-control form-control-sm" placeholder="{{ __('Resolution note') }}">
                                                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No feedback or complaint tickets recorded.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $entries->links() }}
                </div>
                <div class="tab-pane fade" id="fb-new">
                    <form method="POST" action="{{ route('feedback.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('Type') }}</label><select name="entry_type" class="form-control"><option value="feedback">Feedback</option><option value="suggestion">Suggestion</option><option value="appreciation">Appreciation</option><option value="complaint">Complaint</option><option value="discipline_case">Disciplinary Case</option></select></div>
                            <div class="col-md-4 form-group"><label>{{ __('Priority') }}</label><select name="priority" class="form-control"><option value="medium">Medium</option><option value="low">Low</option><option value="high">High</option></select></div>
                            <div class="col-md-4 form-group"><label>{{ __('Related Student (Optional)') }}</label><select name="student_record_id" class="form-control"><option value="">{{ __('Optional') }}</option>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                            <div class="col-md-12 form-group"><label>{{ __('Subject') }} *</label><input type="text" name="subject" class="form-control" required></div>
                            <div class="col-md-12 form-group"><label>{{ __('Details') }} *</label><textarea name="description" rows="4" class="form-control" required></textarea></div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Submit Ticket') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
