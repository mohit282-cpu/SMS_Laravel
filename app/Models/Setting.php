<?php

namespace App\Models;

use Eloquent;

class Setting extends Eloquent
{
    protected $fillable = ['type', 'description'];

    public function getDescriptionAttribute($value)
    {
        if ($this->type === 'logo') {
            $default = asset('global_assets/images/logo.png');

            if (!$value) {
                return $default;
            }

            if (str_contains($value, 'logo.png') && !str_contains($value, 'storage/')) {
                return $default;
            }

            if (str_contains($value, 'storage/')) {
                $storagePos = strpos($value, 'storage/');
                $subPath = substr($value, $storagePos + 8);
                if (file_exists(storage_path('app/public/' . $subPath))) {
                    return asset('storage/' . $subPath);
                }
                return $default;
            }

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

        return $value;
    }
}
