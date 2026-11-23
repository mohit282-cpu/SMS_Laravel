@extends('layouts.master')
@section('page_title', __('BS 12-Month Fee Collection Reports'))
@section('content')

    {{-- Executive Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-file-stats mr-1" aria-hidden="true"></i> {{ __('Annual Financial Analytics') }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('BS Fiscal Year') }}: <strong>{{ $bs_year }} BS</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Annual Nepali Calendar Fee Collection Reports') }}</h2>
        </div>
        <div class="d-flex align-items-center flex-wrap mt-3 mt-md-0" style="gap: 0.5rem;">
            <a href="{{ route('payments.monthly_billing', ['bs_year' => $bs_year]) }}" class="btn btn-outline-primary btn-sm font-weight-semibold">
                <i class="icon-calendar52 mr-1" aria-hidden="true"></i> {{ __('Monthly Invoicing Dashboard') }}
            </a>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold">
                <i class="icon-coins mr-1" aria-hidden="true"></i> {{ __('Fee Structures') }}
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="get" action="{{ route('payments.reports') }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="bs_year" class="font-weight-bold text-muted small text-uppercase">{{ __('Bikram Sambat Year') }}</label>
                        <select id="bs_year" name="bs_year" class="form-control select font-weight-semibold">
                            @for($y = 2080; $y <= 2090; $y++)
                                <option value="{{ $y }}" {{ $bs_year == $y ? 'selected' : '' }}>{{ $y }} BS ({{ \App\Helpers\NepaliHelper::toDevanagari($y) }})</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label for="my_class_id" class="font-weight-bold text-muted small text-uppercase">{{ __('Filter by Class') }}</label>
                        <select id="my_class_id" name="my_class_id" class="form-control select">
                            <option value="">{{ __('All Classes (Consolidated Overview)') }}</option>
                            @foreach($my_classes as $c)
                                <option value="{{ $c->id }}" {{ $my_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block font-weight-semibold">
                            <i class="icon-filter3 mr-1" aria-hidden="true"></i> {{ __('Generate Report') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Grand Totals Stat Cards --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number" style="font-size: 1.55rem;">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalBilled) }}</div>
                    <div class="stat-label">{{ __('Annual Billed Total') }}</div>
                    <div class="text-muted font-size-xs mt-1">{{ __('Baisakh — Chaitra') }} {{ $bs_year }} BS</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-navy">
                    <i class="icon-calculator2" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number" style="font-size: 1.55rem;">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalCollected) }}</div>
                    <div class="stat-label">{{ __('Annual Cash Collected') }}</div>
                    <div class="text-success font-size-xs mt-1">{{ __('Settlement Rate:') }} <strong>{{ $overallRate }}%</strong></div>
                </div>
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="icon-cash3" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number text-danger" style="font-size: 1.55rem;">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalBalance) }}</div>
                    <div class="stat-label">{{ __('Outstanding Receivables') }}</div>
                    <div class="text-danger font-size-xs mt-1">{{ __('Uncollected balance') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-rose">
                    <i class="icon-warning" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="sms-stat-card">
                <div>
                    <div class="stat-number">{{ $overallRate }}%</div>
                    <div class="stat-label">{{ __('Collection Efficiency') }}</div>
                    <div class="text-muted font-size-xs mt-1">{{ __('Across all 12 BS months') }}</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">
                    <i class="icon-pie-chart5" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- 12-Month Collection Matrix Table --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between">
            <h5 class="font-weight-bold mb-0 text-dark">
                <i class="icon-table2 mr-2 text-primary" aria-hidden="true"></i>
                {{ __('12-Month Bikram Sambat Collection Breakdown — Year') }} {{ $bs_year }} BS
            </h5>
            <span class="badge badge-light border text-muted">{{ __('12 Months (Baisakh to Chaitra)') }}</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>{{ __('Nepali Month') }}</th>
                            <th>{{ __('Gregorian Period (AD)') }}</th>
                            <th>{{ __('Invoices') }}</th>
                            <th>{{ __('Settled') }}</th>
                            <th>{{ __('Total Billed') }}</th>
                            <th>{{ __('Cash Collected') }}</th>
                            <th>{{ __('Balance') }}</th>
                            <th>{{ __('Collection Rate') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlySummaries as $mNum => $ms)
                            <tr>
                                <td><span class="badge badge-light font-weight-bold">{{ $mNum }}</span></td>
                                <td>
                                    <strong>{{ $ms['name_en'] }}</strong>
                                    <span class="text-muted ml-1">({{ $ms['name_ne'] }})</span>
                                </td>
                                <td>
                                    <small class="text-muted font-weight-semibold">
                                        {{ $ms['start_ad'] }} &rarr; {{ $ms['end_ad'] }} ({{ $ms['days'] }}d)
                                    </small>
                                </td>
                                <td><span class="badge badge-secondary">{{ $ms['invoice_count'] }}</span></td>
                                <td><span class="badge badge-success">{{ $ms['paid_count'] }}</span></td>
                                <td><strong>{{ \App\Helpers\NepaliHelper::formatNpr($ms['billed']) }}</strong></td>
                                <td><span class="text-success font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($ms['collected']) }}</span></td>
                                <td>
                                    @if($ms['balance'] > 0)
                                        <span class="text-danger font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($ms['balance']) }}</span>
                                    @else
                                        <span class="text-muted">{{ \App\Helpers\NepaliHelper::formatNpr(0) }}</span>
                                    @endif
                                </td>
                                <td style="min-width: 140px;">
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 mr-2" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(100, $ms['rate']) }}%"></div>
                                        </div>
                                        <span class="small font-weight-bold">{{ $ms['rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('payments.monthly_billing', ['bs_year' => $bs_year, 'bs_month' => $mNum, 'my_class_id' => $my_class_id]) }}"
                                       class="btn btn-outline-primary btn-sm px-2 py-1 font-weight-semibold">
                                        <i class="icon-eye mr-1" aria-hidden="true"></i> {{ __('View Invoices') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="3" class="text-right">{{ __('Annual Total:') }}</td>
                            <td>{{ collect($monthlySummaries)->sum('invoice_count') }}</td>
                            <td>{{ collect($monthlySummaries)->sum('paid_count') }}</td>
                            <td>{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalBilled) }}</td>
                            <td class="text-success">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalCollected) }}</td>
                            <td class="text-danger">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalBalance) }}</td>
                            <td><strong>{{ $overallRate }}%</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Class Breakdown Table --}}
    @if(count($classBreakdown) > 0)
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between">
                <h5 class="font-weight-bold mb-0 text-dark">
                    <i class="icon-windows2 mr-2 text-primary" aria-hidden="true"></i>
                    {{ __('Class-Wise Fee Collection Summary — Year') }} {{ $bs_year }} BS
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Invoices') }}</th>
                                <th>{{ __('Total Billed') }}</th>
                                <th>{{ __('Cash Collected') }}</th>
                                <th>{{ __('Outstanding Balance') }}</th>
                                <th>{{ __('Collection Rate') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classBreakdown as $cb)
                                @php
                                    $cbRate = ($cb['billed'] > 0) ? round(($cb['collected'] / $cb['billed']) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $cb['class_name'] }}</strong></td>
                                    <td><span class="badge badge-light font-weight-bold">{{ $cb['invoices'] }}</span></td>
                                    <td><strong>{{ \App\Helpers\NepaliHelper::formatNpr($cb['billed']) }}</strong></td>
                                    <td><span class="text-success font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($cb['collected']) }}</span></td>
                                    <td>
                                        @if($cb['balance'] > 0)
                                            <span class="text-danger font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($cb['balance']) }}</span>
                                        @else
                                            <span class="text-muted">{{ \App\Helpers\NepaliHelper::formatNpr(0) }}</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 140px;">
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 mr-2" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, $cbRate) }}%"></div>
                                            </div>
                                            <span class="small font-weight-bold">{{ $cbRate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

@endsection
