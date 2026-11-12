@extends('layouts.master')
@section('page_title', __('My Children'))
@section('content')

    <div class="card sms-card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">{{ __('My Children') }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @if($students->isEmpty())
                <x-empty-state
                    :title="__('No Children Currently Linked')"
                    :message="__('No students are currently linked to your parent account. If your child is enrolled, please contact the school administration to link your student records.')"
                    icon="icon-user-cancel"
                    actionUrl="mailto:admin@smsacademy.edu.np"
                    :actionLabel="__('Contact School Administration')"
                />
            @else
                <div class="table-responsive">
                    <table class="table datatable-button-html5-columns table-hover">
                        <thead>
                        <tr>
                            <th>{{ __('S/N') }}</th>
                            <th>{{ __('Photo') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('ADM No') }}</th>
                            <th>{{ __('Class & Section') }}</th>
                            <th>{{ __('Relationship') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($students as $s)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><img class="rounded-circle" style="height: 40px; width: 40px; object-fit: cover;" src="{{ $s->user->photo }}" alt="{{ $s->user->name }}"></td>
                                <td class="font-weight-semibold text-dark">{{ $s->user->name }}</td>
                                <td>{{ $s->adm_no }}</td>
                                <td>{{ ($s->my_class->name ?? '') . ' ' . ($s->section->name ?? '') }}</td>
                                <td>
                                    @if($s->parent_relationship)
                                        <span class="badge badge-light border text-dark">{{ $s->parent_relationship }}</span>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                                        <a href="{{ route('payments.invoice', [Qs::hash($s->user->id), Qs::getCurrentSession()]) }}" class="btn btn-primary btn-sm">
                                            <i class="icon-cash mr-1" aria-hidden="true"></i> {{ __('Fees & Payments') }}
                                        </a>
                                        <div class="dropdown">
                                            <a href="#" class="btn btn-light btn-sm sms-btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('More actions for') }} {{ $s->user->name }}">
                                                <i class="icon-menu9" aria-hidden="true"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a href="{{ route('students.show', Qs::hash($s->id)) }}" class="dropdown-item"><i class="icon-eye mr-2" aria-hidden="true"></i> {{ __('View Profile') }}</a>
                                                <a target="_blank" href="{{ route('marks.year_selector', Qs::hash($s->user->id)) }}" class="dropdown-item"><i class="icon-check mr-2" aria-hidden="true"></i> {{ __('Marksheet') }}</a>
                                                <a href="{{ route('payments.invoice', [Qs::hash($s->user->id), Qs::getCurrentSession()]) }}" class="dropdown-item"><i class="icon-cash mr-2" aria-hidden="true"></i> {{ __('Fees & Payments') }}</a>
                                                <a href="{{ route('tt.student_timetable', Qs::hash($s->user->id)) }}" class="dropdown-item"><i class="icon-calendar mr-2" aria-hidden="true"></i> {{ __('Timetable') }}</a>
                                                <a href="{{ route('attendance.student_history', $s->user_id) }}" class="dropdown-item"><i class="icon-alarm mr-2" aria-hidden="true"></i> {{ __('Attendance') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

@endsection
