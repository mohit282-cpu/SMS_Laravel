@extends('layouts.master')
@section('page_title', __('Student Payments & Fee Collection'))
@section('content')

    {{-- Executive Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-cash3 mr-1" aria-hidden="true"></i> {{ __('Fee Collection') }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('Session') }}: <strong>{{ Qs::getCurrentSession() }}</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Student Payments & Invoice Management') }}</h2>
        </div>
        <div class="d-flex align-items-center flex-wrap mt-3 mt-md-0" style="gap: 0.5rem;">
            <a href="{{ route('payments.monthly_billing') }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-calendar52 mr-1" aria-hidden="true"></i> {{ __('Monthly Invoicing') }}
            </a>
            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="icon-coins mr-1" aria-hidden="true"></i> {{ __('Fee Structures') }}
            </a>
        </div>
    </div>

    {{-- Class Selector Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="post" action="{{ route('payments.select_class') }}" class="d-flex flex-wrap align-items-center justify-content-between">
                @csrf
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="icon-users4 icon-2x text-primary mr-3" aria-hidden="true"></i>
                    <div>
                        <h6 class="font-weight-bold text-dark mb-0">{{ __('Select Class for Fee Collection') }}</h6>
                        <span class="text-muted font-size-xs">{{ __('Choose a class cohort to view student accounts, issue receipts, and manage fee settlements.') }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center" style="gap: 0.5rem;">
                    <div style="min-width: 220px;">
                        <select required id="my_class_id" name="my_class_id" class="form-control select">
                            <option value="">{{ __('Select Class') }}</option>
                            @foreach($my_classes as $c)
                                <option {{ (($my_class_id ?? '') == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-semibold">
                        <i class="icon-filter3 mr-1" aria-hidden="true"></i> {{ __('Filter') }}
                    </button>
                </div>
            </form>

            {{-- Quick Class Pills --}}
            @if(count($my_classes ?? []) > 0)
                <div class="border-top pt-2 mt-3">
                    <span class="text-muted font-size-xs font-weight-bold text-uppercase mr-2">{{ __('Quick Classes') }}:</span>
                    <div class="d-inline-flex flex-wrap" style="gap: 4px;">
                        @foreach($my_classes as $c)
                            <a href="{{ route('payments.manage', $c->id) }}"
                               class="btn btn-xs {{ (($my_class_id ?? '') == $c->id) ? 'btn-primary font-weight-bold' : 'btn-light border' }}">
                                {{ $c->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Students Table Card --}}
    @if($selected && isset($students))
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h5 class="font-weight-bold mb-0 text-dark">
                        <i class="icon-user-check mr-2 text-success" aria-hidden="true"></i>
                        {{ __('Enrolled Students in') }} {{ optional($my_classes->firstWhere('id', $my_class_id))->name ?? __('Selected Class') }}
                    </h5>
                    <span class="text-muted font-size-xs">{{ __('Click "Manage Invoices" to collect payments, print receipts, and review balances.') }}</span>
                </div>
                <span class="badge badge-primary font-weight-semibold px-2 py-1">{{ $students->count() }} {{ __('Students') }}</span>
            </div>

            <div class="card-body p-0">
                @if($students->isEmpty())
                    <div class="text-center py-5">
                        <i class="icon-user-cancel icon-3x text-muted mb-2 d-block" aria-hidden="true"></i>
                        <h5 class="font-weight-bold text-dark mb-1">{{ __('No Students Currently Enrolled in This Class') }}</h5>
                        <p class="text-muted small mb-3">{{ __('Admit students to this class to begin tracking fee payments and generating invoices.') }}</p>
                        @if(Qs::userIsTeamSA())
                            <a href="{{ route('students.create') }}" class="btn btn-sm btn-primary">
                                <i class="icon-user-plus mr-1" aria-hidden="true"></i>{{ __('Admit New Student') }}
                            </a>
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable-button-html5-columns mb-0">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 60px;">{{ __('Photo') }}</th>
                                <th>{{ __('Student Name') }}</th>
                                <th>{{ __('ADM No') }}</th>
                                <th>{{ __('Class & Section') }}</th>
                                <th class="text-center" style="width: 160px;">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($students as $s)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <img class="rounded-circle" style="height: 38px; width: 38px; object-fit: cover; border: 1px solid #ddd;" src="{{ $s->user->photo }}" alt="{{ $s->user->name }}">
                                    </td>
                                    <td>
                                        <strong class="text-dark">{{ $s->user->name }}</strong>
                                        <div class="small text-muted">{{ $s->user->email ?? $s->user->username }}</div>
                                    </td>
                                    <td><span class="badge badge-light font-weight-bold">{{ $s->adm_no }}</span></td>
                                    <td>
                                        <span class="badge badge-info">{{ $s->my_class->name ?? '-' }}</span>
                                        <span class="badge badge-light border">{{ $s->section->name ?? '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle font-weight-semibold" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="icon-cash mr-1" aria-hidden="true"></i> {{ __('Manage Invoices') }}
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                <a href="{{ route('payments.invoice', [Qs::hash($s->user_id)]) }}" class="dropdown-item font-weight-semibold text-primary">
                                                    <i class="icon-file-text2 mr-2" aria-hidden="true"></i> {{ __('Consolidated Invoice (All Years)') }}
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <span class="dropdown-header text-uppercase font-size-xs text-muted">{{ __('Specific Sessions') }}</span>
                                                @foreach(Pay::getYears($s->user_id) as $py)
                                                    @if($py)
                                                        <a href="{{ route('payments.invoice', [Qs::hash($s->user_id), $py]) }}" class="dropdown-item">
                                                            <i class="icon-calendar2 mr-2 text-muted" aria-hidden="true"></i> {{ $py }}
                                                        </a>
                                                    @endif
                                                @endforeach
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
    @endif

@endsection
