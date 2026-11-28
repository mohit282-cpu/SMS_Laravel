@extends('layouts.master')
@section('page_title', __('Transport & Bus Attendance'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('Vehicle Fleet, Routes & Bus Attendance') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Manage bus routes, driver details, monthly transport fare, pickup stops & boarding attendance') }}</p>
        </div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#tr-routes" class="nav-link active" data-toggle="tab">{{ __('Routes & Assignments') }}</a></li>
                <li class="nav-item"><a href="#tr-att" class="nav-link" data-toggle="tab">{{ __('Bus Boarding Attendance') }}</a></li>
                @if(Qs::userIsTeamSA())
                    <li class="nav-item"><a href="#tr-new" class="nav-link" data-toggle="tab">{{ __('Add Route / Assign Student') }}</a></li>
                @endif
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tr-routes">
                    <h6 class="font-weight-bold">{{ __('Active Bus Routes') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Route Name') }}</th><th>{{ __('Vehicle No.') }}</th><th>{{ __('Driver') }}</th><th>{{ __('Monthly Fee') }}</th><th>{{ __('Students') }}</th></tr></thead>
                            <tbody>
                                @forelse($routes as $rt)
                                    <tr>
                                        <td><strong>{{ $rt->name }}</strong> <div class="text-muted font-size-xs">{{ $rt->name_ne }}</div></td>
                                        <td><code>{{ $rt->vehicle_number ?: '-' }}</code></td>
                                        <td>{{ $rt->driver_name ?: '-' }} <div class="text-muted font-size-xs">{{ $rt->driver_phone }}</div></td>
                                        <td>{{ \App\Helpers\NepaliHelper::formatNpr($rt->monthly_fee) }}</td>
                                        <td><span class="badge badge-info">{{ $rt->assignments_count }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ __('No transport routes defined yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="font-weight-bold">{{ __('Assigned Students') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Route') }}</th><th>{{ __('Pickup Stop') }}</th><th>{{ __('Session') }}</th></tr></thead>
                            <tbody>
                                @forelse($assignments as $as)
                                    <tr>
                                        <td>{{ $as->studentRecord->user->name ?? '-' }}</td>
                                        <td>{{ $as->route->name ?? '-' }}</td>
                                        <td>{{ $as->pickup_point ?: '-' }}</td>
                                        <td>{{ $as->academic_year }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">{{ __('No student transport assignments found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $assignments->links() }}
                </div>

                <div class="tab-pane fade" id="tr-att">
                    @if(Qs::userIsTeamSAT())
                        <form method="POST" action="{{ route('transport.attendance.store') }}" class="mb-4 p-3 bg-light rounded border">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-3 form-group mb-md-0"><label>{{ __('Route') }} *</label><select name="transport_route_id" class="form-control" required>@foreach($routes as $rt)<option value="{{ $rt->id }}">{{ $rt->name }}</option>@endforeach</select></div>
                                <div class="col-md-3 form-group mb-md-0"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Trip') }}</label><select name="trip_type" class="form-control"><option value="pickup">Pickup</option><option value="drop">Drop</option></select></div>
                                <div class="col-md-2 form-group mb-md-0"><label>{{ __('Status') }}</label><select name="status" class="form-control"><option value="boarded">Boarded</option><option value="missed">Missed</option><option value="absent">Absent</option></select></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-block">{{ __('Mark') }}</button></div>
                            </div>
                        </form>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Date (BS)') }}</th><th>{{ __('Route') }}</th><th>{{ __('Student') }}</th><th>{{ __('Trip') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @forelse($busAttendances as $ba)
                                    <tr>
                                        <td>{{ $ba->attendance_date_bs ?: $ba->attendance_date_ad->format('Y-m-d') }}</td>
                                        <td>{{ $ba->route->name ?? '-' }}</td>
                                        <td>{{ $ba->studentRecord->user->name ?? '-' }}</td>
                                        <td class="text-capitalize">{{ $ba->trip_type }}</td>
                                        <td><span class="badge badge-{{ $ba->status === 'boarded' ? 'success' : 'warning' }} text-capitalize">{{ $ba->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ __('No bus boarding logs recorded.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if(Qs::userIsTeamSA())
                    <div class="tab-pane fade" id="tr-new">
                        <form method="POST" action="{{ route('transport.routes.store') }}" class="mb-4 p-3 bg-light rounded border">
                            @csrf
                            <h6 class="font-weight-bold mb-3">{{ __('Add Bus Route') }}</h6>
                            <div class="row">
                                <div class="col-md-4 form-group"><label>{{ __('Route Name') }} *</label><input type="text" name="name" class="form-control" required></div>
                                <div class="col-md-2 form-group"><label>{{ __('Vehicle No') }}</label><input type="text" name="vehicle_number" class="form-control"></div>
                                <div class="col-md-2 form-group"><label>{{ __('Driver Name') }}</label><input type="text" name="driver_name" class="form-control"></div>
                                <div class="col-md-2 form-group"><label>{{ __('Driver Phone') }}</label><input type="text" name="driver_phone" class="form-control"></div>
                                <div class="col-md-2 form-group"><label>{{ __('Monthly Fee') }} *</label><input type="number" step="0.01" name="monthly_fee" class="form-control" value="1500" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Route') }}</button>
                        </form>

                        <form method="POST" action="{{ route('transport.assign.store') }}" class="p-3 bg-light rounded border">
                            @csrf
                            <h6 class="font-weight-bold mb-3">{{ __('Assign Student to Bus Route') }}</h6>
                            <div class="row">
                                <div class="col-md-4 form-group"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->adm_no }} - {{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                                <div class="col-md-4 form-group"><label>{{ __('Route') }} *</label><select name="transport_route_id" class="form-control" required>@foreach($routes as $rt)<option value="{{ $rt->id }}">{{ $rt->name }}</option>@endforeach</select></div>
                                <div class="col-md-4 form-group"><label>{{ __('Pickup Point / Stop') }}</label><input type="text" name="pickup_point" class="form-control"></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Assign Student') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
