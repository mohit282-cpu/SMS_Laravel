@extends('layouts.master')
@section('page_title', 'BS 12-Month Fee Collection Reports')
@section('content')

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white header-elements-inline border-bottom">
            <h2 class="card-title font-weight-bold mb-0 text-primary" style="font-size: 1.15rem;">
                <i class="icon-file-stats mr-2 text-danger"></i> {{ __('Annual Nepali Calendar Fee Collection Reports') }}
            </h2>
            <div class="header-elements">
                <a href="{{ route('payments.monthly_billing', ['bs_year' => $bs_year]) }}" class="btn btn-outline-primary btn-sm font-weight-semibold">
                    <i class="icon-calendar52 mr-1"></i> {{ __('Monthly Invoicing Dashboard') }}
                </a>
            </div>
        </div>

        <div class="card-body">
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
                            <option value="">{{ __('All Classes') }}</option>
                            @foreach($my_classes as $c)
                                <option value="{{ $c->id }}" {{ $my_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block font-weight-semibold">
                            <i class="icon-filter3 mr-1"></i> {{ __('Generate Report') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Grand Totals Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-indigo-400 text-white has-bg-image shadow-sm mb-3 mb-xl-0">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalBilled) }}</h3>
                        <span class="text-uppercase font-size-xs font-weight-semibold opacity-75">{{ __('Annual Billed Total') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-calculator2 icon-3x opacity-75"></i>
                    </div>
                </div>
                <div class="mt-2 text-white-50 small">
                    {{ __('Baisakh to Chaitra') }} {{ $bs_year }} BS
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-success-600 text-white has-bg-image shadow-sm mb-3 mb-xl-0">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalCollected) }}</h3>
                        <span class="text-uppercase font-size-xs font-weight-semibold opacity-75">{{ __('Annual Cash Collected') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-cash3 icon-3x opacity-75"></i>
                    </div>
                </div>
                <div class="mt-2 text-white-50 small">
                    {{ __('Overall Settlement Rate:') }} {{ $overallRate }}%
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-danger-400 text-white has-bg-image shadow-sm mb-3 mb-xl-0">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ \App\Helpers\NepaliHelper::formatNpr($grandTotalBalance) }}</h3>
                        <span class="text-uppercase font-size-xs font-weight-semibold opacity-75">{{ __('Outstanding Receivables') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-warning icon-3x opacity-75"></i>
                    </div>
                </div>
                <div class="mt-2 text-white-50 small">
                    {{ __('Uncollected fee balance') }}
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-body bg-purple-400 text-white has-bg-image shadow-sm mb-3 mb-xl-0">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-bold">{{ $overallRate }}%</h3>
                        <span class="text-uppercase font-size-xs font-weight-semibold opacity-75">{{ __('Collection Efficiency') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-pie-chart5 icon-3x opacity-75"></i>
                    </div>
                </div>
                <div class="mt-2 text-white-50 small">
                    {{ __('Across all 12 BS billing months') }}
                </div>
            </div>
        </div>
    </div>

    {{-- 12-Month Collection Matrix Table --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white header-elements-inline border-bottom">
            <h2 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.1rem;">
                <i class="icon-table2 mr-2 text-primary"></i>
                {{ __('12-Month Bikram Sambat Collection Breakdown — Year') }} {{ $bs_year }} BS
            </h2>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>{{ __('Month No') }}</th>
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
                                <td style="min-width: 130px;">
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 mr-2" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: {{ min(100, $ms['rate']) }}%"></div>
                                        </div>
                                        <span class="small font-weight-bold">{{ $ms['rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('payments.monthly_billing', ['bs_year' => $bs_year, 'bs_month' => $mNum, 'my_class_id' => $my_class_id]) }}"
                                       class="btn btn-outline-primary btn-sm px-2 py-1 font-weight-semibold">
                                        <i class="icon-eye mr-1"></i> {{ __('Details') }}
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
            <div class="card-header bg-white header-elements-inline border-bottom">
                <h2 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.1rem;">
                    <i class="icon-windows2 mr-2 text-primary"></i>
                    {{ __('Class-Wise Fee Collection Summary — Year') }} {{ $bs_year }} BS
                </h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                    <td style="min-width: 130px;">
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 mr-2" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ min(100, $cbRate) }}%"></div>
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
