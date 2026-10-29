@extends('layouts.master')
@section('page_title', __('Activity & Audit Logs'))
@section('content')

<style>
    /* Glassmorphism theme tokens */
    .glass-panel {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 12px;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        transition: all 0.25s ease;
    }
    .glass-card {
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.6);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.85) 100%);
        backdrop-filter: blur(12px);
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
        padding: 1.15rem 1.25rem;
        position: relative;
        overflow: hidden;
    }
    .glass-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }
    .glass-card-primary::before { background: linear-gradient(90deg, #3B82F6, #1D4ED8); }
    .glass-card-success::before { background: linear-gradient(90deg, #10B981, #059669); }
    .glass-card-purple::before { background: linear-gradient(90deg, #8B5CF6, #6D28D9); }
    .glass-card-warning::before { background: linear-gradient(90deg, #F59E0B, #D97706); }
    .glass-card-danger::before { background: linear-gradient(90deg, #EF4444, #B91C1C); }

    .pulse-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #10B981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .table-row-new {
        animation: highlight-row 3s ease-out;
    }
    @keyframes highlight-row {
        0% { background-color: rgba(254, 243, 199, 0.95); }
        100% { background-color: transparent; }
    }
    .code-diff-box {
        background: #0F172A;
        color: #F8FAFC;
        border-radius: 8px;
        padding: 0.85rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.82rem;
        max-height: 280px;
        overflow-y: auto;
    }
    .badge-soft-success { background: rgba(16, 185, 129, 0.12); color: #047857; border: 1px solid rgba(16, 185, 129, 0.25); }
    .badge-soft-danger { background: rgba(239, 68, 68, 0.12); color: #B91C1C; border: 1px solid rgba(239, 68, 68, 0.25); }
    .badge-soft-info { background: rgba(59, 130, 246, 0.12); color: #1D4ED8; border: 1px solid rgba(59, 130, 246, 0.25); }
    .badge-soft-warning { background: rgba(245, 158, 11, 0.12); color: #B45309; border: 1px solid rgba(245, 158, 11, 0.25); }
    .badge-soft-secondary { background: rgba(100, 116, 139, 0.12); color: #334155; border: 1px solid rgba(100, 116, 139, 0.25); }
</style>

<div class="content">

    {{-- HEADER BAR --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
            <h4 class="font-weight-bold mb-1 text-slate-800" style="letter-spacing: -0.02em;">
                <i class="icon-shield-check text-primary mr-2" style="font-size: 1.35rem;"></i>
                {{ __('Activity & Audit Logs') }}
            </h4>
            <div class="text-muted small">
                {{ __('Immutable server-side audit trail tracking critical academic, administrative, and security operations.') }}
            </div>
        </div>
        <div class="d-flex align-items-center mt-2 mt-md-0">
            <div class="mr-2 px-3 py-1 bg-white rounded-pill border d-flex align-items-center shadow-sm">
                <span id="liveStatusDot" class="pulse-dot mr-2"></span>
                <span id="liveStatusText" class="small font-weight-semibold text-secondary">{{ __('Live Stream Active') }}</span>
                <span id="liveCountBadge" class="badge badge-primary badge-pill ml-2" style="display:none;">0</span>
            </div>
            <a href="{{ route('audit_logs.export', request()->all()) }}" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm" title="{{ __('Export CSV') }}">
                <i class="icon-download4 mr-1"></i> {{ __('Export CSV') }}
            </a>
        </div>
    </div>

    {{-- SUMMARY METRIC CARDS --}}
    <div class="row mb-3">
        <div class="col-6 col-lg">
            <div class="glass-card glass-card-primary mb-2 mb-lg-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Total Events') }}</div>
                        <h3 class="font-weight-bold mb-0 text-slate-900 mt-1" id="metricTotal">{{ number_format($metrics['total_events']) }}</h3>
                    </div>
                    <div class="bg-blue-50 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="icon-history" style="font-size: 1.25rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="glass-card glass-card-success mb-2 mb-lg-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Events Today') }}</div>
                        <h3 class="font-weight-bold mb-0 text-emerald-700 mt-1" id="metricToday">{{ number_format($metrics['events_today']) }}</h3>
                    </div>
                    <div class="bg-emerald-50 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="icon-calendar" style="font-size: 1.25rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="glass-card glass-card-purple mb-2 mb-lg-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Active Actors') }}</div>
                        <h3 class="font-weight-bold mb-0 text-purple-700 mt-1" id="metricActors">{{ number_format($metrics['active_actors']) }}</h3>
                    </div>
                    <div class="bg-purple-50 text-indigo rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="icon-users4" style="font-size: 1.25rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="glass-card glass-card-warning mb-2 mb-lg-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Security Events') }}</div>
                        <h3 class="font-weight-bold mb-0 text-amber-700 mt-1" id="metricSecurity">{{ number_format($metrics['security_events']) }}</h3>
                    </div>
                    <div class="bg-amber-50 text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="icon-shield-check" style="font-size: 1.25rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg">
            <div class="glass-card glass-card-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Failed Logins') }}</div>
                        <h3 class="font-weight-bold mb-0 text-danger mt-1" id="metricFailedLogins">{{ number_format($metrics['failed_logins']) }}</h3>
                    </div>
                    <div class="bg-rose-50 text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="icon-shield-notice" style="font-size: 1.25rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER & SEARCH PANEL --}}
    <div class="glass-panel p-3 mb-3">
        <form method="GET" action="{{ route('audit_logs.index') }}" id="auditFilterForm">
            <div class="row align-items-end">
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="font-weight-semibold text-secondary small mb-1">{{ __('Search') }}</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="icon-search4"></i></span>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Actor, description, IP...') }}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="font-weight-semibold text-secondary small mb-1">{{ __('Category') }}</label>
                    <select name="category" class="form-control form-control-sm">
                        <option value="all">{{ __('All Categories') }}</option>
                        @foreach($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" {{ request('category') === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="font-weight-semibold text-secondary small mb-1">{{ __('Status') }}</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="all">{{ __('All Statuses') }}</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>{{ __('Success') }}</option>
                        <option value="failure" {{ request('status') === 'failure' ? 'selected' : '' }}>{{ __('Failure') }}</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="font-weight-semibold text-secondary small mb-1">{{ __('Actor') }}</label>
                    <select name="actor_id" class="form-control form-control-sm">
                        <option value="all">{{ __('All Actors') }}</option>
                        @foreach($actors as $actor)
                            <option value="{{ $actor->id }}" {{ request('actor_id') == $actor->id ? 'selected' : '' }}>
                                {{ $actor->name }} ({{ $actor->user_type }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-sm-12 mb-2 d-flex">
                    <div class="mr-1 flex-fill">
                        <label class="font-weight-semibold text-secondary small mb-1">{{ __('From') }}</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                    </div>
                    <div class="flex-fill">
                        <label class="font-weight-semibold text-secondary small mb-1">{{ __('To') }}</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <div class="d-flex align-items-center">
                    <span class="small text-muted mr-2">{{ __('Sort:') }}</span>
                    <select name="sort" class="form-control form-control-sm d-inline-block" style="width: auto;">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>{{ __('Newest First') }}</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('Oldest First') }}</option>
                    </select>
                </div>
                <div>
                    <a href="{{ route('audit_logs.index') }}" class="btn btn-light btn-sm mr-1">{{ __('Reset') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="icon-filter3 mr-1"></i> {{ __('Apply Filters') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- AUDIT LOGS STREAM TABLE --}}
    <div class="glass-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle" id="auditLogsTable">
                <thead class="bg-light text-secondary text-uppercase font-weight-bold" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="min-width: 140px;">{{ __('Event / Category') }}</th>
                        <th style="min-width: 180px;">{{ __('Actor') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th style="min-width: 130px;">{{ __('Network / Ref') }}</th>
                        <th style="min-width: 160px;">{{ __('Timestamp (AD / BS)') }}</th>
                        <th style="width: 90px;" class="text-center">{{ __('Status') }}</th>
                        <th style="width: 80px;" class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody id="auditLogsTbody">
                    @forelse($logs as $log)
                        <tr id="log-row-{{ $log->id }}" data-log-id="{{ $log->id }}">
                            <td class="font-weight-semibold text-muted small">{{ $log->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-pill {{ $log->category_badge_class }} mr-2 p-1" title="{{ ucfirst($log->category) }}">
                                        <i class="{{ $log->category_icon }}" style="font-size: 0.85rem;"></i>
                                    </span>
                                    <div>
                                        <div class="font-weight-semibold text-dark text-truncate" style="max-width: 140px;" title="{{ $log->action }}">
                                            {{ $log->action }}
                                        </div>
                                        <div class="small text-muted">{{ ucfirst($log->category) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="font-weight-semibold text-dark">
                                    {{ $log->actor_name_snapshot ?: ($log->actor ? $log->actor->name : 'System') }}
                                </div>
                                <div>
                                    <span class="badge badge-soft-secondary font-weight-normal" style="font-size: 0.7rem;">
                                        {{ $log->actor_role_snapshot ?: ($log->actor ? $log->actor->user_type : 'system') }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="text-slate-800" style="font-size: 0.88rem;">
                                    {{ $log->description }}
                                </div>
                                @if($log->auditable_type)
                                    <div class="small text-muted mt-1 font-italic" style="font-size: 0.75rem;">
                                        <i class="icon-cube mr-1"></i> {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="small text-muted">
                                    <i class="icon-lan2 mr-1"></i> {{ $log->ip_address ?: '—' }}
                                </div>
                                @if($log->request_id)
                                    <div class="small text-muted font-monospace text-truncate" style="max-width: 120px;" title="{{ $log->request_id }}">
                                        <i class="icon-link mr-1"></i> {{ substr($log->request_id, 0, 8) }}...
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-semibold text-dark" style="font-size: 0.82rem;">
                                    {{ $log->formatted_ad_date }}
                                </div>
                                <div class="small text-muted">
                                    <i class="icon-calendar2 mr-1"></i> {{ $log->formatted_bs_date }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($log->status === 'success')
                                    <span class="badge badge-soft-success font-weight-semibold px-2 py-1">{{ __('Success') }}</span>
                                @else
                                    <span class="badge badge-soft-danger font-weight-semibold px-2 py-1">{{ __('Failure') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-primary btn-sm btn-icon rounded-circle inspect-log-btn" data-id="{{ $log->id }}" title="{{ __('Inspect Details') }}">
                                    <i class="icon-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="noLogsRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="icon-shield-notice text-slate-300 d-block mb-2" style="font-size: 2.5rem;"></i>
                                <span class="font-weight-semibold">{{ __('No audit logs found matching the specified criteria.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($logs->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small mb-2 mb-md-0">
                    {{ __('Showing') }} {{ $logs->firstItem() }} {{ __('to') }} {{ $logs->lastItem() }} {{ __('of') }} {{ $logs->total() }} {{ __('recorded audit events') }}
                </div>
                <div>
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

{{-- AUDIT EVENT DETAIL MODAL / DRAWER --}}
<div class="modal fade" id="auditLogDetailModal" tabindex="-1" role="dialog" aria-labelledby="auditDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content glass-panel border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom py-2">
                <h6 class="modal-title font-weight-bold" id="auditDetailTitle">
                    <i class="icon-shield-check text-primary mr-1"></i> {{ __('Audit Event Inspection') }} #<span id="modalLogId"></span>
                </h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small text-uppercase font-weight-bold mb-0">{{ __('Action & Category') }}</label>
                        <div class="mt-1">
                            <span id="modalAction" class="font-weight-bold text-dark h6 mb-0"></span>
                            <span id="modalCategoryBadge" class="badge ml-2"></span>
                            <span id="modalStatusBadge" class="badge ml-1"></span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small text-uppercase font-weight-bold mb-0">{{ __('Actor Snapshot') }}</label>
                        <div class="mt-1">
                            <strong id="modalActorName" class="text-dark"></strong>
                            <span class="badge badge-light border ml-1" id="modalActorRole"></span>
                            <span class="text-muted small ml-1" id="modalActorId"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small text-uppercase font-weight-bold mb-0">{{ __('Event Description') }}</label>
                    <div id="modalDescription" class="p-2 bg-light rounded border text-slate-800 mt-1 font-weight-medium"></div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="small border rounded p-2 bg-white">
                            <div class="text-muted small font-weight-bold mb-1"><i class="icon-calendar mr-1"></i> {{ __('Timestamps') }}</div>
                            <div><strong>AD:</strong> <span id="modalTimeAd"></span></div>
                            <div><strong>BS:</strong> <span id="modalTimeBs"></span></div>
                            <div class="text-muted font-italic"><span id="modalTimeAgo"></span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small border rounded p-2 bg-white">
                            <div class="text-muted small font-weight-bold mb-1"><i class="icon-lan2 mr-1"></i> {{ __('Network & Correlation') }}</div>
                            <div><strong>IP:</strong> <span id="modalIp"></span></div>
                            <div><strong>Correlation ID:</strong> <span id="modalRequestId" class="font-monospace"></span></div>
                            <div><strong>Target:</strong> <span id="modalTarget"></span></div>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="text-muted small text-uppercase font-weight-bold mb-1"><i class="icon-browser mr-1"></i> {{ __('User Agent') }}</label>
                    <div id="modalUserAgent" class="small text-muted p-2 bg-light rounded text-truncate border"></div>
                </div>

                {{-- BEFORE / AFTER APPROVED DIFF VIEWER --}}
                <div class="mt-3">
                    <label class="text-muted small text-uppercase font-weight-bold mb-1">
                        <i class="icon-diff mr-1 text-primary"></i> {{ __('State Modification Diff (Approved & Redacted)') }}
                    </label>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="small font-weight-semibold text-danger mb-1">{{ __('Old Values (Before)') }}</div>
                            <pre id="modalOldValues" class="code-diff-box mb-0"></pre>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="small font-weight-semibold text-success mb-1">{{ __('New Values (After)') }}</div>
                            <pre id="modalNewValues" class="code-diff-box mb-0"></pre>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let highestId = 0;
    // Find initial highest log ID on page
    $('#auditLogsTbody tr[data-log-id]').each(function() {
        const id = parseInt($(this).data('log-id'));
        if (id > highestId) {
            highestId = id;
        }
    });

    let pollingActive = true;
    const feedUrl = "{{ route('audit_logs.feed') }}";
    const detailUrlBase = "{{ url('/super_admin/audit_logs') }}";

    // Escape HTML to prevent XSS safely
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Inspect detail handler
    $(document).on('click', '.inspect-log-btn', function() {
        const logId = $(this).data('id');
        $.ajax({
            url: detailUrlBase + '/' + logId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#modalLogId').text(data.id);
                $('#modalAction').text(data.action);
                $('#modalCategoryBadge')
                    .removeClass()
                    .addClass('badge ' + data.category_badge)
                    .text(data.category);
                $('#modalStatusBadge')
                    .removeClass()
                    .addClass('badge ' + (data.status === 'success' ? 'badge-soft-success' : 'badge-soft-danger'))
                    .text(data.status);

                $('#modalActorName').text(data.actor_name);
                $('#modalActorRole').text(data.actor_role);
                $('#modalActorId').text(data.actor_id ? '(ID: ' + data.actor_id + ')' : '');

                $('#modalDescription').text(data.description);
                $('#modalTimeAd').text(data.created_at_ad);
                $('#modalTimeBs').text(data.created_at_bs);
                $('#modalTimeAgo').text(data.created_at_human);

                $('#modalIp').text(data.ip_address);
                $('#modalRequestId').text(data.request_id);
                $('#modalTarget').text(data.auditable_type + ' #' + data.auditable_id);
                $('#modalUserAgent').text(data.user_agent);

                const oldJson = Object.keys(data.old_values).length > 0 ? JSON.stringify(data.old_values, null, 2) : 'No recorded previous values.';
                const newJson = Object.keys(data.new_values).length > 0 ? JSON.stringify(data.new_values, null, 2) : 'No recorded new values.';

                $('#modalOldValues').text(oldJson);
                $('#modalNewValues').text(newJson);

                $('#auditLogDetailModal').modal('show');
            },
            error: function() {
                alert('{{ __("Unable to retrieve event details. Please try again.") }}');
            }
        });
    });

    // Real-time live feed polling
    function pollLiveFeed() {
        if (!pollingActive) return;

        $.ajax({
            url: feedUrl,
            type: 'GET',
            data: { since_id: highestId },
            dataType: 'json',
            timeout: 6000,
            success: function(response) {
                $('#liveStatusDot').removeClass('bg-danger').addClass('pulse-green');
                $('#liveStatusText').text('{{ __("Live Stream Active") }}');

                if (response.success && response.events && response.events.length > 0) {
                    $('#noLogsRow').remove();

                    // Prepend events in chronological reverse so newest appears at top
                    response.events.forEach(function(ev) {
                        if (ev.id > highestId) {
                            highestId = ev.id;
                        }

                        if ($('#log-row-' + ev.id).length === 0) {
                            const statusBadge = ev.status === 'success'
                                ? '<span class="badge badge-soft-success font-weight-semibold px-2 py-1">{{ __("Success") }}</span>'
                                : '<span class="badge badge-soft-danger font-weight-semibold px-2 py-1">{{ __("Failure") }}</span>';

                            const targetHtml = ev.auditable_type
                                ? '<div class="small text-muted mt-1 font-italic"><i class="icon-cube mr-1"></i> ' + escapeHtml(ev.auditable_type) + ' #' + escapeHtml(ev.auditable_id) + '</div>'
                                : '';

                            const rowHtml = '<tr id="log-row-' + ev.id + '" data-log-id="' + ev.id + '" class="table-row-new">' +
                                '<td class="font-weight-semibold text-muted small">' + ev.id + '</td>' +
                                '<td>' +
                                    '<div class="d-flex align-items-center">' +
                                        '<span class="badge badge-pill ' + escapeHtml(ev.category_badge) + ' mr-2 p-1">' +
                                            '<i class="' + escapeHtml(ev.category_icon) + '" style="font-size: 0.85rem;"></i>' +
                                        '</span>' +
                                        '<div>' +
                                            '<div class="font-weight-semibold text-dark text-truncate" style="max-width: 140px;">' + escapeHtml(ev.action) + '</div>' +
                                            '<div class="small text-muted">' + escapeHtml(ev.category) + '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</td>' +
                                '<td>' +
                                    '<div class="font-weight-semibold text-dark">' + escapeHtml(ev.actor_name) + '</div>' +
                                    '<div><span class="badge badge-soft-secondary font-weight-normal" style="font-size: 0.7rem;">' + escapeHtml(ev.actor_role) + '</span></div>' +
                                '</td>' +
                                '<td>' +
                                    '<div class="text-slate-800" style="font-size: 0.88rem;">' + escapeHtml(ev.description) + '</div>' +
                                    targetHtml +
                                '</td>' +
                                '<td>' +
                                    '<div class="small text-muted"><i class="icon-lan2 mr-1"></i> ' + escapeHtml(ev.ip_address) + '</div>' +
                                    '<div class="small text-muted font-monospace text-truncate" style="max-width: 120px;"><i class="icon-link mr-1"></i> ' + escapeHtml(ev.request_id.substring(0, 8)) + '...</div>' +
                                '</td>' +
                                '<td>' +
                                    '<div class="font-weight-semibold text-dark" style="font-size: 0.82rem;">' + escapeHtml(ev.created_at_ad) + '</div>' +
                                    '<div class="small text-muted"><i class="icon-calendar2 mr-1"></i> ' + escapeHtml(ev.created_at_bs) + '</div>' +
                                '</td>' +
                                '<td class="text-center">' + statusBadge + '</td>' +
                                '<td class="text-center">' +
                                    '<button type="button" class="btn btn-outline-primary btn-sm btn-icon rounded-circle inspect-log-btn" data-id="' + ev.id + '" title="{{ __("Inspect Details") }}">' +
                                        '<i class="icon-eye"></i>' +
                                    '</button>' +
                                '</td>' +
                            '</tr>';

                            $('#auditLogsTbody').prepend(rowHtml);
                        }
                    });

                    // Update live indicator counter
                    const badge = $('#liveCountBadge');
                    const currentCount = parseInt(badge.text()) || 0;
                    const newCount = currentCount + response.events.length;
                    badge.text('+' + newCount).show();
                }
            },
            error: function(xhr, status) {
                if (status === 'timeout') {
                    $('#liveStatusText').text('{{ __("Reconnecting...") }}');
                } else {
                    $('#liveStatusDot').removeClass('pulse-green').addClass('bg-warning');
                    $('#liveStatusText').text('{{ __("Retrying feed...") }}');
                }
            },
            complete: function() {
                // Schedule next poll in 6.5 seconds
                setTimeout(pollLiveFeed, 6500);
            }
        });
    }

    // Start live polling after page load
    setTimeout(pollLiveFeed, 4000);
});
</script>
@endsection
