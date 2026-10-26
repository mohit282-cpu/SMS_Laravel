<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\StudentRecord;
use App\User;
use Tests\TestCase;

class MediaPhotoResolutionTest extends TestCase
{
    /**
     * Test user default photo falls back to user.png dynamically.
     */
    public function test_user_default_photo_falls_back_to_asset()
    {
        $user = new User(['name' => 'Test User', 'photo' => 'http://localhost/global_assets/images/user.png']);
        $this->assertStringContainsString('global_assets/images/user.png', $user->photo);

        $userEmpty = new User(['name' => 'Empty User', 'photo' => '']);
        $this->assertStringContainsString('global_assets/images/user.png', $userEmpty->photo);

        $userNull = new User(['name' => 'Null User', 'photo' => null]);
        $this->assertStringContainsString('global_assets/images/user.png', $userNull->photo);
    }

    /**
     * Test missing uploaded photo falls back to default user image.
     */
    public function test_missing_uploaded_photo_falls_back_to_default()
    {
        $user = new User([
            'name' => 'Missing Photo User',
            'photo' => 'http://127.0.0.1:8000/storage/uploads/student/NONEXISTENT/photo.jpg'
        ]);

        $this->assertStringContainsString('global_assets/images/user.png', $user->photo);
    }

    /**
     * Test student record photo accessor forwards to user photo.
     */
    public function test_student_record_photo_accessor()
    {
        $user = User::where('user_type', 'student')->first();
        if ($user) {
            $sr = StudentRecord::where('user_id', $user->id)->first();
            if ($sr) {
                $this->assertEquals($user->photo, $sr->photo);
            }
        }
    }

    /**
     * Test setting logo fallback when unset.
     */
    public function test_setting_logo_fallback()
    {
        $setting = new Setting(['type' => 'logo', 'description' => '']);
        $this->assertStringContainsString('global_assets/images/logo.png', $setting->description);

        $settingNull = new Setting(['type' => 'logo', 'description' => null]);
        $this->assertStringContainsString('global_assets/images/logo.png', $settingNull->description);
    }
}
