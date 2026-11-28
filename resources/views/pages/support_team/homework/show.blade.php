@extends('layouts.master')
@section('page_title', $homework->title)
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title font-weight-bold mb-0">{{ $homework->title }}</h5>
            <a href="{{ route('homework.index') }}" class="btn btn-sm btn-light border">{{ __('Back to List') }}</a>
        </div>
        <div class="card-body">
            <div class="mb-3 d-flex flex-wrap gap-2">
                <span class="badge badge-info mr-2">{{ ucfirst(str_replace('_', ' ', $homework->homework_type)) }}</span>
                <span class="badge badge-light border mr-2">{{ $homework->my_class->name ?? '-' }} {{ $homework->section ? '(' . $homework->section->name . ')' : '' }}</span>
                <span class="badge badge-light border mr-2">{{ $homework->subject->name ?? __('General') }}</span>
                <span class="badge badge-primary mr-2">{{ __('Max Marks') }}: {{ $homework->max_marks }}</span>
                <span class="badge badge-secondary">{{ __('Due') }}: {{ $homework->due_date_bs ?: ($homework->due_date_ad ? $homework->due_date_ad->format('Y-m-d') : __('None')) }}</span>
            </div>

            <p class="mb-3" style="white-space: pre-line;">{{ $homework->description }}</p>

            @if($homework->reference_link)
                <p><a href="{{ $homework->reference_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info"><i class="icon-link mr-1"></i>{{ __('Open Reference Resource') }}</a></p>
            @endif
            @if($homework->attachment_path)
                <p><a href="{{ route('homework.download', $homework->id) }}" class="btn btn-sm btn-outline-primary"><i class="icon-download mr-1"></i>{{ __('Download Attachment') }}</a></p>
            @endif
        </div>
    </div>

    @if(Auth::user()->user_type === 'student')
        <div class="card sms-card mb-4">
            <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('My Submission') }}</h6></div>
            <div class="card-body">
                @if($mySubmission)
                    <div class="alert alert-info">
                        <strong>{{ __('Status') }}:</strong> {{ ucfirst($mySubmission->status) }}
                        @if(!is_null($mySubmission->marks_obtained))
                            | <strong>{{ __('Marks') }}:</strong> {{ $mySubmission->marks_obtained }} / {{ $homework->max_marks }}
                        @endif
                        @if($mySubmission->teacher_remarks)
                            <div class="mt-2"><strong>{{ __('Teacher Remarks / Annotation') }}:</strong> {{ $mySubmission->teacher_remarks }}</div>
                        @endif
                    </div>
                @endif
                <form method="POST" action="{{ route('homework.submit', $homework->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="submission_text">{{ __('Answer / Notes') }}</label>
                        <textarea id="submission_text" name="submission_text" rows="4" class="form-control">{{ old('submission_text', $mySubmission->submission_text ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="submission_attachment">{{ __('Upload Work File (Optional)') }}</label>
                        <input id="submission_attachment" type="file" name="attachment" class="form-control-file">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ $mySubmission ? __('Update Submission') : __('Submit Homework') }}</button>
                </form>
            </div>
        </div>
    @endif

    <div class="card sms-card">
        <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Submissions & Evaluation') }}</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Answer') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Marks / Remarks') }}</th>
                            @if(Qs::userIsTeamSAT()) <th>{{ __('Evaluate') }}</th> @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visibleSubmissions as $sub)
                            <tr>
                                <td>{{ $sub->studentRecord->user->name ?? '-' }} <div class="text-muted font-size-xs">{{ $sub->studentRecord->adm_no ?? '' }}</div></td>
                                <td>{{ $sub->submission_text ?: '-' }}</td>
                                <td><span class="badge badge-primary text-capitalize">{{ $sub->status }}</span></td>
                                <td>
                                    {{ is_null($sub->marks_obtained) ? '-' : $sub->marks_obtained . ' / ' . $homework->max_marks }}
                                    @if($sub->teacher_remarks) <div class="text-muted font-size-xs">{{ $sub->teacher_remarks }}</div> @endif
                                </td>
                                @if(Qs::userIsTeamSAT())
                                    <td>
                                        <form method="POST" action="{{ route('homework.evaluate', $sub->id) }}" class="form-inline" style="gap: 0.35rem;">
                                            @csrf
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="evaluated" {{ $sub->status === 'evaluated' ? 'selected' : '' }}>{{ __('Evaluated') }}</option>
                                                <option value="incomplete" {{ $sub->status === 'incomplete' ? 'selected' : '' }}>{{ __('Incomplete') }}</option>
                                                <option value="submitted" {{ $sub->status === 'submitted' ? 'selected' : '' }}>{{ __('Submitted') }}</option>
                                            </select>
                                            <input type="number" step="0.5" min="0" max="{{ $homework->max_marks }}" name="marks_obtained" value="{{ $sub->marks_obtained }}" class="form-control form-control-sm" style="width: 80px;" placeholder="{{ __('Marks') }}">
                                            <input type="text" name="teacher_remarks" value="{{ $sub->teacher_remarks }}" class="form-control form-control-sm" placeholder="{{ __('Remarks') }}">
                                            <button type="submit" class="btn btn-sm btn-success">{{ __('Save') }}</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No submissions yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
