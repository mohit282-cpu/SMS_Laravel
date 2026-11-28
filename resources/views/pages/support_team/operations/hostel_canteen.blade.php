@extends('layouts.master')
@section('page_title', __('Hostel Allocation & Canteen Lunch'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('Hostel Room Allocation & Canteen Lunch Attendance') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Assign dormitory rooms/beds, meal plans & track student lunch attendance logs') }}</p>
        </div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#hc-hostel" class="nav-link active" data-toggle="tab">{{ __('Hostel Room Allocations') }}</a></li>
                <li class="nav-item"><a href="#hc-lunch" class="nav-link" data-toggle="tab">{{ __('Canteen & Lunch Attendance') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="hc-hostel">
                    @if(Qs::userIsTeamSA())
                        <form method="POST" action="{{ route('hostel_canteen.hostel.store') }}" class="mb-4 p-3 bg-light rounded border">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-3 form-group mb-md-0"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Dormitory') }} *</label><select name="dorm_id" class="form-control" required>@foreach($dorms as $dm)<option value="{{ $dm->id }}">{{ $dm->name }}</option>@endforeach</select></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Room No') }} *</label><input type="text" name="room_no" class="form-control" required placeholder="B-104"></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Bed No') }}</label><input type="text" name="bed_no" class="form-control"></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Monthly Fee') }}</label><input type="number" step="0.01" name="monthly_fee" value="6500" class="form-control"></div>
                                <div class="col-md-1"><button type="submit" class="btn btn-primary btn-block">{{ __('Save') }}</button></div>
                            </div>
                        </form>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Dormitory') }}</th><th>{{ __('Room / Bed') }}</th><th>{{ __('Monthly Fee') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @forelse($allocations as $al)
                                    <tr>
                                        <td>{{ $al->studentRecord->user->name ?? '-' }}</td>
                                        <td>{{ $al->dorm->name ?? '-' }}</td>
                                        <td>{{ $al->room_no }} {{ $al->bed_no ? '/ Bed ' . $al->bed_no : '' }}</td>
                                        <td>{{ \App\Helpers\NepaliHelper::formatNpr($al->monthly_fee) }}</td>
                                        <td><span class="badge badge-success text-capitalize">{{ $al->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ __('No hostel allocations yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $allocations->links() }}
                </div>

                <div class="tab-pane fade" id="hc-lunch">
                    @if(Qs::userIsTeamSAT())
                        <form method="POST" action="{{ route('hostel_canteen.lunch.store') }}" class="mb-4 p-3 bg-light rounded border">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-3 form-group mb-md-0"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Meal') }}</label><select name="meal_type" class="form-control"><option value="lunch">Lunch</option><option value="snacks">Snacks</option><option value="breakfast">Breakfast</option></select></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Status') }}</label><select name="status" class="form-control"><option value="taken">Taken</option><option value="absent">Absent</option></select></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Charge (NPR)') }}</label><input type="number" step="0.01" name="unit_charge" value="120" class="form-control"></div>
                                <div class="col-md-3"><button type="submit" class="btn btn-primary btn-block">{{ __('Log Meal Attendance') }}</button></div>
                            </div>
                        </form>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Date (BS)') }}</th><th>{{ __('Student') }}</th><th>{{ __('Meal') }}</th><th>{{ __('Status') }}</th><th>{{ __('Charge') }}</th></tr></thead>
                            <tbody>
                                @forelse($lunchLogs as $ll)
                                    <tr>
                                        <td>{{ $ll->log_date_bs ?: $ll->log_date_ad->format('Y-m-d') }}</td>
                                        <td>{{ $ll->studentRecord->user->name ?? '-' }}</td>
                                        <td class="text-capitalize">{{ $ll->meal_type }}</td>
                                        <td><span class="badge badge-{{ $ll->status === 'taken' ? 'success' : 'secondary' }} text-capitalize">{{ $ll->status }}</span></td>
                                        <td>{{ \App\Helpers\NepaliHelper::formatNpr($ll->unit_charge) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ __('No meal logs recorded.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $lunchLogs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
