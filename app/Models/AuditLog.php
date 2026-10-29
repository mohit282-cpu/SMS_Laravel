<?php

namespace App\Models;

use App\Helpers\NepaliHelper;
use App\Services\NepaliDateService;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    /**
     * Audit log fields are strictly controlled and populated exclusively server-side.
     *
     * @var array
     */
    protected $fillable = [
        'actor_id',
        'actor_name_snapshot',
        'actor_role_snapshot',
        'action',
        'category',
        'description',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Flag allowing administrative maintenance/archival routines to bypass immutability guard.
     *
     * @var bool
     */
    public static $allowDirectModification = false;

    /**
     * Relationship to the user who performed the action.
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Polymorphic relation to the affected model.
     */
    public function auditable()
    {
        return $this->morphTo('auditable', 'auditable_type', 'auditable_id');
    }

    /* -------------------------------------------------------------------------
     * QUERY SCOPES
     * ------------------------------------------------------------------------- */

    /**
     * Scope query to search by actor name, action, or description.
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
              ->orWhere('action', 'like', "%{$search}%")
              ->orWhere('actor_name_snapshot', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%")
              ->orWhere('request_id', 'like', "%{$search}%");
        });
    }

    /**
     * Scope query to specific category.
     */
    public function scopeCategory($query, $category)
    {
        if (empty($category) || $category === 'all') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Scope query to specific action.
     */
    public function scopeAction($query, $action)
    {
        if (empty($action) || $action === 'all') {
            return $query;
        }

        return $query->where('action', $action);
    }

    /**
     * Scope query to specific status (success or failure).
     */
    public function scopeStatus($query, $status)
    {
        if (empty($status) || $status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope query to specific actor ID.
     */
    public function scopeActor($query, $actorId)
    {
        if (empty($actorId) || $actorId === 'all') {
            return $query;
        }

        return $query->where('actor_id', $actorId);
    }

    /**
     * Scope query to date range (from and to inclusive).
     */
    public function scopeDateRange($query, $from = null, $to = null)
    {
        if (!empty($from)) {
            try {
                $start = Carbon::parse($from)->startOfDay();
                $query->where('created_at', '>=', $start);
            } catch (\Exception $e) {
                // Ignore malformed from date
            }
        }

        if (!empty($to)) {
            try {
                $end = Carbon::parse($to)->endOfDay();
                $query->where('created_at', '<=', $end);
            } catch (\Exception $e) {
                // Ignore malformed to date
            }
        }

        return $query;
    }

    /* -------------------------------------------------------------------------
     * ACCESSORS & PRESENTATION HELPERS
     * ------------------------------------------------------------------------- */

    /**
     * Category badge CSS class.
     */
    public function getCategoryBadgeClassAttribute(): string
    {
        switch ($this->category) {
            case 'authentication':
                return 'badge-info';
            case 'security':
                return 'badge-danger';
            case 'finance':
                return 'badge-success';
            case 'academics':
                return 'badge-primary';
            case 'students':
                return 'badge-warning text-dark';
            case 'administration':
                return 'badge-secondary';
            default:
                return 'badge-light';
        }
    }

    /**
     * Category Icon class.
     */
    public function getCategoryIconAttribute(): string
    {
        switch ($this->category) {
            case 'authentication':
                return 'icon-key';
            case 'security':
                return 'icon-shield-notice';
            case 'finance':
                return 'icon-cash3';
            case 'academics':
                return 'icon-graduation2';
            case 'students':
                return 'icon-users';
            case 'administration':
                return 'icon-cogs';
            default:
                return 'icon-history';
        }
    }

    /**
     * Status badge CSS class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status === 'success' ? 'badge-success' : 'badge-danger';
    }

    /**
     * Gregorian AD formatted date.
     */
    public function getFormattedAdDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : '';
    }

    /**
     * Nepali Bikram Sambat (BS) date representation.
     */
    public function getFormattedBsDateAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }

        try {
            $service = app(NepaliDateService::class);
            $bs = $service->adToBs($this->created_at->format('Y-m-d'));
            if ($bs && isset($bs['formatted'])) {
                return $bs['formatted'] . ' ' . $this->created_at->format('H:i');
            }
        } catch (\Throwable $e) {
            // Fallback gracefully
        }

        return $this->formatted_ad_date;
    }

    /* -------------------------------------------------------------------------
     * IMMUTABILITY GUARDS
     * ------------------------------------------------------------------------- */

    /**
     * Prevent ordinary update operations to preserve audit log integrity.
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (!static::$allowDirectModification) {
            throw new RuntimeException('Audit records are immutable and cannot be updated.');
        }

        return parent::update($attributes, $options);
    }

    /**
     * Prevent ordinary delete operations to preserve audit log integrity.
     */
    public function delete()
    {
        if (!static::$allowDirectModification) {
            throw new RuntimeException('Audit records are immutable and cannot be deleted.');
        }

        return parent::delete();
    }
}
