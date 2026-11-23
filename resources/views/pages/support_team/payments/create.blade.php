@extends('layouts.master')
@section('page_title', __('Create Payment Structure'))
@section('content')

    {{-- Executive Header with Dual Date & Academic Session --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex flex-wrap align-items-center mb-1 gap-2">
                <span class="badge badge-primary mr-2">
                    <i class="icon-cash3 mr-1" aria-hidden="true"></i> {{ __('Financial Configuration') }}
                </span>
                <span class="badge badge-light border text-dark mr-2">
                    <i class="icon-calendar22 mr-1 text-primary" aria-hidden="true"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                </span>
                <span class="badge badge-light border text-muted">
                    <i class="icon-graduation2 mr-1 text-secondary" aria-hidden="true"></i> {{ __('Session') }}: <strong>{{ Qs::getCurrentSession() }}</strong>
                </span>
            </div>
            <h2 class="font-weight-bold text-dark mb-0" style="font-size: 1.35rem;">{{ __('Create Payment & Fee Structure') }}</h2>
        </div>
        <div class="d-flex align-items-center mt-3 mt-md-0" style="gap: 0.5rem;">
            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="icon-list mr-1" aria-hidden="true"></i> {{ __('Manage Fee Structures') }}
            </a>
            <a href="{{ route('payments.monthly_billing') }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-calendar52 mr-1" aria-hidden="true"></i> {{ __('Monthly Invoicing') }}
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Form Column --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="font-weight-bold mb-0 text-dark">
                        <i class="icon-plus-circle2 mr-2 text-primary" aria-hidden="true"></i>{{ __('New Fee Item Definition') }}
                    </h5>
                    <span class="text-muted font-size-xs">{{ __('Define the fee title, target class, payment method, and amount in NPR.') }}</span>
                </div>

                <div class="card-body p-4">
                    <form class="ajax-store" method="post" action="{{ route('payments.store') }}">
                        @csrf
                        <div class="form-group row align-items-center">
                            <label class="col-lg-3 col-form-label font-weight-semibold">
                                {{ __('Fee Title') }} <span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                <input name="title" value="{{ old('title') }}" required type="text" class="form-control" placeholder="{{ __('e.g. Monthly Tuition Fee, Examination Fee, Annual Development') }}">
                            </div>
                        </div>

                        <div class="form-group row align-items-center">
                            <label for="my_class_id" class="col-lg-3 col-form-label font-weight-semibold">
                                {{ __('Target Class') }}
                            </label>
                            <div class="col-lg-9">
                                <select class="form-control select-search" name="my_class_id" id="my_class_id">
                                    <option value="">{{ __('All Classes (Whole School Structure)') }}</option>
                                    @foreach($my_classes as $c)
                                        <option {{ old('my_class_id') == $c->id ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    {{ __('Leave as "All Classes" for universal charges, or specify a particular grade.') }}
                                </small>
                            </div>
                        </div>

                        <div class="form-group row align-items-center">
                            <label for="method" class="col-lg-3 col-form-label font-weight-semibold">
                                {{ __('Payment Channel') }}
                            </label>
                            <div class="col-lg-9">
                                <select class="form-control select" name="method" id="method">
                                    <option value="Cash" {{ old('method') == 'Cash' ? 'selected' : '' }}>{{ __('Cash at Counter') }}</option>
                                    <option value="Bank Transfer" {{ old('method') == 'Bank Transfer' ? 'selected' : '' }}>{{ __('Bank Transfer / Direct Deposit') }}</option>
                                    <option value="Digital Wallet" {{ old('method') == 'Digital Wallet' ? 'selected' : '' }}>{{ __('Digital Wallet (eSewa / Khalti / ConnectIPS)') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row align-items-center">
                            <label for="amount" class="col-lg-3 col-form-label font-weight-semibold">
                                {{ __('Amount (NPR)') }} <span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold bg-light">रु</span>
                                    </div>
                                    <input class="form-control font-weight-bold" value="{{ old('amount') }}" required name="amount" id="amount" type="number" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="form-group row align-items-center">
                            <label for="description" class="col-lg-3 col-form-label font-weight-semibold">
                                {{ __('Description / Remarks') }}
                            </label>
                            <div class="col-lg-9">
                                <input class="form-control" value="{{ old('description') }}" name="description" id="description" type="text" placeholder="{{ __('Optional notes or fee breakdown info') }}">
                            </div>
                        </div>

                        <div class="text-right pt-3 border-top mt-4">
                            <a href="{{ route('payments.index') }}" class="btn btn-secondary mr-2">{{ __('Cancel') }}</a>
                            <button id="ajax-btn" type="submit" class="btn btn-primary px-4 font-weight-semibold">
                                {{ __('Save Fee Structure') }} <i class="icon-paperplane ml-2" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Guidance Sidebar Column --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: #F8FAFC;">
                <div class="card-body p-4">
                    <h6 class="font-weight-bold text-dark mb-2">
                        <i class="icon-info22 mr-2 text-info" aria-hidden="true"></i>{{ __('Fee Structure Principles') }}
                    </h6>
                    <ul class="text-muted font-size-sm pl-3 mb-3" style="line-height: 1.8;">
                        <li><strong>{{ __('Universal vs Grade-Specific') }}:</strong> {{ __('Fees created without selecting a class automatically apply across all student cohorts.') }}</li>
                        <li><strong>{{ __('Bikram Sambat Integration') }}:</strong> {{ __('Recurring fees can be billed monthly using the 12-Month Nepali calendar generator.') }}</li>
                        <li><strong>{{ __('Audit Trail') }}:</strong> {{ __('Every generated invoice is tracked with unique reference numbers and receipts.') }}</li>
                    </ul>

                    <div class="p-3 bg-white rounded border">
                        <span class="font-weight-bold text-dark font-size-xs text-uppercase d-block mb-1">{{ __('Quick Financial Actions') }}</span>
                        <div class="d-flex flex-column gap-2" style="gap: 6px;">
                            <a href="{{ route('payments.monthly_billing') }}" class="btn btn-sm btn-outline-primary text-left">
                                <i class="icon-calendar52 mr-2"></i>{{ __('Monthly Billing Invoices') }}
                            </a>
                            <a href="{{ route('payments.manage') }}" class="btn btn-sm btn-outline-success text-left">
                                <i class="icon-cash3 mr-2"></i>{{ __('Collect Student Fees') }}
                            </a>
                            <a href="{{ route('payments.reports') }}" class="btn btn-sm btn-outline-info text-left">
                                <i class="icon-chart mr-2"></i>{{ __('Annual Collection Reports') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
