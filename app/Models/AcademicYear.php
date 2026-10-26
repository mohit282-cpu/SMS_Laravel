<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year_bs',
        'start_date',
        'end_date',
        'start_date_bs',
        'end_date_bs',
        'is_active',
        'is_closed',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'year_bs' => 'integer',
    ];

    /**
     * Session code accessor (falls back to name if column not present).
     */
    public function getCodeAttribute(): string
    {
        return $this->attributes['code'] ?? (string) $this->name;
    }

    /**
     * Scope for active academic year.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for open (not closed) academic years.
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Get the single authoritative active academic session.
     * Automatically repairs state if no session is currently marked active.
     *
     * @return AcademicYear|null
     */
    public static function current(): ?self
    {
        $active = self::where('is_active', true)->first();
        if ($active) {
            return $active;
        }

        // Auto-heal: find matching Setting or fallback to latest open session
        $settingSession = \App\Models\Setting::where('type', 'current_session')->value('description');
        $candidate = null;

        if ($settingSession) {
            $candidate = self::where('name', $settingSession)->first();
        }

        if (!$candidate) {
            $candidate = self::orderBy('year_bs', 'desc')->first();
        }

        if ($candidate) {
            self::activateSession($candidate->id);
            return $candidate->fresh();
        }

        return null;
    }

    /**
     * Atomically activate a specific session and deactivate all others.
     *
     * @param int $id
     * @return AcademicYear
     */
    public static function activateSession(int $id): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            self::where('id', '<>', $id)->where('is_active', true)->update(['is_active' => false]);

            $ay = self::findOrFail($id);
            $ay->update([
                'is_active' => true,
                'is_closed' => false,
            ]);

            \App\Models\Setting::where('type', 'current_session')->update(['description' => $ay->name]);

            return $ay;
        });
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'academic_year_id');
    }
}
