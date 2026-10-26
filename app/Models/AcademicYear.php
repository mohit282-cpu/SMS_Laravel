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
     * Pure read-only query that never mutates session states or auto-activates historical sessions.
     *
     * @return AcademicYear|null
     */
    public static function current(): ?self
    {
        return self::where('is_active', true)->where('is_closed', false)->first();
    }

    /**
     * Atomically activate a specific open session and deactivate all others.
     * Closed sessions cannot be activated without an explicit reopen operation first.
     *
     * @param int $id
     * @return AcademicYear
     * @throws \InvalidArgumentException
     */
    public static function activateSession(int $id): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $ay = self::findOrFail($id);

            if ($ay->is_closed) {
                throw new \InvalidArgumentException(__('Cannot activate a closed academic session. Please reopen it first.'));
            }

            self::where('id', '<>', $id)->where('is_active', true)->update(['is_active' => false]);

            $ay->update([
                'is_active' => true,
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
