@extends('layouts.master')
@section('page_title', __('Staff Payroll & Salary Slips'))
@section('content')

    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <h3 class="font-weight-bold mb-1 text-dark">{{ __('HR Payroll & Monthly Salary Processing') }}</h3>
            <p class="text-muted mb-0 font-size-sm">{{ __('Calculate salaries with allowances, provident fund & tax deductions, disburse payments & print payslips') }}</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Total Disbursed Payroll') }}</span><h3 class="font-weight-bold text-success mb-0">{{ \App\Helpers\NepaliHelper::formatNpr($totalNetPaid) }}</h3></div></div></div>
        <div class="col-md-6"><div class="card sms-stat-card"><div class="card-body p-3"><span class="text-muted font-size-xs text-uppercase">{{ __('Pending Disbursements') }}</span><h3 class="font-weight-bold text-warning mb-0">{{ $pendingPayrolls }}</h3></div></div></div>
    </div>

    <div class="card sms-card">
        <div class="card-body p-3 p-md-4">
            @if(Qs::userIsTeamAccount())
                <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                    <li class="nav-item"><a href="#pr-list" class="nav-link active" data-toggle="tab">{{ __('Payroll Register') }}</a></li>
                    <li class="nav-item"><a href="#pr-new" class="nav-link" data-toggle="tab">{{ __('Generate Salary Entry') }}</a></li>
                </ul>
            @endif

            <div class="tab-content">
                <div class="tab-pane fade show active" id="pr-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>{{ __('Staff') }}</th><th>{{ __('Month (BS)') }}</th><th>{{ __('Basic + Allow.') }}</th><th>{{ __('Deductions / Tax') }}</th><th>{{ __('Net Salary') }}</th><th>{{ __('Status') }}</th><th>{{ __('Action') }}</th></tr></thead>
                            <tbody>
                                @forelse($payrolls as $pr)
                                    <tr>
                                        <td><strong>{{ $pr->staff->name ?? '-' }}</strong> <div class="text-muted font-size-xs">{{ ucwords(str_replace('_', ' ', $pr->staff->user_type ?? '')) }}</div></td>
                                        <td>{{ $pr->salary_month_bs }}</td>
                                        <td>{{ \App\Helpers\NepaliHelper::formatNpr((float) $pr->basic_salary + (float) $pr->allowances) }}</td>
                                        <td>{{ \App\Helpers\NepaliHelper::formatNpr((float) $pr->deductions + (float) $pr->provident_fund + (float) $pr->tax_amount) }}</td>
                                        <td class="font-weight-bold text-primary">{{ \App\Helpers\NepaliHelper::formatNpr($pr->net_salary) }}</td>
                                        <td><span class="badge badge-{{ $pr->payment_status === 'paid' ? 'success' : 'warning' }} text-capitalize">{{ $pr->payment_status }}</span></td>
                                        <td>
                                            <a href="{{ route('payroll.payslip', $pr->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">{{ __('Payslip') }}</a>
                                            @if(Qs::userIsTeamAccount() && $pr->payment_status !== 'paid')
                                                <form method="POST" action="{{ route('payroll.mark_paid', $pr->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">{{ __('Pay & Post') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No payroll entries found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $payrolls->links() }}
                </div>

                @if(Qs::userIsTeamAccount())
                    <div class="tab-pane fade" id="pr-new">
                        <form method="POST" action="{{ route('payroll.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group"><label>{{ __('Staff Member') }} *</label><select name="user_id" class="form-control" required>@foreach($staffList as $st)<option value="{{ $st->id }}">{{ $st->name }} ({{ ucfirst($st->user_type) }})</option>@endforeach</select></div>
                                <div class="col-md-4 form-group"><label>{{ __('Salary Month (BS YYYY-MM)') }} *</label><input type="text" name="salary_month_bs" class="form-control" value="{{ $currentMonthBs }}" required></div>
                                <div class="col-md-4 form-group"><label>{{ __('Payment Method') }}</label><select name="payment_method" class="form-control"><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="cheque">Cheque</option></select></div>
                                <div class="col-md-3 form-group"><label>{{ __('Basic Salary') }} *</label><input type="number" step="0.01" name="basic_salary" class="form-control" required></div>
                                <div class="col-md-3 form-group"><label>{{ __('Allowances') }}</label><input type="number" step="0.01" name="allowances" value="0" class="form-control"></div>
                                <div class="col-md-2 form-group"><label>{{ __('Provident Fund') }}</label><input type="number" step="0.01" name="provident_fund" value="0" class="form-control"></div>
                                <div class="col-md-2 form-group"><label>{{ __('TDS / Tax') }}</label><input type="number" step="0.01" name="tax_amount" value="0" class="form-control"></div>
                                <div class="col-md-2 form-group"><label>{{ __('Other Deductions') }}</label><input type="number" step="0.01" name="deductions" value="0" class="form-control"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Generate Payroll') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
