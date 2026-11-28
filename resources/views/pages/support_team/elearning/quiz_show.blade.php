@extends('layouts.master')
@section('page_title', $quiz->title)
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title font-weight-bold mb-0">{{ $quiz->title }}</h5>
            <a href="{{ route('elearning.quizzes') }}" class="btn btn-sm btn-light border">{{ __('Back to Quizzes') }}</a>
        </div>
        <div class="card-body">
            <p class="text-muted">{{ $quiz->description ?: __('Objective assessment') }} — <strong>{{ $quiz->questions->count() }}</strong> {{ __('questions') }} | {{ $quiz->duration_minutes }} min | {{ __('Pass') }}: {{ $quiz->pass_marks }}%</p>

            @if(Auth::user()->user_type === 'student' && $myAttempt)
                <div class="alert alert-{{ $myAttempt->passed ? 'success' : 'warning' }}">
                    <strong>{{ __('Submitted Result') }}:</strong> {{ $myAttempt->score }} / {{ $myAttempt->total_marks }} ({{ $myAttempt->percentage }}%) — <strong>{{ $myAttempt->passed ? __('PASSED') : __('NEEDS REVIEW') }}</strong>
                </div>
            @endif

            @if(Auth::user()->user_type === 'student' && $quiz->questions->isNotEmpty())
                <form method="POST" action="{{ route('elearning.quizzes.attempt', $quiz->id) }}">
                    @csrf
                    @foreach($quiz->questions as $idx => $q)
                        <div class="p-3 mb-3 border rounded bg-light">
                            <p class="font-weight-bold mb-2">Q{{ $idx + 1 }}. {{ $q->question_text }} <span class="badge badge-light border">{{ $q->marks }} pt</span></p>
                            @foreach(['a', 'b', 'c', 'd'] as $opt)
                                @php $val = $q->{'option_' . $opt}; @endphp
                                @if($val)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answers[{{ $q->id }}]" id="q_{{ $q->id }}_{{ $opt }}" value="{{ $opt }}">
                                        <label class="form-check-label" for="q_{{ $q->id }}_{{ $opt }}">{{ strtoupper($opt) }}. {{ $val }}</label>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary">{{ __('Submit Quiz Answers') }}</button>
                </form>
            @endif
        </div>
    </div>

    @if(Qs::userIsTeamSAT())
        <div class="card sms-card mb-4">
            <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Add MCQ Question') }}</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('elearning.quizzes.questions.add', $quiz->id) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-12 form-group"><label for="q_text">{{ __('Question') }} *</label><textarea id="q_text" name="question_text" rows="2" class="form-control" required></textarea></div>
                        <div class="col-md-6 form-group"><label for="opt_a">{{ __('Option A') }} *</label><input id="opt_a" type="text" name="option_a" class="form-control" required></div>
                        <div class="col-md-6 form-group"><label for="opt_b">{{ __('Option B') }} *</label><input id="opt_b" type="text" name="option_b" class="form-control" required></div>
                        <div class="col-md-6 form-group"><label for="opt_c">{{ __('Option C') }}</label><input id="opt_c" type="text" name="option_c" class="form-control"></div>
                        <div class="col-md-6 form-group"><label for="opt_d">{{ __('Option D') }}</label><input id="opt_d" type="text" name="option_d" class="form-control"></div>
                        <div class="col-md-4 form-group">
                            <label for="correct_opt">{{ __('Correct Option') }} *</label>
                            <select id="correct_opt" name="correct_option" class="form-control" required>
                                <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group"><label for="q_marks">{{ __('Marks') }}</label><input id="q_marks" type="number" name="marks" value="1" class="form-control" min="1"></div>
                        <div class="col-md-4 form-group"><label for="q_exp">{{ __('Explanation') }}</label><input id="q_exp" type="text" name="explanation" class="form-control"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Add Question') }}</button>
                </form>

                <hr>
                <h6>{{ __('Configured Questions') }} ({{ $quiz->questions->count() }})</h6>
                <ul class="list-group">
                    @foreach($quiz->questions as $idx => $q)
                        <li class="list-group-item">
                            <strong>Q{{ $idx + 1 }}:</strong> {{ $q->question_text }}
                            <span class="badge badge-success ml-2">{{ __('Answer') }}: {{ strtoupper($q->correct_option) }} ({{ $q->marks }}m)</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card sms-card">
        <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Student Attempts & Scores') }}</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Score') }}</th><th>{{ __('Percentage') }}</th><th>{{ __('Outcome') }}</th></tr></thead>
                    <tbody>
                        @forelse($visibleAttempts as $att)
                            <tr>
                                <td>{{ $att->studentRecord->user->name ?? '-' }}</td>
                                <td>{{ $att->score }} / {{ $att->total_marks }}</td>
                                <td>{{ $att->percentage }}%</td>
                                <td><span class="badge badge-{{ $att->passed ? 'success' : 'danger' }}">{{ $att->passed ? __('Passed') : __('Failed') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ __('No quiz attempts yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
