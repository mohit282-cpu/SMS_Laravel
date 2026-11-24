@extends('layouts.master')
@section('page_title', __('Manage Book Requests'))
@section('content')

    {{-- Executive Header --}}
    <div class="card sms-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="d-inline-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 87, 0, 0.12);">
                        <i class="icon-hour-glass2 icon-2x" style="color: #FF5700;"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold mb-1 text-dark" style="font-size: 1.35rem; letter-spacing: -0.01em;">
                            {{ __('Library Book Requests & Reservations') }}
                        </h3>
                        <p class="text-muted mb-0 font-size-sm">
                            {{ __('Student & faculty borrow applications, queue approvals & collection dispatch') }}
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
                    <span class="badge badge-light border px-3 py-2 text-dark font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-calendar22 mr-1 text-primary"></i> {{ \App\Helpers\NepaliHelper::formatDualDate(date('Y-m-d')) }}
                    </span>
                    <span class="badge badge-primary px-3 py-2 font-size-sm shadow-sm" style="border-radius: 8px;">
                        <i class="icon-graduation mr-1"></i> {{ __('Session') }}: {{ Qs::getSetting('current_session') }}
                    </span>
                    <a href="{{ route('books.index') }}" class="btn btn-outline-secondary btn-sm font-weight-semibold shadow-sm ml-md-2" style="border-radius: 8px;">
                        <i class="icon-books mr-1"></i>{{ __('Inventory') }}
                    </a>
                    <a href="{{ route('books.circulations') }}" class="btn btn-primary btn-sm font-weight-semibold shadow-sm" style="border-radius: 8px;">
                        <i class="icon-sync mr-1"></i>{{ __('Circulations') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Metric KPI Stat Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Pending Requests') }}</span>
                            <h3 class="font-weight-bold text-warning mb-0 mt-1">{{ number_format($stats['pending'] ?? count($pending_requests)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Awaiting review') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1);">
                            <i class="icon-hour-glass2 icon-2x" style="color: #f59e0b;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Approved (Ready)') }}</span>
                            <h3 class="font-weight-bold text-success mb-0 mt-1">{{ number_format($stats['approved'] ?? count($approved_requests)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Ready for checkout') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1);">
                            <i class="icon-checkmark4 icon-2x" style="color: #10b981;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Rejected / Cancelled') }}</span>
                            <h3 class="font-weight-bold text-danger mb-0 mt-1">{{ number_format($stats['rejected'] ?? 0) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Unfulfilled applications') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239, 68, 68, 0.1);">
                            <i class="icon-cross2 icon-2x" style="color: #ef4444;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card sms-stat-card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted font-size-xs text-uppercase font-weight-bold tracking-wider">{{ __('Total Inquiries') }}</span>
                            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format(($stats['pending'] ?? count($pending_requests)) + ($stats['approved'] ?? count($approved_requests)) + ($stats['rejected'] ?? 0)) }}</h3>
                            <span class="font-size-xs text-muted">{{ __('Lifetime borrower queue') }}</span>
                        </div>
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1);">
                            <i class="icon-list3 icon-2x" style="color: #4f46e5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Requests Card --}}
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header d-flex align-items-center justify-content-between bg-white border-bottom py-3">
            <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">
                <i class="icon-list3 mr-2" style="color: #FF5700;"></i>{{ __('Book Requests Management') }}
            </h5>
            <div class="d-flex align-items-center">
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs nav-tabs-highlight mb-4">
                <li class="nav-item">
                    <a href="#pending-req" class="nav-link active font-weight-semibold" data-toggle="tab">
                        <i class="icon-hour-glass2 mr-1 text-warning"></i>
                        {{ __('Pending Requests') }}
                        <span class="badge badge-warning text-white ml-1 font-size-xs">{{ count($pending_requests) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#approved-req" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-checkmark4 mr-1 text-success"></i>
                        {{ __('Approved (Awaiting Collection)') }}
                        <span class="badge badge-success ml-1 font-size-xs">{{ count($approved_requests) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#past-req" class="nav-link font-weight-semibold" data-toggle="tab">
                        <i class="icon-history mr-1"></i>
                        {{ __('Archived / Rejected') }}
                        <span class="badge badge-secondary ml-1 font-size-xs">{{ count($past_requests) }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Tab 1: Pending Requests --}}
                <div class="tab-pane fade show active" id="pending-req">
                    @if(count($pending_requests) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-striped datatable-button-html5-columns">
                                <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('Book Title') }}</th>
                                    <th>{{ __('Requester') }}</th>
                                    <th>{{ __('Role') }}</th>
                                    <th>{{ __('Available Stock') }}</th>
                                    <th>{{ __('Request Date') }}</th>
                                    <th class="text-center" style="width: 170px;">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($pending_requests as $req)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">
                                                <a href="{{ route('books.show', $req->book_id) }}">{{ $req->book ? $req->book->name : '-' }}</a>
                                            </div>
                                            @if($req->book && $req->book->category)
                                                <div class="text-muted font-size-xs">{{ $req->book->category->name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">{{ $req->user ? $req->user->name : '-' }}</div>
                                            <div class="text-muted font-size-xs">{{ $req->user ? $req->user->email : '' }}</div>
                                        </td>
                                        <td><span class="badge badge-light border">{{ ucfirst($req->borrower_type) }}</span></td>
                                        <td>
                                            @if($req->book)
                                                <span class="badge {{ $req->book->available_copies > 0 ? 'badge-success' : 'badge-danger' }}" style="border-radius: 6px; padding: 4px 8px;">
                                                    {{ $req->book->available_copies }} {{ __('Available') }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $req->request_date_bs ?: $req->created_at->format('Y-m-d') }}</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <form method="post" action="{{ route('book_requests.approve', $req->id) }}" class="d-inline mr-1">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success font-weight-semibold" style="border-radius: 6px;" {{ ($req->book && $req->book->available_copies == 0) ? 'disabled' : '' }}>
                                                        <i class="icon-checkmark mr-1"></i> {{ __('Approve') }}
                                                    </button>
                                                </form>
                                                <form method="post" action="{{ route('book_requests.reject', $req->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px;" onclick="return confirm('{{ __('Reject this book request?') }}')">
                                                        <i class="icon-cross2 mr-1"></i> {{ __('Reject') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="icon-checkmark-circle icon-2x mb-2 d-block text-success"></i>
                            <span class="font-weight-medium text-success">{{ __('No pending book requests at this moment.') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Tab 2: Approved Requests --}}
                <div class="tab-pane fade" id="approved-req">
                    @if(count($approved_requests) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-striped datatable-button-html5-columns">
                                <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('Book Title') }}</th>
                                    <th>{{ __('Requester') }}</th>
                                    <th>{{ __('Approved By') }}</th>
                                    <th>{{ __('Approved Date') }}</th>
                                    <th class="text-center" style="width: 160px;">{{ __('Circulate') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($approved_requests as $app)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">
                                                <a href="{{ route('books.show', $app->book_id) }}">{{ $app->book ? $app->book->name : '-' }}</a>
                                            </div>
                                        </td>
                                        <td>{{ $app->user ? $app->user->name : '-' }}</td>
                                        <td>{{ $app->approver ? $app->approver->name : '-' }}</td>
                                        <td>{{ $app->updated_at->format('Y-m-d') }}</td>
                                        <td class="text-center">
                                            <form method="post" action="{{ route('book_requests.issue_from_request', $app->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm text-white font-weight-semibold shadow-sm" style="background: #FF5700; border-radius: 6px;" onclick="return confirm('{{ __('Issue physical copy to the borrower now?') }}')">
                                                    <i class="icon-reading mr-1"></i> {{ __('Issue to Borrower') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="icon-info22 icon-2x mb-2 d-block text-muted"></i>
                            <span class="font-weight-medium">{{ __('No approved requests waiting for collection.') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Tab 3: Archived / Rejected --}}
                <div class="tab-pane fade" id="past-req">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable-button-html5-columns">
                            <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Book Title') }}</th>
                                <th>{{ __('Requester') }}</th>
                                <th>{{ __('Requested On') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($past_requests as $past)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-semibold text-dark">{{ $past->book ? $past->book->name : '-' }}</div>
                                    </td>
                                    <td>{{ $past->user ? $past->user->name : '-' }}</td>
                                    <td>{{ $past->request_date_bs ?: $past->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        @if($past->status == 'issued')
                                            <span class="badge badge-success" style="border-radius: 6px; padding: 4px 8px;">{{ __('Issued / Fulfilled') }}</span>
                                        @elseif($past->status == 'rejected')
                                            <span class="badge badge-danger" style="border-radius: 6px; padding: 4px 8px;">{{ __('Rejected') }}</span>
                                        @elseif($past->status == 'cancelled')
                                            <span class="badge badge-secondary" style="border-radius: 6px; padding: 4px 8px;">{{ __('Cancelled by User') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="icon-history icon-2x mb-2 d-block text-muted"></i>
                                        <span class="font-weight-medium">{{ __('No historical or archived requests found.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
