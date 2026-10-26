@extends('layouts.master')
@section('page_title', 'Tabulation Sheet')
@section('content')
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title"><i class="icon-books mr-2"></i> Tabulation Sheet</h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
        <form method="post" action="{{ route('marks.tabulation_select') }}">
                    @csrf
                    <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="exam_id" class="col-form-label font-weight-bold">Exam:</label>
                                            <select required id="exam_id" name="exam_id" class="form-control select" data-placeholder="Select Exam">
                                                @foreach($exams as $exm)
                                                    <option {{ ($selected && $exam_id == $exm->id) ? 'selected' : '' }} value="{{ $exm->id }}">{{ $exm->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="my_class_id" class="col-form-label font-weight-bold">Class:</label>
                                            <select onchange="getClassSections(this.value)" required id="my_class_id" name="my_class_id" class="form-control select" data-placeholder="Select Class">
                                                <option value=""></option>
                                                @foreach($my_classes as $c)
                                                    <option {{ ($selected && $my_class_id == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="section_id" class="col-form-label font-weight-bold">Section:</label>
                                <select required id="section_id" name="section_id" data-placeholder="Select Class First" class="form-control select">
                                    @if($selected)
                                        @foreach($sections->where('my_class_id', $my_class_id) as $s)
                                            <option {{ $section_id == $s->id ? 'selected' : '' }} value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>


                        <div class="col-md-2 mt-4">
                            <div class="text-right mt-1">
                                <button type="submit" class="btn btn-primary">View Sheet <i class="icon-paperplane ml-2"></i></button>
                            </div>
                        </div>

                    </div>

                </form>
        </div>
    </div>

    {{--if Selction Has Been Made --}}

    @if($selected)
        <div class="card">
            <div class="card-header">
                <h6 class="card-title font-weight-bold">Tabulation Sheet for {{ $my_class->name.' '.$section->name.' - '.$ex->name.' ('.$year.')' }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-responsive table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>NAMES_OF_STUDENTS_IN_CLASS</th>
                       @foreach($subjects as $sub)
                       <th title="{{ $sub->name }}" rowspan="2">{{ strtoupper($sub->slug ?: $sub->name) }}</th>
                       @endforeach
                        {{--@if($ex->term == 3)
                        <th>1ST TERM TOTAL</th>
                        <th>2ND TERM TOTAL</th>
                        <th>3RD TERM TOTAL</th>
                        <th style="color: darkred">CUM Total</th>
                        <th style="color: darkblue">CUM Average</th>
                        @endif--}}
                        <th style="color: darkred">{{ __('Total') }}</th>
                        <th style="color: darkblue">{{ __('Percentage') }}</th>
                        <th style="color: purple">{{ __('GPA') }}</th>
                        <th style="color: teal">{{ __('Grade') }}</th>
                        <th style="color: darkgreen">{{ __('Result') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $s)
                        @php $record = $exr->where('student_id', $s->user_id)->first(); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td style="text-align: left">{{ $s->user->name }}</td>
                            @foreach($subjects as $sub)
                            <td>{{ $marks->where('student_id', $s->user_id)->where('subject_id', $sub->id)->first()->$tex ?? '-' ?: '-' }}</td>
                            @endforeach

                            <td style="color: darkred">{{ $record->total ?? '-' }}</td>
                            <td style="color: darkblue">{{ $record && $record->ave ? number_format($record->ave, 2).'%' : '-' }}</td>
                            <td style="color: purple; font-weight: bold;">{{ $record && $record->gpa !== null ? number_format($record->gpa, 2) : '-' }}</td>
                            <td style="color: teal; font-weight: bold;">{{ $record->grade ?? '-' }}</td>
                            <td style="font-weight: bold; color: {{ ($record->result_status ?? '') === 'PASS' ? '#28a745' : (($record->result_status ?? '') === 'FAIL' ? '#dc3545' : '#6c757d') }}">
                                {{ $record->result_status ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{--Print Button--}}
                <div class="text-center mt-4">
                    <a target="_blank" href="{{  route('marks.print_tabulation', [$exam_id, $my_class_id, $section_id]) }}" class="btn btn-danger btn-lg"><i class="icon-printer mr-2"></i> Print Tabulation Sheet</a>
                </div>
            </div>
        </div>
    @endif
@endsection
