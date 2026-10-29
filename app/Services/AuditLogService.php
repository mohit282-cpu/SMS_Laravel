<?php

namespace App\Services;

use App\Models\AuditLog;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AuditLogService
{
    /**
     * Re-entrancy guard to prevent recursive audit loops.
     *
     * @var bool
     */
    protected static bool $isAuditing = false;

    /**
     * Correlation ID for the current request lifecycle.
     *
     * @var string|null
     */
    protected static ?string $currentRequestId = null;

    /**
     * Sensitive fields that must be unconditionally redacted from before/after diffs.
     *
     * @var array<string>
     */
    protected const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'old_password',
        'remember_token',
        '_token',
        'token',
        'secret',
        'card_number',
        'cvv',
        'cvc',
        'pin',
        'api_key',
        'private_key',
        'auth_token',
        'session_id',
    ];

    /**
     * Retrieve or generate unique request/correlation identifier.
     */
    public function getRequestId(): string
    {
        if (static::$currentRequestId === null) {
            $headerId = request()->header('X-Request-Id') ?: request()->header('X-Correlation-Id');
            static::$currentRequestId = !empty($headerId) ? substr($headerId, 0, 64) : (string) Str::uuid();
        }

        return static::$currentRequestId;
    }

    /**
     * Create an audit log record securely.
     *
     * @param string $action Normalized action key (e.g. 'auth.login', 'student.admit')
     * @param string $category authentication, academics, students, finance, administration, security
     * @param string $description Clear, human-readable description
     * @param Model|string|null $auditable Affected entity
     * @param array $oldValues Previous attributes
     * @param array $newValues New attributes
     * @param string $status success or failure
     * @param User|null $actor Explicit actor or null to detect from Auth
     * @return AuditLog|null
     */
    public function log(
        string $action,
        string $category,
        string $description,
        $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        string $status = 'success',
        ?User $actor = null
    ): ?AuditLog {
        // Prevent recursive audit log emission
        if (static::$isAuditing) {
            return null;
        }

        static::$isAuditing = true;

        try {
            $resolvedActor = $actor ?: (Auth::check() ? Auth::user() : null);

            $actorId = $resolvedActor ? $resolvedActor->id : null;
            $actorName = $resolvedActor ? $resolvedActor->name : null;
            $actorRole = $resolvedActor ? $resolvedActor->user_type : null;

            $auditableType = null;
            $auditableId = null;

            if ($auditable instanceof Model) {
                $auditableType = get_class($auditable);
                $auditableId = $auditable->getKey();
            } elseif (is_string($auditable) && !empty($auditable)) {
                $auditableType = $auditable;
            }

            $cleanOld = !empty($oldValues) ? $this->redactSensitiveData($oldValues) : null;
            $cleanNew = !empty($newValues) ? $this->redactSensitiveData($newValues) : null;

            $req = request();
            $ip = $req ? $req->ip() : null;
            $rawUa = $req ? (string) $req->userAgent() : null;
            $ua = $rawUa ? Str::limit($rawUa, 250, '') : null;
            $requestId = $this->getRequestId();

            $log = AuditLog::create([
                'actor_id' => $actorId,
                'actor_name_snapshot' => $actorName,
                'actor_role_snapshot' => $actorRole,
                'action' => $action,
                'category' => $category,
                'description' => $description,
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'old_values' => $cleanOld,
                'new_values' => $cleanNew,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'request_id' => $requestId,
                'status' => in_array($status, ['success', 'failure']) ? $status : 'success',
            ]);

            return $log;
        } catch (Throwable $e) {
            // Never break existing application workflows if optional logging fails
            Log::warning('AuditLogService::log failed: ' . $e->getMessage(), [
                'action' => $action,
                'category' => $category,
                'exception' => $e,
            ]);
            return null;
        } finally {
            static::$isAuditing = false;
        }
    }

    /**
     * Recursively redact sensitive information from before-and-after values.
     *
     * @param array $data
     * @return array
     */
    public function redactSensitiveData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            $isRedacted = false;
            foreach (self::REDACTED_KEYS as $redactedKey) {
                if ($lowerKey === $redactedKey || str_ends_with($lowerKey, '_' . $redactedKey) || str_contains($lowerKey, 'password')) {
                    $isRedacted = true;
                    break;
                }
            }

            if ($isRedacted) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->redactSensitiveData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Compute actual metrics from database records without hardcoded values.
     *
     * @param string|null $from
     * @param string|null $to
     * @return array
     */
    public function getMetrics(?string $from = null, ?string $to = null): array
    {
        $baseQuery = AuditLog::query();
        if (!empty($from) || !empty($to)) {
            $baseQuery->dateRange($from, $to);
        }

        $totalEvents = (clone $baseQuery)->count();

        // Events today
        $eventsToday = AuditLog::whereDate('created_at', Carbon::today())->count();

        // Unique active actors in range
        $activeActors = (clone $baseQuery)
            ->whereNotNull('actor_id')
            ->distinct('actor_id')
            ->count('actor_id');

        // Security-related events
        $securityEvents = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('category', 'security')
                  ->orWhere('category', 'authentication');
            })
            ->count();

        // Failed logins in range
        $failedLogins = (clone $baseQuery)
            ->where('action', 'auth.failed_login')
            ->count();

        return [
            'total_events' => $totalEvents,
            'events_today' => $eventsToday,
            'active_actors' => $activeActors,
            'security_events' => $securityEvents,
            'failed_logins' => $failedLogins,
        ];
    }

    /**
     * Retrieve paginated audit logs based on search and filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = AuditLog::with('actor');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category'])) {
            $query->category($filters['category']);
        }

        if (!empty($filters['action'])) {
            $query->action($filters['action']);
        }

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['actor_id'])) {
            $query->actor($filters['actor_id']);
        }

        if (!empty($filters['from']) || !empty($filters['to'])) {
            $query->dateRange($filters['from'] ?? null, $filters['to'] ?? null);
        }

        $sort = $filters['sort'] ?? 'newest';
        if ($sort === 'oldest') {
            $query->orderBy('id', 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query->paginate($perPage)->appends(request()->except('page'));
    }

    /**
     * Retrieve lightweight event stream for real-time live feed polling.
     *
     * @param int $sinceId
     * @param int $limit
     * @return array
     */
    public function getLiveFeed(int $sinceId = 0, int $limit = 30): array
    {
        $query = AuditLog::with('actor')
            ->where('id', '>', $sinceId)
            ->orderBy('id', 'asc')
            ->limit($limit);

        $logs = $query->get();

        return $logs->map(function (AuditLog $log) {
            $hasDiff = !empty($log->old_values) || !empty($log->new_values);

            return [
                'id' => $log->id,
                'action' => $log->action,
                'category' => $log->category,
                'category_badge' => $log->category_badge_class,
                'category_icon' => $log->category_icon,
                'description' => $log->description,
                'actor_name' => $log->actor_name_snapshot ?: ($log->actor ? $log->actor->name : 'System'),
                'actor_role' => $log->actor_role_snapshot ?: ($log->actor ? $log->actor->user_type : 'system'),
                'status' => $log->status,
                'status_badge' => $log->status_badge_class,
                'ip_address' => $log->ip_address ?: '—',
                'request_id' => $log->request_id ?: '—',
                'created_at_human' => $log->created_at ? $log->created_at->diffForHumans() : '',
                'created_at_ad' => $log->formatted_ad_date,
                'created_at_bs' => $log->formatted_bs_date,
                'has_diff' => $hasDiff,
            ];
        })->values()->all();
    }

    /**
     * Stream safe CSV export of filtered audit records with formula-injection mitigation.
     *
     * @param array $filters
     * @return StreamedResponse
     */
    public function exportCsv(array $filters = []): StreamedResponse
    {
        $filename = 'audit-logs-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($filters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Log ID',
                'Date (AD)',
                'Date (BS)',
                'Actor Name',
                'Actor Role',
                'Action',
                'Category',
                'Status',
                'Description',
                'Target Record Type',
                'Target Record ID',
                'IP Address',
                'Request ID',
            ]);

            $query = AuditLog::with('actor');

            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }
            if (!empty($filters['category'])) {
                $query->category($filters['category']);
            }
            if (!empty($filters['action'])) {
                $query->action($filters['action']);
            }
            if (!empty($filters['status'])) {
                $query->status($filters['status']);
            }
            if (!empty($filters['actor_id'])) {
                $query->actor($filters['actor_id']);
            }
            if (!empty($filters['from']) || !empty($filters['to'])) {
                $query->dateRange($filters['from'] ?? null, $filters['to'] ?? null);
            }

            $query->orderBy('id', 'desc');

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    $sanitize = function ($val) {
                        $str = (string) $val;
                        // Prevent CSV Formula Injection (=, +, -, @, tab, cr)
                        if (strlen($str) > 0 && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"])) {
                            return "'" . $str;
                        }
                        return $str;
                    };

                    fputcsv($handle, [
                        $log->id,
                        $sanitize($log->formatted_ad_date),
                        $sanitize($log->formatted_bs_date),
                        $sanitize($log->actor_name_snapshot ?: ($log->actor ? $log->actor->name : 'System')),
                        $sanitize($log->actor_role_snapshot ?: ($log->actor ? $log->actor->user_type : 'system')),
                        $sanitize($log->action),
                        $sanitize($log->category),
                        $sanitize($log->status),
                        $sanitize($log->description),
                        $sanitize($log->auditable_type ?: '—'),
                        $sanitize($log->auditable_id ?: '—'),
                        $sanitize($log->ip_address ?: '—'),
                        $sanitize($log->request_id ?: '—'),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
