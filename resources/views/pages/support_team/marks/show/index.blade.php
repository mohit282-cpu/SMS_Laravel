@extends('layouts.master')
@section('page_title', 'Student Marksheet')
@section('content')

    <div class="card">
        <div class="card-header text-center">
            <h2 class="card-title font-weight-bold">Student Marksheet: {{ $sr->user->name.' ('.$my_class->name.' '.$my_class->section->first()->name.')' }}</h2>
        </div>
    </div>

    @foreach($exams as $ex)
        @foreach($exam_records->where('exam_id', $ex->id) as $exr)

                <div class="card">
                    <div class="card-header header-elements-inline">
                        <h3 class="card-title font-weight-bold m-0">{{ $ex->name.' - '.$ex->year }}</h3>
                        {!! Qs::getPanelOptions() !!}
                    </div>

                    <div class="card-body collapse">

                        {{--Sheet Table--}}
                        @include('pages.support_team.marks.show.sheet')

                        {{--Print & Download PDF Buttons--}}
                        <div class="text-center mt-3">
                            <a target="_blank" href="{{ route('marks.print', [Qs::hash($student_id), $ex->id, $year]) }}" class="btn btn-secondary btn-lg mr-2"><i class="icon-printer mr-2"></i>{{ __('Print Marksheet') }}</a>
                            <a href="{{ route('marks.pdf', [Qs::hash($student_id), $ex->id, $year]) }}" class="btn btn-danger btn-lg"><i class="icon-file-pdf mr-2"></i>{{ __('Download PDF') }}</a>
                        </div>

                    </div>

                </div>

            {{--    EXAM COMMENTS   --}}
            @include('pages.support_team.marks.show.comments')

            {{-- SKILL RATING --}}
            @include('pages.support_team.marks.show.skills')

        @endforeach
    @endforeach

@endsection
