<?php

namespace App;

use App\Models\BloodGroup;
use App\Models\Lga;
use App\Models\Nationality;
use App\Models\StaffRecord;
use App\Models\State;
use App\Models\StudentRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'phone', 'phone2', 'dob', 'gender', 'photo', 'address', 'bg_id', 'password', 'nal_id', 'state_id', 'lga_id', 'code', 'user_type', 'email_verified_at', 'locale'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function student_record()
    {
        return $this->hasOne(StudentRecord::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nal_id');
    }

    public function blood_group()
    {
        return $this->belongsTo(BloodGroup::class, 'bg_id');
    }

    public function staff()
    {
        return $this->hasMany(StaffRecord::class);
    }

    public function getPhotoAttribute($value)
    {
        $default = asset('global_assets/images/user.png');

        if (!$value) {
            return $default;
        }

        // Default avatar referenced in any form (absolute or relative)
        if (str_contains($value, 'user.png')) {
            return $default;
        }

        // Uploaded photo with storage/ path
        if (str_contains($value, 'storage/')) {
            $storagePos = strpos($value, 'storage/');
            $subPath = substr($value, $storagePos + 8);
            if (file_exists(storage_path('app/public/' . $subPath))) {
                return asset('storage/' . $subPath);
            }
            return $default;
        }

        // Relative path starting with uploads/
        if (str_starts_with($value, 'uploads/')) {
            if (file_exists(storage_path('app/public/' . $value))) {
                return asset('storage/' . $value);
            }
            return $default;
        }

        // Full URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = parse_url($value, PHP_URL_PATH);
            if ($path && str_contains($path, 'storage/')) {
                $storagePos = strpos($path, 'storage/');
                $subPath = substr($path, $storagePos + 8);
                if (file_exists(storage_path('app/public/' . $subPath))) {
                    return asset('storage/' . $subPath);
                }
                return $default;
            }
            return $value;
        }

        return $default;
    }

    public function bookRequests()
    {
        return $this->hasMany(\App\Models\BookRequest::class, 'user_id');
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(\App\Models\PushSubscription::class, 'user_id');
    }
}
