@extends('layouts.master')
@section('page_title', __('Monthly Fee Billing (Bikram Sambat 12-Month)'))
@section('content')

    {{-- Executive Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-calendar52 mr-1" aria-hidden="true"></i> {{ __('Nepali 12-Month Billing Cycle') }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('BS Year') }}: <strong>{{ $bs_year }} BS</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Nepali 12-Month Billing Cycle (Baisakh — Chaitra)') }}</h2>
        </div>
        <div class="d-flex align-items-center flex-wrap mt-3 mt-md-0" style="gap: 0.5rem;">
            <button type="button" class="btn btn-sm text-white font-weight-semibold" style="background: #FF5700; border-radius: 8px;" data-toggle="modal" data-target="#generateInvoicesModal">
                <i class="icon-plus-circle2 mr-1" aria-hidden="true"></i> {{ __('Generate Monthly Invoices') }}
            </button>
            <a href="{{ route('payments.reports', ['bs_year' => $bs_year]) }}" class="btn btn-outline-primary btn-sm font-weight-semibold">
                <i class="icon-file-stats mr-1" aria-hidden="true"></i> {{ __('Annual BS Reports') }}
            </a>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold">
                <i class="icon-coins mr-1" aria-hidden="true"></i> {{ __('Fee Structures') }}
            </a>
        </div>
    </div>

    {{-- Filter & Period Selector Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" action="{{ route('payments.monthly_billing') }}" class="mb-3">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="bs_year" class="font-weight-bold text-muted small text-uppercase">{{ __('Bikram Sambat Year') }}</label>
                        <select id="bs_year" name="bs_year" class="form-control select font-weight-semibold">
                            @for($y = 2080; $y <= 2090; $y++)
                                <option value="{{ $y }}" {{ $bs_year == $y ? 'selected' : '' }}>{{ $y }} BS ({{ \App\Helpers\NepaliHelper::toDevanagari($y) }})</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="bs_month" class="font-weight-bold text-muted small text-uppercase">{{ __('Billing Month') }}</label>
                        <select id="bs_month" name="bs_month" class="form-control select font-weight-semibold">
                            @foreach($months as $mId => $m)
                                <option value="{{ $mId }}" {{ $bs_month == $mId ? 'selected' : '' }}>
                                    {{ __('Month :m', ['m' => $mId]) }}: {{ $m['en'] }} ({{ $m['ne'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="my_class_id" class="font-weight-bold text-muted small text-uppercase">{{ __('Filter by Class') }}</label>
                        <select id="my_class_id" name="my_class_id" class="form-control select">
                            <option value="">{{ __('All Classes') }}</option>
                            @foreach($my_classes as $c)
                                <option value="{{ $c->id }}" {{ $my_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block font-weight-semibold">
                            <i class="icon-filter3 mr-1" aria-hidden="true"></i> {{ __('View Period') }}
                        </button>
                    </div>
                </div>
            </form>

            {{-- 12-Month Navigation Pills --}}
            <div class="border-top pt-3">
                <label class="font-weight-bold text-muted small text-uppercase d-block mb-2">{{ __('Nepali Calendar Months (Baisakh to Chaitra)') }}</label>
                <div class="d-flex flex-wrap gap-1" style="gap: 6px;">
                    @foreach($months as $mId => $m)
                        <a href="{{ route('payments.monthly_billing', ['bs_year' => $bs_year, 'bs_month' => $mId, 'my_class_id' => $my_class_id]) }}"
                           class="btn btn-sm {{ $bs_month == $mId ? 'btn-danger font-weight-bold text-white shadow-sm' : 'btn-outline-secondary' }}"
                           style="min-width: 80px;">
                            <span class="d-block small">{{ $mId }}. {{ $m['ne'] }}</span>
                            <span class="d-block" style="font-size: 0.75rem;">{{ $m['en'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 p-2 bg-light rounded text-muted small d-flex flex-wrap justify-content-between align-items-center">
                <span>
                    <i class="icon-info22 text-info mr-1" aria-hidden="true"></i>
                    <strong>{{ $periodRange['month_name_en'] }} ({{ $periodRange['month_name_ne'] }}) {{ $bs_year }} BS:</strong>
                    {{ $periodRange['days'] }} {{ __('days') }} ({{ __('Gregorian Period') }}: {{ $periodRange['start_ad'] }} &rarr; {{ $periodRange['end_ad'] }})
                </span>
                <span class="badge badge-info font-weight-semibold px-2 py-1 mt-1 mt-md-0">
                    {{ __('Canonical Storage: Gregorian (AD)') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Financial Statistics Cards --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number" style="font-size: 1.55rem;">{{ \App\Helpers\NepaliHelper::formatNpr($totalBilled) }}</div>
                    <div class="stat-label">{{ __('Total Invoiced') }}</div>
                    <div class="text-muted font-size-xs mt-1">{{ $records->count() }} {{ __('invoices generated') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-navy">
                    <i class="icon-calculator2" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number" style="font-size: 1.55rem;">{{ \App\Helpers\NepaliHelper::formatNpr($totalCollected) }}</div>
                    <div class="stat-label">{{ __('Cash Collected') }}</div>
                    <div class="text-success font-size-xs mt-1">{{ $clearedCount }} {{ __('settled invoices') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="icon-cash3" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number text-danger" style="font-size: 1.55rem;">{{ \App\Helpers\NepaliHelper::formatNpr($totalBalance) }}</div>
                    <div class="stat-label">{{ __('Outstanding Balance') }}</div>
                    <div class="text-danger font-size-xs mt-1">{{ $unclearedCount }} {{ __('due / partial invoices') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-rose">
                    <i class="icon-warning" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number">{{ $eligibleStudentsCount }}</div>
                    <div class="stat-label">{{ __('Eligible Students') }}</div>
                    <div class="text-muted font-size-xs mt-1">
                        @if($eligibleStudentsCount > 0)
                            {{ round(($records->pluck('student_id')->unique()->count() / $eligibleStudentsCount) * 100, 1) }}% {{ __('students billed') }}
                        @else
                            0% {{ __('students billed') }}
                        @endif
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">
                    <i class="icon-users4" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Invoices Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between">
            <h5 class="font-weight-bold mb-0 text-dark">
                <i class="icon-file-text2 mr-2 text-primary" aria-hidden="true"></i>
                {{ __('Invoices for') }} {{ $periodRange['month_name_en'] }} ({{ $periodRange['month_name_ne'] }}) {{ $bs_year }} BS
            </h5>
            <span class="badge badge-primary font-weight-semibold px-2 py-1">{{ $records->count() }} {{ __('Total') }}</span>
        </div>

        <div class="card-body p-0">
            @if($records->isEmpty())
                <div class="text-center py-5">
                    <i class="icon-file-empty icon-4x text-muted mb-3 d-block" aria-hidden="true"></i>
                    <h5 class="text-muted font-weight-semibold">{{ __('No invoices generated for this Nepali billing period yet.') }}</h5>
                    <p class="text-muted small mb-3">{{ __('Click "Generate Invoices" to create recurring bills for eligible enrolled students.') }}</p>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#generateInvoicesModal">
                        <i class="icon-plus-circle2 mr-1" aria-hidden="true"></i> {{ __('Generate Monthly Invoices Now') }}
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover datatable-button-html5-columns">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Ref No') }}</th>
                                <th>{{ __('Student') }}</th>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Fee Item') }}</th>
                                <th>{{ __('Due Date') }}</th>
                                <th>{{ __('Billed') }}</th>
                                <th>{{ __('Paid') }}</th>
                                <th>{{ __('Balance') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $idx => $r)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td><span class="badge badge-light font-weight-bold">{{ $r->ref_no }}</span></td>
                                    <td>
                                        <strong>{{ $r->student->name ?? 'N/A' }}</strong>
                                        <div class="small text-muted">{{ $r->student->code ?? '' }}</div>
                                    </td>
                                    <td>{{ $r->student->student_record->my_class->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="font-weight-semibold">{{ $r->payment->title ?? __('Monthly Fee') }}</span>
                                        <div class="small text-muted">{{ ucfirst($r->payment->fee_category ?? 'tuition') }}</div>
                                    </td>
                                    <td>
                                        @if($r->due_date)
                                            <span class="font-weight-semibold">{{ \App\Helpers\NepaliHelper::formatDisplayDate($r->due_date) }}</span>
                                        @elseif($r->due_date_bs)
                                            <span>{{ $r->due_date_bs }} BS</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><strong class="text-dark">{{ \App\Helpers\NepaliHelper::formatNpr($r->payment->amount ?? 0) }}</strong></td>
                                    <td><span class="text-success font-weight-semibold">{{ \App\Helpers\NepaliHelper::formatNpr($r->amt_paid) }}</span></td>
                                    <td>
                                        @if($r->balance > 0)
                                            <span class="text-danger font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($r->balance) }}</span>
                                        @else
                                            <span class="text-muted">{{ \App\Helpers\NepaliHelper::formatNpr(0) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($r->paid)
                                            <span class="badge badge-success px-2 py-1"><i class="icon-checkmark2 mr-1" aria-hidden="true"></i> {{ __('Paid') }}</span>
                                        @elseif($r->amt_paid > 0)
                                            <span class="badge badge-warning px-2 py-1"><i class="icon-hour-glass2 mr-1" aria-hidden="true"></i> {{ __('Partial') }}</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1"><i class="icon-cross2 mr-1" aria-hidden="true"></i> {{ __('Unpaid') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown" aria-label="{{ __('Options') }}"><i class="icon-menu9"></i></a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a href="{{ route('payments.invoice', [Qs::hash($r->student_id), $r->year]) }}" class="dropdown-item">
                                                        <i class="icon-file-text2 mr-2 text-primary" aria-hidden="true"></i> {{ __('Student Invoice') }}
                                                    </a>
                                                    @if($r->receipt->count() > 0)
                                                        <a target="_blank" href="{{ route('payments.receipts', Qs::hash($r->id)) }}" class="dropdown-item">
                                                            <i class="icon-printer mr-2 text-secondary" aria-hidden="true"></i> {{ __('Print Receipt') }}
                                                        </a>
                                                    @endif
                                                    @if(!$r->paid && Qs::userIsTeamAccount())
                                                        <a href="{{ route('payments.invoice', [Qs::hash($r->student_id), $r->year]) }}" class="dropdown-item text-primary font-weight-semibold">
                                                            <i class="icon-cash mr-2" aria-hidden="true"></i> {{ __('Collect Payment') }}
                                                        </a>
                                                    @endif
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

    {{-- Generate Invoices Modal --}}
    <div class="modal fade" id="generateInvoicesModal" tabindex="-1" role="dialog" aria-labelledby="generateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post" action="{{ route('payments.generate_monthly_invoices') }}">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold" id="generateModalLabel">
                            <i class="icon-calendar52 mr-2" aria-hidden="true"></i> {{ __('Generate Monthly Fee Invoices') }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-info border-0 alert-dismissible mb-3">
                            <i class="icon-info22 mr-2" aria-hidden="true"></i>
                            {{ __('Invoice generation is strictly idempotent. Re-running for an existing month will not create duplicate charges.') }}
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_bs_year" class="font-weight-semibold">{{ __('BS Year') }} <span class="text-danger">*</span></label>
                                    <select required id="modal_bs_year" name="bs_year" class="form-control select">
                                        @for($y = 2080; $y <= 2090; $y++)
                                            <option value="{{ $y }}" {{ $bs_year == $y ? 'selected' : '' }}>{{ $y }} BS</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_bs_month" class="font-weight-semibold">{{ __('Nepali Month') }} <span class="text-danger">*</span></label>
                                    <select required id="modal_bs_month" name="bs_month" class="form-control select">
                                        @foreach($months as $mId => $m)
                                            <option value="{{ $mId }}" {{ $bs_month == $mId ? 'selected' : '' }}>
                                                {{ $mId }}. {{ $m['en'] }} ({{ $m['ne'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="modal_my_class_id" class="font-weight-semibold">{{ __('Target Class') }}</label>
                            <select id="modal_my_class_id" name="my_class_id" class="form-control select">
                                <option value="">{{ __('All Classes (Whole School)') }}</option>
                                @foreach($my_classes as $c)
                                    <option value="{{ $c->id }}" {{ $my_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_fee_category" class="font-weight-semibold">{{ __('Fee Category') }} <span class="text-danger">*</span></label>
                                    <select required id="modal_fee_category" name="fee_category" class="form-control select">
                                        <option value="tuition">{{ __('Tuition Fee') }}</option>
                                        <option value="transport">{{ __('Transport Fee') }}</option>
                                        <option value="hostel">{{ __('Hostel Fee') }}</option>
                                        <option value="computer_lab">{{ __('Computer / Lab Fee') }}</option>
                                        <option value="all">{{ __('All Core Fee Items') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_due_day" class="font-weight-semibold">{{ __('Due Day of BS Month') }}</label>
                                    <input type="number" min="1" max="32" id="modal_due_day" name="due_day" value="15" class="form-control" placeholder="15">
                                    <span class="form-text text-muted small">{{ __('e.g., 15th of the Nepali month') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="modal_amount" class="font-weight-semibold">{{ __('Custom Amount Override (Optional)') }}</label>
                            <input type="number" step="0.01" min="0" id="modal_amount" name="amount" class="form-control" placeholder="{{ __('Leave blank to use class standard fee structure') }}">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary font-weight-semibold">
                            <i class="icon-checkmark3 mr-1" aria-hidden="true"></i> {{ __('Generate Invoices Now') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
