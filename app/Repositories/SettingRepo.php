<?php

namespace App\Repositories;


use App\Models\Setting;

class SettingRepo
{
    public function update($type, $desc)
    {
        return Setting::updateOrCreate(['type' => $type], ['description' => $desc]);
    }

    public function getSetting($type)
    {
        return Setting::where('type', $type)->get();
    }

    public function all()
    {
        return Setting::all();
    }
}