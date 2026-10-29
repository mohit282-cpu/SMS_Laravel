<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    protected AuditLogService $audit;

    public function __construct(AuditLogService $audit)
    {
        $this->middleware(['auth', 'super_admin']);
        $this->audit = $audit;
    }

    /**
     * Display the central glassmorphic audit log dashboard.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category', 'action', 'status', 'actor_id', 'from', 'to', 'sort']);

        $metrics = $this->audit->getMetrics($request->input('from'), $request->input('to'));
        $logs = $this->audit->getPaginatedLogs($filters, 20);

        // Pre-compute filter options
        $categories = [
            'authentication' => __('Authentication & Access'),
            'security' => __('Security & Account Controls'),
            'students' => __('Students & Admissions'),
            'academics' => __('Academics, Exams & Grades'),
            'finance' => __('Finance, Invoices & Payments'),
            'administration' => __('Administration & Settings'),
        ];

        $actions = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $actors = User::whereIn('id', function ($query) {
                $query->select('actor_id')
                    ->from('audit_logs')
                    ->whereNotNull('actor_id')
                    ->distinct();
            })
            ->select('id', 'name', 'user_type')
            ->orderBy('name')
            ->get();

        return view('pages.super_admin.audit_logs.index', compact('metrics', 'logs', 'categories', 'actions', 'actors', 'filters'));
    }

    /**
     * Return JSON details of a single audit event for modal/drawer inspection.
     */
    public function show($id): JsonResponse
    {
        $log = AuditLog::with('actor')->findOrFail($id);

        return response()->json([
            'id' => $log->id,
            'action' => $log->action,
            'category' => $log->category,
            'category_badge' => $log->category_badge_class,
            'category_icon' => $log->category_icon,
            'description' => $log->description,
            'actor_id' => $log->actor_id,
            'actor_name' => $log->actor_name_snapshot ?: ($log->actor ? $log->actor->name : 'System'),
            'actor_role' => $log->actor_role_snapshot ?: ($log->actor ? $log->actor->user_type : 'system'),
            'status' => $log->status,
            'status_badge' => $log->status_badge_class,
            'auditable_type' => $log->auditable_type ?: '—',
            'auditable_id' => $log->auditable_id ?: '—',
            'ip_address' => $log->ip_address ?: '—',
            'user_agent' => $log->user_agent ?: '—',
            'request_id' => $log->request_id ?: '—',
            'old_values' => $log->old_values ?: [],
            'new_values' => $log->new_values ?: [],
            'created_at_ad' => $log->formatted_ad_date,
            'created_at_bs' => $log->formatted_bs_date,
            'created_at_human' => $log->created_at ? $log->created_at->diffForHumans() : '',
        ]);
    }

    /**
     * Real-time polling endpoint for newly recorded audit events.
     */
    public function feed(Request $request): JsonResponse
    {
        $sinceId = (int) $request->input('since_id', 0);
        $limit = min(50, max(1, (int) $request->input('limit', 25)));

        $feed = $this->audit->getLiveFeed($sinceId, $limit);

        return response()->json([
            'success' => true,
            'count' => count($feed),
            'events' => $feed,
            'latest_id' => !empty($feed) ? max(array_column($feed, 'id')) : $sinceId,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Export filtered audit logs to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'category', 'action', 'status', 'actor_id', 'from', 'to']);

        return $this->audit->exportCsv($filters);
    }
}
