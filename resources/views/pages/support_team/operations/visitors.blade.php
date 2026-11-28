@extends('layouts.master')
@section('page_title', __('Front Office & Visitor Register'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('Reception & Visitor Gate Register') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Track parent visits, guests, vendor check-ins/check-outs with Bikram Sambat timestamps') }}</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Currently Checked In') }}</span><h3 class="font-weight-bold text-primary mb-0">{{ $activeInside }}</h3></div></div></div>
        <div class="col-md-6"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Recorded Parent Visits') }}</span><h3 class="font-weight-bold text-success mb-0">{{ $parentVisits }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#vis-log" class="nav-link active" data-toggle="tab">{{ __('Visitor Log') }}</a></li>
                <li class="nav-item"><a href="#vis-new" class="nav-link" data-toggle="tab">{{ __('Log New Visitor') }}</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="vis-log">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Visitor') }}</th><th>{{ __('Type / Student') }}</th><th>{{ __('Purpose & Meeting') }}</th><th>{{ __('Date (BS) & Time') }}</th><th>{{ __('Check Out') }}</th></tr></thead>
                            <tbody>
                                @forelse($visitors as $v)
                                    <tr>
                                        <td><strong>{{ $v->visitor_name }}</strong> <div class="text-muted font-size-xs">{{ $v->phone ?: '-' }} @if($v->id_card_no) | ID: {{ $v->id_card_no }} @endif</div></td>
                                        <td><span class="badge badge-info text-capitalize">{{ $v->visitor_type }}</span> @if($v->studentRecord)<div class="text-muted font-size-xs">{{ $v->studentRecord->user->name ?? '' }}</div>@endif</td>
                                        <td>{{ $v->purpose }} @if($v->person_to_meet)<div class="text-muted font-size-xs">To meet: {{ $v->person_to_meet }}</div>@endif</td>
                                        <td>{{ $v->visit_date_bs ?: $v->visit_date_ad->format('Y-m-d') }} ({{ $v->check_in_time }})</td>
                                        <td>
                                            @if($v->check_out_time)
                                                <span class="badge badge-secondary">{{ __('Out') }}: {{ $v->check_out_time }}</span>
                                            @else
                                                <form method="POST" action="{{ route('visitors.checkout', $v->id) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Mark Check-Out') }}</button></form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No visitors logged yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $visitors->links() }}
                </div>
                <div class="tab-pane fade" id="vis-new">
                    <form method="POST" action="{{ route('visitors.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('Visitor Name') }} *</label><input type="text" name="visitor_name" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('Phone') }}</label><input type="text" name="phone" class="form-control"></div>
                            <div class="col-md-4 form-group"><label>{{ __('Visitor Type') }}</label><select name="visitor_type" class="form-control"><option value="parent">Parent / Guardian</option><option value="guest">Guest</option><option value="vendor">Vendor</option><option value="official">Education Official</option><option value="other">Other</option></select></div>
                            <div class="col-md-4 form-group"><label>{{ __('Related Student (Optional)') }}</label><select name="student_record_id" class="form-control"><option value="">{{ __('None') }}</option>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                            <div class="col-md-4 form-group"><label>{{ __('Person to Meet') }}</label><input type="text" name="person_to_meet" class="form-control"></div>
                            <div class="col-md-4 form-group"><label>{{ __('ID Proof / Pass No') }}</label><input type="text" name="id_card_no" class="form-control"></div>
                            <div class="col-md-12 form-group"><label>{{ __('Visit Purpose') }} *</label><input type="text" name="purpose" class="form-control" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Check-In Visitor') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
