<?php

namespace App\Models;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookRequest extends Model
{
    use HasFactory;

    protected $table = 'book_requests';

    protected $fillable = [
        'book_id',
        'user_id',
        'borrower_type',
        'request_date',
        'issue_date',
        'due_date',
        'return_date',
        'start_date',
        'end_date',
        'returned',
        'approved_by',
        'issued_by',
        'returned_by_staff',
        'fine_amount',
        'fine_paid',
        'notes',
        'status',
    ];

    protected $casts = [
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->start_date)) {
                $model->start_date = $model->request_date ?? $model->issue_date ?? date('Y-m-d');
            }
            if (empty($model->end_date)) {
                $model->end_date = $model->due_date ?? date('Y-m-d', strtotime('+14 days'));
            }
        });
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'returned_by_staff');
    }

    /**
     * Determine if current active loan is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'issued' || empty($this->due_date)) {
            return false;
        }

        try {
            $dueDate = Carbon::parse($this->due_date);
            return Carbon::today()->gt($dueDate);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getIssueDateBsAttribute()
    {
        if (empty($this->issue_date)) {
            return null;
        }
        try {
            $conv = app(\App\Services\BikramSambatService::class)->adToBs($this->issue_date);
            return $conv['bs_date'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getDueDateBsAttribute()
    {
        if (empty($this->due_date)) {
            return null;
        }
        try {
            $conv = app(\App\Services\BikramSambatService::class)->adToBs($this->due_date);
            return $conv['bs_date'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getReturnDateBsAttribute()
    {
        if (empty($this->return_date)) {
            return null;
        }
        try {
            $conv = app(\App\Services\BikramSambatService::class)->adToBs($this->return_date);
            return $conv['bs_date'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
