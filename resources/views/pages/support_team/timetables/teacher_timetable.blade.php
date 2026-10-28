@extends('layouts.master')
@section('page_title', __('My Teaching Timetable'))
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-calendar mr-2" style="color: #FF5700;"></i>{{ __('My Weekly Teaching Schedule') }}</h2>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        @if($tts->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="icon-calendar-cross icon-2x mb-2 d-block"></i>
                {{ __('No scheduled teaching classes assigned to you in the timetable yet.') }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>{{ __('Day / Date') }}</th>
                            <th>{{ __('Time Slot') }}</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Subject') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tts->sortBy('day') as $t)
                            <tr>
                                <td class="font-weight-bold">{{ $t->exam_date ?? $t->day }}</td>
                                <td><span class="badge badge-light font-size-sm">{{ $t->time_slot->full ?? '-' }}</span></td>
                                <td><span class="badge badge-info">{{ $t->tt_record->my_class->name ?? '-' }}</span></td>
                                <td class="font-weight-semibold">{{ $t->subject->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
