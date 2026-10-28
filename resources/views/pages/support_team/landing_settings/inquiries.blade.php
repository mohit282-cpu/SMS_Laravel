@extends('layouts.master')
@section('page_title', __('Admissions Inquiries Management'))

@section('content')

    <!-- Subnav -->
    <div class="card mb-3">
        <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <a href="{{ route('landing_settings.index') }}" class="btn btn-light btn-sm mr-2 font-weight-semibold">
                    <i class="icon-gear mr-1"></i> {{ __('General & Admissions Controls') }}
                </a>
                <a href="{{ route('landing_settings.inquiries') }}" class="btn btn-primary btn-sm mr-2 font-weight-semibold">
                    <i class="icon-bubbles4 mr-1"></i> {{ __('View Inquiries') }} ({{ $inquiries->total() }})
                </a>
                <a href="{{ route('landing_settings.entrance_results') }}" class="btn btn-light btn-sm font-weight-semibold">
                    <i class="icon-file-text2 mr-1"></i> {{ __('Manage Entrance Exam Results') }}
                </a>
            </div>
            <div>
                <a href="{{ route('landing.contact') }}" target="_blank" class="btn btn-outline-dark btn-sm font-weight-semibold">
                    <i class="icon-eye mr-1"></i> {{ __('View Public Admissions Desk') }} ↗
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="font-weight-bold text-dark">{{ __('Filter by Status') }}:</span>
                <div class="btn-group">
                    <a href="{{ route('landing_settings.inquiries') }}" class="btn btn-sm {{ empty($status) ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('All Inquiries') }}</a>
                    <a href="{{ route('landing_settings.inquiries', ['status' => 'pending']) }}" class="btn btn-sm {{ $status === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">{{ __('Pending') }}</a>
                    <a href="{{ route('landing_settings.inquiries', ['status' => 'contacted']) }}" class="btn btn-sm {{ $status === 'contacted' ? 'btn-info' : 'btn-outline-info' }}">{{ __('Contacted') }}</a>
                    <a href="{{ route('landing_settings.inquiries', ['status' => 'admitted']) }}" class="btn btn-sm {{ $status === 'admitted' ? 'btn-success' : 'btn-outline-success' }}">{{ __('Admitted') }}</a>
                    <a href="{{ route('landing_settings.inquiries', ['status' => 'archived']) }}" class="btn btn-sm {{ $status === 'archived' ? 'btn-secondary' : 'btn-outline-secondary' }}">{{ __('Archived') }}</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Inquiries Table -->
    <div class="card">
        <div class="card-header header-elements-inline">
            <h2 class="card-title font-weight-bold mb-0" style="font-size: 1.15rem;">
                <i class="icon-bubbles4 mr-2 text-primary"></i> {{ __('Admissions Inquiries List') }}
            </h2>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body p-0">
            @if(count($inquiries) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr class="bg-light text-dark font-weight-bold">
                                <th>#</th>
                                <th>{{ __('Applicant Name') }}</th>
                                <th>{{ __('Contact Phone') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Seeking Level') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Received Date') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inquiries as $inq)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold text-dark">{{ $inq->full_name }}</td>
                                    <td><a href="tel:{{ $inq->phone }}"><i class="icon-phone2 mr-1"></i>{{ $inq->phone }}</a></td>
                                    <td>{{ $inq->email ?: 'N/A' }}</td>
                                    <td><span class="badge badge-light border">{{ $inq->academic_level ?: 'General' }}</span></td>
                                    <td>
                                        @if($inq->status === 'pending')
                                            <span class="badge badge-warning">{{ __('Pending') }}</span>
                                        @elseif($inq->status === 'contacted')
                                            <span class="badge badge-info">{{ __('Contacted') }}</span>
                                        @elseif($inq->status === 'admitted')
                                            <span class="badge badge-success">{{ __('Admitted') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Archived') }}</span>
                                        @endif
                                    </td>
                                    <td class="font-size-xs text-muted">{{ $inq->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-menu9"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <button type="button" class="dropdown-item" onclick="viewInquiry({{ json_encode($inq) }})">
                                                        <i class="icon-eye mr-2"></i> {{ __('View Message & Notes') }}
                                                    </button>
                                                    <form method="post" action="{{ route('landing_settings.inquiries.status', $inq->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="contacted">
                                                        <button type="submit" class="dropdown-item"><i class="icon-checkmark mr-2 text-info"></i> {{ __('Mark Contacted') }}</button>
                                                    </form>
                                                    <form method="post" action="{{ route('landing_settings.inquiries.status', $inq->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="admitted">
                                                        <button type="submit" class="dropdown-item"><i class="icon-star-full2 mr-2 text-success"></i> {{ __('Mark Admitted') }}</button>
                                                    </form>
                                                    <div class="dropdown-divider"></div>
                                                    <form method="post" action="{{ route('landing_settings.inquiries.delete', $inq->id) }}" onsubmit="return confirm('Are you sure you want to delete this inquiry record?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"><i class="icon-trash mr-2"></i> {{ __('Delete Record') }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 d-flex justify-content-center">
                    {{ $inquiries->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="icon-bubbles4 icon-3x text-muted mb-2"></i>
                    <h4 class="text-muted font-weight-bold">{{ __('No inquiries found.') }}</h4>
                </div>
            @endif
        </div>
    </div>

    <!-- Inquiry Details Modal -->
    <div id="inquiryModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="icon-bubbles4 mr-2"></i>{{ __('Inquiry Details') }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="font-weight-bold text-muted font-size-xs text-uppercase">{{ __('Applicant') }}:</label>
                        <h4 id="modal_name" class="font-weight-bold text-dark mb-0"></h4>
                        <div id="modal_level" class="text-primary font-weight-semibold font-size-sm"></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="font-weight-bold text-muted font-size-xs text-uppercase">{{ __('Phone') }}:</label>
                            <div id="modal_phone" class="font-weight-bold text-dark"></div>
                        </div>
                        <div class="col-6">
                            <label class="font-weight-bold text-muted font-size-xs text-uppercase">{{ __('Email') }}:</label>
                            <div id="modal_email" class="text-dark"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="font-weight-bold text-muted font-size-xs text-uppercase">{{ __('Inquiry Message') }}:</label>
                        <div id="modal_msg" class="p-3 bg-light rounded border text-dark font-size-sm" style="white-space: pre-wrap;"></div>
                    </div>

                    <form id="modal_status_form" method="post" action="">
                        @csrf
                        <div class="form-group">
                            <label class="font-weight-bold">{{ __('Update Status') }}:</label>
                            <select name="status" id="modal_status_select" class="form-control">
                                <option value="pending">{{ __('Pending Review') }}</option>
                                <option value="contacted">{{ __('Contacted / Follow-up Sent') }}</option>
                                <option value="admitted">{{ __('Admitted to School') }}</option>
                                <option value="archived">{{ __('Archived') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">{{ __('Internal Counselor Notes') }}:</label>
                            <textarea name="admin_notes" id="modal_notes" rows="2" class="form-control" placeholder="Add confidential notes about phone call or entrance interview..."></textarea>
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">{{ __('Close') }}</button>
                            <button type="submit" class="btn btn-primary font-weight-bold">{{ __('Save Changes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    function viewInquiry(item) {
        document.getElementById('modal_name').innerText = item.full_name;
        document.getElementById('modal_level').innerText = item.academic_level || 'General Admission';
        document.getElementById('modal_phone').innerText = item.phone;
        document.getElementById('modal_email').innerText = item.email || 'N/A';
        document.getElementById('modal_msg').innerText = item.message;
        document.getElementById('modal_status_select').value = item.status;
        document.getElementById('modal_notes').value = item.admin_notes || '';

        const form = document.getElementById('modal_status_form');
        form.action = "{{ url('landing-settings/inquiries') }}/" + item.id + "/status";

        $('#inquiryModal').modal('show');
    }
</script>
@endsection
