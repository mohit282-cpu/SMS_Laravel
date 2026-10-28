@extends('layouts.master')
@section('page_title', 'View TimeTable')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.15rem;">
                <i class="icon-calendar mr-2" style="color: #FF5700;"></i>{{ $ttr->name }}
            </h2>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="badge badge-primary px-3 py-1 font-weight-semibold mr-2"><i class="icon-windows2 mr-1"></i>{{ $my_class->name }}</span>
                <span class="badge badge-secondary px-3 py-1 font-weight-semibold"><i class="icon-calendar52 mr-1"></i>{{ ($ttr->exam_id) ? 'Exam TimeTable' : 'Class TimeTable' }} ({{ $ttr->year }})</span>
            </div>
        </div>
            <div class="card-body">
                <table class="table table-responsive table-striped">
                    <thead>
                    <tr>
                        <th rowspan="2">Time <i class="icon-arrow-right7 ml-2"></i> <br> Date<i class="icon-arrow-down7 ml-2"></i>
                        </th>
                        @foreach($time_slots as $tms)
                            <th rowspan="2">{{ $tms->time_from }} <br>
                            {{ $tms->time_to }}
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $day)
                            <tr>
                                @if($ttr->exam_id)
                                <td><strong>{{ date('l', strtotime($day)) }} <br> {{ date('d/m/Y', strtotime($day)) }} </strong></td>
                                @else
                                <td><strong>{{ $day }}</strong></td>
                                @endif
                                @foreach($d_time->where('day', $day) as $dt)
                                <td>{{ $dt['subject'] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{--Print Button--}}
                <div class="text-center mt-4">
                    <a target="_blank" href="{{ route('ttr.print', $ttr->id) }}" class="btn btn-danger btn-lg"><i class="icon-printer mr-2"></i> Print Timetable</a>
                </div>
            </div>
        </div>

@endsection