@extends('layouts.master')
@section('page_title', __('Accounting, Vouchers & Day Book'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="font-weight-bold mb-1 text-dark">{{ __('Accounting Ledger, Vouchers & Concessions') }}</h3>
                <p class="text-muted mb-0 font-size-sm">{{ __('Double-entry Day Book vouchers, Cash/Bank balance summary, Fiscal Years & Student Scholarships') }}</p>
            </div>
            <span class="badge badge-primary px-3 py-2">{{ __('Active Fiscal Year') }}: {{ $activeFiscalYear->name ?? __('Default') }}</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Total Income') }}</span><h3 class="font-weight-bold text-success mb-0">{{ \App\Helpers\NepaliHelper::formatNpr($totalIncome) }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Total Expense') }}</span><h3 class="font-weight-bold text-danger mb-0">{{ \App\Helpers\NepaliHelper::formatNpr($totalExpense) }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Net Ledger Balance') }}</span><h3 class="font-weight-bold text-primary mb-0">{{ \App\Helpers\NepaliHelper::formatNpr($netBalance) }}</h3></div></div></div>
        <div class="col-md-3"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Cash in Hand') }}</span><h3 class="font-weight-bold text-dark mb-0">{{ \App\Helpers\NepaliHelper::formatNpr($cashBalance) }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item"><a href="#acc-daybook" class="nav-link active" data-toggle="tab">{{ __('Day Book Ledger') }}</a></li>
                <li class="nav-item"><a href="#acc-voucher" class="nav-link" data-toggle="tab">{{ __('New Accounting Voucher') }}</a></li>
                <li class="nav-item"><a href="#acc-concessions" class="nav-link" data-toggle="tab">{{ __('Scholarships & Discounts') }}</a></li>
                <li class="nav-item"><a href="#acc-fy" class="nav-link" data-toggle="tab">{{ __('Fiscal Years') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="acc-daybook">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Date (BS)') }}</th><th>{{ __('Voucher / Type') }}</th><th>{{ __('Category / Party') }}</th><th>{{ __('Account') }}</th><th>{{ __('Amount') }}</th></tr></thead>
                            <tbody>
                                @forelse($entries as $en)
                                    <tr>
                                        <td>{{ $en->entry_date_bs ?: $en->entry_date_ad->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $en->entry_type === 'income' ? 'success' : 'danger' }} text-uppercase">{{ $en->entry_type }}</span>
                                            <span class="badge badge-light border text-capitalize">{{ $en->voucher_type ?? 'voucher' }}</span>
                                        </td>
                                        <td><strong>{{ $en->category }}</strong> <div class="text-muted font-size-xs">{{ $en->party_name ?: '' }} {{ $en->description }}</div></td>
                                        <td class="text-capitalize">{{ $en->account_head }} @if($en->reference_no)<div class="text-muted font-size-xs">#{{ $en->reference_no }}</div>@endif</td>
                                        <td class="font-weight-bold {{ $en->entry_type === 'income' ? 'text-success' : 'text-danger' }}">{{ \App\Helpers\NepaliHelper::formatNpr($en->amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No accounting ledger vouchers recorded yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $entries->links() }}
                </div>

                <div class="tab-pane fade" id="acc-voucher">
                    <form method="POST" action="{{ route('accounting.ledger.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label>{{ __('Entry Type') }} *</label>
                                <select name="entry_type" class="form-control" required><option value="income">Income</option><option value="expense">Expense</option></select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>{{ __('Voucher Format') }}</label>
                                <select name="voucher_type" class="form-control">
                                    <option value="receipt">Receipt</option><option value="payment">Payment</option><option value="journal">Journal</option><option value="contra">Contra</option><option value="purchase">Purchase</option><option value="sales">Sales</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>{{ __('Account Head') }} *</label>
                                <select name="account_head" class="form-control"><option value="cash">Cash</option><option value="bank">Bank</option></select>
                            </div>
                            <div class="col-md-3 form-group"><label>{{ __('Amount (NPR)') }} *</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('Category / Ledger Head') }} *</label><input type="text" name="category" class="form-control" required placeholder="Stationery / Maintenance / Fee"></div>
                            <div class="col-md-4 form-group"><label>{{ __('Party / Payee Name') }}</label><input type="text" name="party_name" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ __('Ref / Bill No') }}</label><input type="text" name="reference_no" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ __('Date (BS)') }}</label><input type="text" name="entry_date_bs" class="form-control" value="{{ \App\Helpers\NepaliHelper::adToBs(date('Y-m-d')) }}"></div>
                            <div class="col-md-12 form-group"><label>{{ __('Narration / Description') }}</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Post Voucher') }}</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="acc-concessions">
                    <form method="POST" action="{{ route('accounting.concessions.store') }}" class="mb-4 p-3 bg-light rounded border">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('Student') }} *</label><select name="student_record_id" class="form-control" required>@foreach($students as $st)<option value="{{ $st->id }}">{{ $st->adm_no }} - {{ $st->user->name ?? '' }}</option>@endforeach</select></div>
                            <div class="col-md-3 form-group"><label>{{ __('Scholarship / Title') }} *</label><input type="text" name="title" class="form-control" required placeholder="Merit / Sibling Discount"></div>
                            <div class="col-md-2 form-group"><label>{{ __('Type') }}</label><select name="concession_type" class="form-control"><option value="fixed">Fixed NPR</option><option value="percentage">Percentage %</option></select></div>
                            <div class="col-md-3 form-group"><label>{{ __('Amount / %') }} *</label><input type="number" step="0.01" name="amount_or_percent" class="form-control" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Apply Concession') }}</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Title') }}</th><th>{{ __('Concession') }}</th><th>{{ __('Session') }}</th></tr></thead>
                            <tbody>
                                @forelse($concessions as $con)
                                    <tr>
                                        <td>{{ $con->studentRecord->user->name ?? '-' }}</td>
                                        <td>{{ $con->title }}</td>
                                        <td>{{ $con->concession_type === 'percentage' ? $con->amount_or_percent . '%' : \App\Helpers\NepaliHelper::formatNpr($con->amount_or_percent) }}</td>
                                        <td>{{ $con->academic_year }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">{{ __('No scholarships or fee concessions recorded.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="acc-fy">
                    @if(Qs::userIsTeamSA())
                        <form method="POST" action="{{ route('accounting.fiscal_years.store') }}" class="mb-4 p-3 bg-light rounded border">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-4 form-group mb-md-0"><label>{{ __('Fiscal Year Name') }} *</label><input type="text" name="name" class="form-control" required placeholder="FY 2083/84"></div>
                                <div class="col-md-3 form-group mb-md-0"><label>{{ __('Start (BS)') }} *</label><input type="text" name="start_date_bs" class="form-control" required placeholder="2083-04-01"></div>
                                <div class="col-md-3 form-group mb-md-0"><label>{{ __('End (BS)') }} *</label><input type="text" name="end_date_bs" class="form-control" required placeholder="2084-03-31"></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-block">{{ __('Save FY') }}</button></div>
                            </div>
                        </form>
                    @endif
                    <ul class="list-group">
                        @foreach($fiscalYears as $fy)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>{{ $fy->name }}</strong> ({{ $fy->start_date_bs }} BS &rarr; {{ $fy->end_date_bs }} BS)</span>
                                <span class="badge badge-{{ $fy->is_active ? 'success' : 'secondary' }}">{{ $fy->is_active ? __('Active') : __('Inactive') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
