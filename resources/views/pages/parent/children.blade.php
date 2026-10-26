@extends('layouts.master')
@section('page_title', 'My Children')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">My Children</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <table class="table datatable-button-html5-columns">
                <thead>
                <tr>
                    <th>S/N</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>ADM_No</th>
                    <th>Section</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($students as $s)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><img class="rounded-circle" style="height: 40px; width: 40px;" src="{{ $s->user->photo }}" alt="photo"></td>
                        <td>{{ $s->user->name }}</td>
                        <td>{{ $s->adm_no }}</td>
                        <td>{{ $s->my_class->name.' '.$s->section->name }}</td>
                        <td>{{ $s->user->email }}</td>
                        <td class="text-center">
                            <div class="list-icons">
                                <div class="dropdown">
                                    <a href="#" class="list-icons-item" data-toggle="dropdown">
                                        <i class="icon-menu9"></i>
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-left">
                                        <a href="{{ route('students.show', Qs::hash($s->id)) }}" class="dropdown-item"><i class="icon-eye"></i> {{ __('View Profile') }}</a>
                                        <a target="_blank" href="{{ route('marks.year_selector', Qs::hash($s->user->id)) }}" class="dropdown-item"><i class="icon-check"></i> {{ __('Marksheet') }}</a>
                                        <a href="{{ route('payments.invoice', [Qs::hash($s->user->id), Qs::getCurrentSession()]) }}" class="dropdown-item"><i class="icon-cash"></i> {{ __('Fees & Payments') }}</a>
                                        <a href="{{ route('tt.student_timetable', Qs::hash($s->user->id)) }}" class="dropdown-item"><i class="icon-calendar"></i> {{ __('Timetable') }}</a>
                                        <a href="{{ route('attendance.student_history', $s->user_id) }}" class="dropdown-item"><i class="icon-alarm"></i> {{ __('Attendance') }}</a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>
    </div>

    {{--Student List Ends--}}

@endsection
