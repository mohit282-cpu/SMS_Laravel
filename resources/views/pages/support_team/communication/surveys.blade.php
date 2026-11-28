@extends('layouts.master')
@section('page_title', __('Institutional Surveys'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('School Surveys & Poll Feedback') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Gather structured feedback from parents, students, and staff with 5-star ratings') }}</p>
        </div>
    </div>

    <div class="row">
        @if(Qs::userIsTeamSA())
            <div class="col-lg-4 mb-4">
                <div class="card sms-card">
                    <div class="card-header"><h6 class="font-weight-bold mb-0">{{ __('Create New Survey') }}</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('surveys.store') }}">
                            @csrf
                            <div class="form-group"><label>{{ __('Survey Title') }} *</label><input type="text" name="title" class="form-control" required></div>
                            <div class="form-group"><label>{{ __('Target Audience') }}</label><select name="target_role" class="form-control"><option value="all">All Users</option><option value="parent">Parents</option><option value="student">Students</option><option value="teacher">Teachers</option></select></div>
                            <div class="form-group"><label>{{ __('Question 1') }} *</label><input type="text" name="question_1" class="form-control" required></div>
                            <div class="form-group"><label>{{ __('Question 2 (Optional)') }}</label><input type="text" name="question_2" class="form-control"></div>
                            <button type="submit" class="btn btn-primary btn-block">{{ __('Publish Survey') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-lg-{{ Qs::userIsTeamSA() ? '8' : '12' }}">
            @forelse($surveys as $sv)
                @php $resp = $myResponses->get($sv->id); @endphp
                <div class="card sms-card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="font-weight-bold mb-0">{{ $sv->title }}</h5>
                            <span class="badge badge-info">{{ $sv->responses_count }} {{ __('responses') }}</span>
                        </div>
                        @if($resp)
                            <div class="alert alert-success mb-0">{{ __('You submitted this survey (Rating:') }} {{ $resp->rating }}/5).</div>
                        @else
                            <form method="POST" action="{{ route('surveys.respond', $sv->id) }}">
                                @csrf
                                <div class="form-group"><label>{{ $sv->question_1 }} *</label><input type="text" name="answer_1" class="form-control" required></div>
                                @if($sv->question_2)<div class="form-group"><label>{{ $sv->question_2 }}</label><input type="text" name="answer_2" class="form-control"></div>@endif
                                <div class="form-row align-items-end">
                                    <div class="col-md-4 form-group mb-md-0">
                                        <label>{{ __('Overall Rating') }}</label>
                                        <select name="rating" class="form-control"><option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Average</option><option value="2">2 - Fair</option><option value="1">1 - Needs Improvement</option></select>
                                    </div>
                                    <div class="col-md-8"><button type="submit" class="btn btn-primary">{{ __('Submit Response') }}</button></div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card sms-card"><div class="card-body text-center text-muted py-4">{{ __('No active surveys available.') }}</div></div>
            @endforelse
            {{ $surveys->links() }}
        </div>
    </div>
@endsection
