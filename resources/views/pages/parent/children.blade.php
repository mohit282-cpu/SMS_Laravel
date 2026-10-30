@extends('layouts.master')
@section('page_title', 'My Children')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">My Children</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @if($students->isEmpty())
                <div class="text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; border-radius: 50%; background: rgba(108, 117, 125, 0.1);">
                        <i class="icon-user-cancel icon-2x text-muted"></i>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-1">{{ __('No Children Currently Linked') }}</h5>
                    <p class="text-muted mb-3" style="max-width: 450px; margin: 0 auto;">
                        {{ __('No students are currently linked to your parent account. If your child is enrolled, please contact the school administration to link your student records.') }}
                    </p>
                    <a href="mailto:admin@smsacademy.edu.np" class="btn btn-outline-primary btn-sm">
                        <i class="icon-envelop mr-1"></i>{{ __('Contact School Administration') }}
                    </a>
                </div>
            @else
                <table class="table datatable-button-html5-columns">
                    <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>ADM_No</th>
                        <th>Class & Section</th>
                        <th>Relationship</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><img class="rounded-circle" style="height: 40px; width: 40px; object-fit: cover;" src="{{ $s->user->photo }}" alt="photo"></td>
                            <td class="font-weight-semibold text-dark">{{ $s->user->name }}</td>
                            <td>{{ $s->adm_no }}</td>
                            <td>{{ ($s->my_class->name ?? '') . ' ' . ($s->section->name ?? '') }}</td>
                            <td>
                                @if($s->parent_relationship)
                                    <span class="badge badge-light border text-dark">{{ $s->parent_relationship }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
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
            @endif
        </div>
    </div>

    {{--Student List Ends--}}

@endsection
