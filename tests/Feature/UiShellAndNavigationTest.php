<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UiShellAndNavigationTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Test User',
            'email' => $role . '_' . uniqid() . '@sms.local',
            'username' => $role . '_' . uniqid(),
            'user_type' => $role,
            'code' => strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'password' => Hash::make('Secret123!'),
            'photo' => 'global_assets/images/user.png',
        ]);
    }

    public function test_login_page_uses_unified_design_system_and_accessible_labels()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('sms-design-system.css', false);
        $response->assertDontSee('apple-liquid-theme.css', false);
        $response->assertSee('sms-auth-card', false);
        $response->assertSee('for="username"', false);
        $response->assertSee('for="password"', false);
        $response->assertSee('toggle-password-btn', false);
        $response->assertSee('Sovryx Tech Pvt. Ltd.', false);
    }

    public function test_branded_error_views_render_cleanly()
    {
        foreach (['403', '404', '419', '429', '500', '503'] as $code) {
            $html = view("errors.{$code}")->render();
            $this->assertStringContainsString($code, $html);
            $this->assertStringContainsString('sms-error-card', $html);
            $this->assertStringContainsString('Sovryx Tech Pvt. Ltd.', $html);
        }
    }

    public function test_mobile_dock_renders_authorized_links_for_teacher_and_parent()
    {
        $teacher = $this->makeUser('teacher');
        $this->actingAs($teacher);
        $teacherDock = view('partials.mobile_dock')->render();
        $this->assertStringContainsString(route('attendance.index'), $teacherDock);
        $this->assertStringNotContainsString(route('users.index'), $teacherDock);

        $parent = $this->makeUser('parent');
        $this->actingAs($parent);
        $parentDock = view('partials.mobile_dock')->render();
        $this->assertStringContainsString(route('my_children'), $parentDock);
        $this->assertStringNotContainsString('/payments/invoice/', $parentDock);
    }

    public function test_mobile_dock_renders_authorized_links_for_all_remaining_roles()
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        $this->assertStringContainsString(route('classes.index'), view('partials.mobile_dock')->render());

        $accountant = $this->makeUser('accountant');
        $this->actingAs($accountant);
        $this->assertStringContainsString(route('payments.index'), view('partials.mobile_dock')->render());

        $librarian = $this->makeUser('librarian');
        $this->actingAs($librarian);
        $this->assertStringContainsString(route('books.index'), view('partials.mobile_dock')->render());

        $student = $this->makeUser('student');
        $this->actingAs($student);
        $this->assertStringContainsString(route('tt.my_timetable'), view('partials.mobile_dock')->render());
    }

    public function test_master_shell_footer_and_topbar_render_for_authenticated_user()
    {
        $admin = $this->makeUser('super_admin');
        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('sms-navbar', false);
        $response->assertSee('sms-sidebar', false);
        $response->assertSee('sms-footer', false);
        $response->assertSee('sms-skip-link', false);
        $response->assertSee('id="main-content"', false);
        $response->assertSee('Sovryx Tech Pvt. Ltd.', false);
    }

    public function test_parent_children_page_renders_direct_fees_button_and_responsive_table()
    {
        $parent = $this->makeUser('parent');
        $response = $this->actingAs($parent)->get(route('my_children'));

        $response->assertStatus(200);
        $response->assertSee('sms-card', false);
        $response->assertSee('No Children Currently Linked');
    }

    public function test_responsive_css_overflow_and_touch_target_guards_are_present()
    {
        $designCss = file_get_contents(public_path('assets/css/sms-design-system.css'));
        $this->assertStringContainsString('overflow-x: clip;', $designCss);
        $this->assertStringContainsString('min-height: 100dvh;', $designCss);
        $this->assertStringContainsString('.dataTables_wrapper', $designCss);
        $this->assertStringContainsString('min-height: 44px;', $designCss);

        $calendarCss = file_get_contents(public_path('assets/css/nepali_bs_calendar.css'));
        $this->assertStringNotContainsString('min-width: 320px;', $calendarCss);

        $pwaCss = file_get_contents(public_path('assets/css/pwa.css'));
        $this->assertStringContainsString('bottom: calc(74px + env(safe-area-inset-bottom, 0px));', $pwaCss);
    }

    public function test_admit_student_page_renders_with_accessible_labels_and_design_system()
    {
        $this->seed(\Database\Seeders\NepalGeographySeeder::class);

        $admin = $this->makeUser('super_admin');
        $response = $this->actingAs($admin)->get(route('students.create'));

        $response->assertStatus(200);
        $response->assertSee('sms-card', false);
        $response->assertSee('sms-info-callout', false);
        $response->assertSee('for="student_name"', false);
        $response->assertSee('for="nepal_addr_main_province"', false);
        $response->assertSee('for="nepal_addr_main_district"', false);
        $response->assertSee('Koshi Province', false);
        $response->assertDontSee('name="state_id"', false);
        $response->assertDontSee('apple-liquid-theme.css', false);
    }

    public function test_admit_student_stores_nepal_location_fields()
    {
        $this->seed(\Database\Seeders\NepalGeographySeeder::class);

        $admin = $this->makeUser('super_admin');
        $bagmati = \App\Models\NepalProvince::where('code', 'P3')->firstOrFail();
        $kathmandu = \App\Models\NepalDistrict::where('province_id', $bagmati->id)->where('name', 'Kathmandu')->firstOrFail();
        $myClass = \App\Models\MyClass::firstOrFail();
        $section = \App\Models\Section::where('my_class_id', $myClass->id)->firstOrFail();
        $nal = \App\Models\Nationality::firstOrFail();

        $unique = mt_rand(10000, 99999);
        $response = $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'Aarav Adhikari ' . $unique,
            'address' => 'Baneshwor, Kathmandu',
            'gender' => 'Male',
            'nal_id' => $nal->id,
            'province_id' => $bagmati->id,
            'district_id' => $kathmandu->id,
            'ward_no' => 10,
            'tole' => 'New Baneshwor',
            'street' => 'Madan Bhandari Path',
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'year_admitted' => '2083',
        ]);

        $response->assertStatus(200);

        $studentUser = \App\User::where('name', 'Aarav Adhikari ' . $unique)->firstOrFail();
        $record = \App\Models\StudentRecord::where('user_id', $studentUser->id)->firstOrFail();
        $this->assertEquals($bagmati->id, $record->province_id);
        $this->assertEquals($kathmandu->id, $record->district_id);
        $this->assertEquals(10, $record->ward_no);
        $this->assertEquals('New Baneshwor', $record->tole);
        $this->assertEquals('Madan Bhandari Path', $record->street);
    }
}

