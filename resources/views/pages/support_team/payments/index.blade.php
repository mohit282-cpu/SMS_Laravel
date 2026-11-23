@extends('layouts.master')
@section('page_title', __('Manage Payments & Fee Structures'))
@section('content')

    {{-- Executive Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-cash2 mr-1" aria-hidden="true"></i> {{ __('Fee Structures') }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('Active Session') }}: <strong>{{ $year ?? Qs::getCurrentSession() }}</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Manage Payments & Fee Structures') }}</h2>
        </div>
        <div class="d-flex align-items-center flex-wrap mt-3 mt-md-0" style="gap: 0.5rem;">
            <a href="{{ route('payments.create') }}" class="btn btn-sm text-white" style="background: #FF5700; border-radius: 8px;">
                <i class="icon-plus2 mr-1" aria-hidden="true"></i> {{ __('New Fee Item') }}
            </a>
            <a href="{{ route('payments.monthly_billing') }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-calendar52 mr-1" aria-hidden="true"></i> {{ __('Monthly Invoicing') }}
            </a>
            <a href="{{ route('payments.manage') }}" class="btn btn-sm btn-outline-success">
                <i class="icon-wallet mr-1" aria-hidden="true"></i> {{ __('Collect Fees') }}
            </a>
        </div>
    </div>

    {{-- Academic Session Switcher Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="post" action="{{ route('payments.select_year') }}" class="d-flex flex-wrap align-items-center justify-content-between">
                @csrf
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="icon-calendar3 icon-2x text-primary mr-3" aria-hidden="true"></i>
                    <div>
                        <h6 class="font-weight-bold text-dark mb-0">{{ __('Fee Structures for Session') }}: <span class="text-primary">{{ $year ?? Qs::getCurrentSession() }}</span></h6>
                        <span class="text-muted font-size-xs">{{ __('Switch academic years to review or modify past and upcoming fee schedules.') }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center" style="gap: 0.5rem;">
                    <div style="min-width: 200px;">
                        <select data-placeholder="{{ __('Select Academic Year') }}" required id="year" name="year" class="form-control select">
                            @foreach($years as $yr)
                                <option {{ (($year ?? '') == $yr->year) ? 'selected' : '' }} value="{{ $yr->year }}">{{ $yr->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-semibold">
                        <i class="icon-sync mr-1" aria-hidden="true"></i> {{ __('Switch') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Metric Stat Cards --}}
    @if(isset($payments))
        <div class="row">
            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($payments->count()) }}</div>
                        <div class="stat-label">{{ __('Total Fee Structures') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-navy">
                        <i class="icon-coins" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($payments->whereNull('my_class_id')->count()) }}</div>
                        <div class="stat-label">{{ __('Whole-School Fees') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <i class="icon-globe" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ Qs::formatNumber($payments->whereNotNull('my_class_id')->count()) }}</div>
                        <div class="stat-label">{{ __('Class-Specific Fees') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="icon-windows2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="sms-stat-card">
                    <div>
                        <div class="stat-number">{{ count($my_classes ?? []) }}</div>
                        <div class="stat-label">{{ __('Classes Available') }}</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="icon-graduation2" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fee Structures Content Card --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between">
                <h5 class="font-weight-bold mb-0 text-dark">
                    <i class="icon-list mr-2 text-primary" aria-hidden="true"></i>{{ __('Fee Items List & Configuration') }}
                </h5>
                <a href="{{ route('payments.create') }}" class="btn btn-sm btn-outline-primary">
                    <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Add New Fee Item') }}
                </a>
            </div>

            <div class="card-body">
                <ul class="nav nav-tabs nav-tabs-highlight" role="tablist">
                    <li class="nav-item">
                        <a href="#all-payments" class="nav-link active font-weight-semibold" data-toggle="tab">
                            <i class="icon-list mr-1" aria-hidden="true"></i> {{ __('All Fee Items') }}
                            <span class="badge badge-primary ml-1">{{ $payments->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle font-weight-semibold" data-toggle="dropdown">
                            <i class="icon-filter3 mr-1" aria-hidden="true"></i> {{ __('Filter By Class') }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            @foreach($my_classes as $mc)
                                <a href="#pc-{{ $mc->id }}" class="dropdown-item" data-toggle="tab">
                                    <i class="icon-windows2 mr-2 text-muted" aria-hidden="true"></i>{{ $mc->name }}
                                    <span class="badge badge-light border ml-auto">{{ $payments->where('my_class_id', $mc->id)->count() }}</span>
                                </a>
                            @endforeach
                        </div>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    {{-- All Payments Tab --}}
                    <div class="tab-pane fade show active" id="all-payments" role="tabpanel">
                        @if($payments->isEmpty())
                            <div class="text-center py-5">
                                <i class="icon-coins icon-3x text-muted mb-3 d-block" aria-hidden="true"></i>
                                <h5 class="font-weight-bold text-dark mb-1">{{ __('No Fee Structures Configured For This Session') }}</h5>
                                <p class="text-muted max-w-500 mx-auto mb-3" style="max-width: 480px;">
                                    {{ __('Define tuition, exam, or facility fees for this session to enable billing and invoice generation.') }}
                                </p>
                                <a href="{{ route('payments.create') }}" class="btn text-white btn-sm" style="background: #FF5700;">
                                    <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Create Fee Structure') }}
                                </a>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table datatable-button-html5-columns table-hover table-striped">
                                    <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Ref Code') }}</th>
                                        <th>{{ __('Target Class') }}</th>
                                        <th>{{ __('Method') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($payments as $p)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong class="text-dark">{{ $p->title }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border font-weight-bold text-success" style="font-size: 0.95rem;">
                                                    {{ \App\Helpers\NepaliHelper::formatNpr($p->amount) }}
                                                </span>
                                            </td>
                                            <td><span class="badge badge-light font-weight-bold">{{ $p->ref_no }}</span></td>
                                            <td>
                                                @if($p->my_class_id)
                                                    <span class="badge badge-info"><i class="icon-windows2 mr-1"></i>{{ $p->my_class->name }}</span>
                                                @else
                                                    <span class="badge badge-secondary"><i class="icon-globe mr-1"></i>{{ __('All Classes') }}</span>
                                                @endif
                                            </td>
                                            <td><span class="text-muted">{{ ucwords($p->method) }}</span></td>
                                            <td><span class="text-muted small">{{ $p->description ?: '-' }}</span></td>
                                            <td class="text-center">
                                                <div class="list-icons">
                                                    <div class="dropdown">
                                                        <a href="#" class="list-icons-item" data-toggle="dropdown" aria-label="{{ __('Options') }}">
                                                            <i class="icon-menu9"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <a href="{{ route('payments.edit', $p->id) }}" class="dropdown-item">
                                                                <i class="icon-pencil text-warning mr-2" aria-hidden="true"></i>{{ __('Edit Fee') }}
                                                            </a>
                                                            @if(Qs::userIsSuperAdmin())
                                                                <div class="dropdown-divider"></div>
                                                                <a id="{{ $p->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                                    <i class="icon-trash text-danger mr-2" aria-hidden="true"></i>{{ __('Delete') }}
                                                                </a>
                                                                <form method="post" id="item-delete-{{ $p->id }}" action="{{ route('payments.destroy', $p->id) }}" class="d-none">
                                                                    @csrf @method('delete')
                                                                </form>
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

                    {{-- Class-Specific Tabs --}}
                    @foreach($my_classes as $mc)
                        <div class="tab-pane fade" id="pc-{{ $mc->id }}" role="tabpanel">
                            @php $class_payments = $payments->where('my_class_id', $mc->id); @endphp
                            @if($class_payments->isEmpty())
                                <div class="text-center py-4">
                                    <p class="text-muted mb-2">{{ __('No specific fee items assigned to :class.', ['class' => $mc->name]) }}</p>
                                    <a href="{{ route('payments.create') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="icon-plus2 mr-1" aria-hidden="true"></i>{{ __('Add Fee for :class', ['class' => $mc->name]) }}
                                    </a>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table datatable-button-html5-columns table-hover table-striped">
                                        <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>{{ __('Title') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                            <th>{{ __('Ref Code') }}</th>
                                            <th>{{ __('Method') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($class_payments as $p)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td><strong>{{ $p->title }}</strong></td>
                                                <td>
                                                    <span class="badge badge-light border font-weight-bold text-success">
                                                        {{ \App\Helpers\NepaliHelper::formatNpr($p->amount) }}
                                                    </span>
                                                </td>
                                                <td><span class="badge badge-light font-weight-bold">{{ $p->ref_no }}</span></td>
                                                <td><span class="text-muted">{{ ucwords($p->method) }}</span></td>
                                                <td><span class="text-muted small">{{ $p->description ?: '-' }}</span></td>
                                                <td class="text-center">
                                                    <div class="list-icons">
                                                        <div class="dropdown">
                                                            <a href="#" class="list-icons-item" data-toggle="dropdown" aria-label="{{ __('Options') }}">
                                                                <i class="icon-menu9"></i>
                                                            </a>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a href="{{ route('payments.edit', $p->id) }}" class="dropdown-item">
                                                                    <i class="icon-pencil text-warning mr-2" aria-hidden="true"></i>{{ __('Edit Fee') }}
                                                                </a>
                                                                @if(Qs::userIsSuperAdmin())
                                                                    <div class="dropdown-divider"></div>
                                                                    <a id="{{ $p->id }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item text-danger">
                                                                        <i class="icon-trash text-danger mr-2" aria-hidden="true"></i>{{ __('Delete') }}
                                                                    </a>
                                                                    <form method="post" id="item-delete-{{ $p->id }}" action="{{ route('payments.destroy', $p->id) }}" class="d-none">
                                                                        @csrf @method('delete')
                                                                    </form>
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
                    @endforeach
                </div>
            </div>
        </div>
    @endif

@endsection
