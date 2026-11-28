@extends('layouts.master')
@section('page_title', __('Objective Exams & Online Quizzes'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('Objective Exams & Auto-Graded Quizzes') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Create MCQ assessments, auto-grade student submissions, and analyze score percentages') }}</p>
        </div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            @if(Qs::userIsTeamSAT())
                <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                    <li class="nav-item"><a href="#qz-list" class="nav-link active" data-toggle="tab">{{ __('All Quizzes') }}</a></li>
                    <li class="nav-item"><a href="#qz-new" class="nav-link" data-toggle="tab">{{ __('Create New Quiz') }}</a></li>
                </ul>
            @endif
            <div class="tab-content">
                <div class="tab-pane fade show active" id="qz-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Quiz Title') }}</th>
                                    <th>{{ __('Class / Subject') }}</th>
                                    <th>{{ __('Questions') }}</th>
                                    <th>{{ __('Pass %') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quizzes as $qz)
                                    <tr>
                                        <td><strong>{{ $qz->title }}</strong><div class="text-muted font-size-xs">{{ $qz->duration_minutes }} min | {{ $qz->attempts_count }} attempts</div></td>
                                        <td>{{ $qz->my_class->name ?? '-' }} / {{ $qz->subject->name ?? __('General') }}</td>
                                        <td><span class="badge badge-info">{{ $qz->questions_count }}</span></td>
                                        <td>{{ $qz->pass_marks }}%</td>
                                        <td><span class="badge badge-{{ $qz->is_published ? 'success' : 'secondary' }}">{{ $qz->is_published ? __('Published') : __('Draft') }}</span></td>
                                        <td>
                                            <a href="{{ route('elearning.quizzes.show', $qz->id) }}" class="btn btn-sm btn-primary">{{ Auth::user()->user_type === 'student' ? __('Take / View Quiz') : __('Manage Questions') }}</a>
                                            @if(Qs::userIsTeamSAT())
                                                <form method="POST" action="{{ route('elearning.quizzes.toggle', $qz->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $qz->is_published ? __('Unpublish') : __('Publish') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No quizzes available.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $quizzes->links() }}</div>
                </div>

                @if(Qs::userIsTeamSAT())
                    <div class="tab-pane fade" id="qz-new">
                        <form method="POST" action="{{ route('elearning.quizzes.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group"><label for="qz_title">{{ __('Quiz Title') }} *</label><input id="qz_title" type="text" name="title" class="form-control" required></div>
                                <div class="col-md-3 form-group">
                                    <label for="qz_class">{{ __('Class') }} *</label>
                                    <select id="qz_class" name="my_class_id" class="form-control" required>
                                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="qz_sub">{{ __('Subject') }}</label>
                                    <select id="qz_sub" name="subject_id" class="form-control">
                                        <option value="">{{ __('General') }}</option>
                                        @foreach($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group"><label for="qz_dur">{{ __('Duration (Minutes)') }}</label><input id="qz_dur" type="number" name="duration_minutes" class="form-control" value="30"></div>
                                <div class="col-md-4 form-group"><label for="qz_pass">{{ __('Pass Percentage (%)') }}</label><input id="qz_pass" type="number" name="pass_marks" class="form-control" value="40"></div>
                                <div class="col-md-4 form-group"><label for="qz_pub">{{ __('Publish Immediately?') }}</label><select id="qz_pub" name="is_published" class="form-control"><option value="0">{{ __('Draft') }}</option><option value="1">{{ __('Published') }}</option></select></div>
                                <div class="col-md-12 form-group"><label for="qz_desc">{{ __('Instructions') }}</label><textarea id="qz_desc" name="description" rows="2" class="form-control"></textarea></div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Create Quiz & Add Questions') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
