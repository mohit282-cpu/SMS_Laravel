@extends('layouts.master')
@section('page_title', __('Manage Book Requests'))
@section('content')

    <div class="row">
        <div class="col-sm-6 col-xl-4">
            <div class="card card-body bg-warning-400 has-bg-image">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-semibold">{{ $stats['pending'] ?? count($pending_requests) }}</h3>
                        <span class="text-uppercase font-size-xs">{{ __('Pending Book Requests') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-hour-glass2 icon-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card card-body bg-success-400 has-bg-image">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-semibold">{{ $stats['approved'] ?? count($approved_requests) }}</h3>
                        <span class="text-uppercase font-size-xs">{{ __('Approved (Ready to Issue)') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-checkmark4 icon-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card card-body bg-danger-400 has-bg-image">
                <div class="media">
                    <div class="media-body">
                        <h3 class="mb-0 font-weight-semibold">{{ $stats['rejected'] ?? 0 }}</h3>
                        <span class="text-uppercase font-size-xs">{{ __('Rejected / Cancelled') }}</span>
                    </div>
                    <div class="ml-3 align-self-center">
                        <i class="icon-cross2 icon-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.1rem;"><i class="icon-list3 mr-2" style="color: #FF5700;"></i>{{ __('Book Requests Management') }}</h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item">
                    <a href="#pending-req" class="nav-link active" data-toggle="tab">
                        <i class="icon-hour-glass2 mr-2 text-warning"></i>
                        {{ __('Pending Requests') }} ({{ count($pending_requests) }})
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#approved-req" class="nav-link" data-toggle="tab">
                        <i class="icon-checkmark4 mr-2 text-success"></i>
                        {{ __('Approved (Awaiting Collection)') }} ({{ count($approved_requests) }})
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#past-req" class="nav-link" data-toggle="tab">
                        <i class="icon-history mr-2"></i>
                        {{ __('Archived / Rejected') }} ({{ count($past_requests) }})
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Tab 1: Pending Requests --}}
                <div class="tab-pane fade show active" id="pending-req">
                    @if(count($pending_requests) > 0)
                        <table class="table datatable-button-html5-columns table-striped table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Book Title') }}</th>
                                <th>{{ __('Requester') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Available Stock') }}</th>
                                <th>{{ __('Request Date') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pending_requests as $req)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('books.show', $req->book_id) }}" class="font-weight-semibold">
                                            {{ $req->book ? $req->book->name : '-' }}
                                        </a>
                                        @if($req->book && $req->book->category)
                                            <div class="text-muted font-size-xs">{{ $req->book->category->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-semibold">{{ $req->user ? $req->user->name : '-' }}</div>
                                        <div class="text-muted font-size-xs">{{ $req->user ? $req->user->email : '' }}</div>
                                    </td>
                                    <td><span class="badge badge-light border-left-info badge-striped">{{ ucfirst($req->borrower_type) }}</span></td>
                                    <td>
                                        @if($req->book)
                                            <span class="badge {{ $req->book->available_copies > 0 ? 'badge-success' : 'badge-danger' }}">
                                                {{ $req->book->available_copies }} {{ __('Available') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $req->request_date_bs ?: $req->created_at->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <form method="post" action="{{ route('book_requests.approve', $req->id) }}" class="d-inline mr-1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success font-weight-bold" {{ ($req->book && $req->book->available_copies == 0) ? 'disabled' : '' }}>
                                                    <i class="icon-checkmark mr-1"></i> {{ __('Approve') }}
                                                </button>
                                            </form>
                                            <form method="post" action="{{ route('book_requests.reject', $req->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Reject this book request?') }}')">
                                                    <i class="icon-cross2 mr-1"></i> {{ __('Reject') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="icon-checkmark-circle icon-2x mb-2 text-success"></i>
                            <p class="mb-0">{{ __('No pending book requests at this moment.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Tab 2: Approved Requests --}}
                <div class="tab-pane fade" id="approved-req">
                    @if(count($approved_requests) > 0)
                        <table class="table datatable-button-html5-columns table-striped table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Book Title') }}</th>
                                <th>{{ __('Requester') }}</th>
                                <th>{{ __('Approved By') }}</th>
                                <th>{{ __('Approved Date') }}</th>
                                <th class="text-center">{{ __('Circulate') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($approved_requests as $app)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('books.show', $app->book_id) }}" class="font-weight-semibold">
                                            {{ $app->book ? $app->book->name : '-' }}
                                        </a>
                                    </td>
                                    <td>{{ $app->user ? $app->user->name : '-' }}</td>
                                    <td>{{ $app->approver ? $app->approver->name : '-' }}</td>
                                    <td>{{ $app->updated_at->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        <form method="post" action="{{ route('book_requests.issue_from_request', $app->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary font-weight-bold" onclick="return confirm('{{ __('Issue physical copy to the borrower now?') }}')">
                                                <i class="icon-reading mr-1"></i> {{ __('Issue to Borrower') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center text-muted py-4">
                            <p class="mb-0">{{ __('No approved requests waiting for collection.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Tab 3: Archived / Rejected --}}
                <div class="tab-pane fade" id="past-req">
                    <table class="table datatable-button-html5-columns table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Book Title') }}</th>
                            <th>{{ __('Requester') }}</th>
                            <th>{{ __('Requested On') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($past_requests as $past)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $past->book ? $past->book->name : '-' }}</td>
                                <td>{{ $past->user ? $past->user->name : '-' }}</td>
                                <td>{{ $past->request_date_bs ?: $past->created_at->format('Y-m-d') }}</td>
                                <td>
                                    @if($past->status == 'issued')
                                        <span class="badge badge-success">{{ __('Issued / Fulfilled') }}</span>
                                    @elseif($past->status == 'rejected')
                                        <span class="badge badge-danger">{{ __('Rejected') }}</span>
                                    @elseif($past->status == 'cancelled')
                                        <span class="badge badge-secondary">{{ __('Cancelled by User') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
