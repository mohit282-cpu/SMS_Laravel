<?php

namespace Tests\Feature;

use App\Models\EntranceResult;
use App\Models\Event;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\Setting;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PublicPortalSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function getSuperAdmin(): User
    {
        return User::where('user_type', 'super_admin')->first();
    }

    /**
     * 1. Public homepage and events gallery only show published events with 'all' target audience.
     */
    public function test_public_landing_page_shows_only_published_all_audience_events()
    {
        // 1. Draft event (should be hidden)
        $draftEvent = Event::create([
            'title' => 'Secret Draft Gala ' . rand(1000, 9999),
            'category' => 'school_event',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_date_bs' => '2083-05-01',
            'end_date_bs' => '2083-05-01',
            'is_all_day' => true,
            'is_published' => false,
            'target_audience' => 'all',
        ]);

        // 2. Teacher-only published event (should be hidden from public)
        $teacherEvent = Event::create([
            'title' => 'Confidential Staff Sync ' . rand(1000, 9999),
            'category' => 'staff_meeting',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_date_bs' => '2083-05-01',
            'end_date_bs' => '2083-05-01',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'teachers',
        ]);

        // 3. Class-specific published event (should be hidden from public)
        $myClass = MyClass::first();
        $classEvent = Event::create([
            'title' => 'Internal Class Quiz ' . rand(1000, 9999),
            'category' => 'school_event',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_date_bs' => '2083-05-01',
            'end_date_bs' => '2083-05-01',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'classes',
            'my_class_id' => $myClass ? $myClass->id : 1,
        ]);

        // 4. Public all-audience published event (must be visible)
        $publicEvent = Event::create([
            'title' => 'Annual Grand Sports Meet ' . rand(1000, 9999),
            'category' => 'sports_extracurricular',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_date_bs' => '2083-05-01',
            'end_date_bs' => '2083-05-01',
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
        ]);

        // Test institutional homepage
        $resHome = $this->get(route('landing.home'));
        $resHome->assertStatus(200);
        $resHome->assertSee($publicEvent->title);
        $resHome->assertDontSee($draftEvent->title);
        $resHome->assertDontSee($teacherEvent->title);
        $resHome->assertDontSee($classEvent->title);

        // Test campus-events gallery page
        $resEvents = $this->get(route('landing.events'));
        $resEvents->assertStatus(200);
        $resEvents->assertSee($publicEvent->title);
        $resEvents->assertDontSee($draftEvent->title);
        $resEvents->assertDontSee($teacherEvent->title);
        $resEvents->assertDontSee($classEvent->title);
    }

    /**
     * 2. Entrance results search enforces exact symbol matching and rejects partial-name enumeration.
     */
    public function test_entrance_results_rejects_empty_symbol_and_partial_name_enumeration()
    {
        // Enable entrance results for testing
        Setting::updateOrCreate(['type' => 'entrance_results_visible'], ['description' => '1']);

        $symbol = 'SYM' . rand(10000, 99999);
        $studentName = 'Ashmita Thapa Magar';

        EntranceResult::create([
            'batch_year' => '2083 B.S.',
            'exam_name' => 'Entrance Test 2083',
            'class_name' => 'Grade 11 Science',
            'section_name' => 'GENERAL',
            'symbol_no' => $symbol,
            'roll_no' => '15',
            'student_name' => $studentName,
            'attendance' => 'PRESENT',
            'gpa' => 3.85,
            'remarks' => 'ELIGIBLE FOR ADMISSION',
            'marks_json' => json_encode([['subject' => 'PHYSICS', 'gp' => 3.8, 'grade' => 'A']]),
        ]);

        // 1. Partial-name enumeration attempt without symbol number must return 400 Bad Request
        $resPartial = $this->postJson(route('admissions.results.search'), [
            'search_query' => 'Ashmita',
        ]);
        $resPartial->assertStatus(400);
        $resPartial->assertJson(['success' => false, 'status' => 'error']);

        // 2. Empty symbol number must return 400 Bad Request
        $resEmpty = $this->postJson(route('admissions.results.search'), [
            'symbol_no' => '',
        ]);
        $resEmpty->assertStatus(400);

        // 3. Exact symbol number lookup succeeds and returns single candidate
        $resExact = $this->postJson(route('admissions.results.search'), [
            'symbol_no' => $symbol,
        ]);
        $resExact->assertStatus(200);
        $resExact->assertJson([
            'success' => true,
            'data' => [
                'symbol_no' => $symbol,
                'student_name' => $studentName,
            ],
        ]);
    }

    /**
     * 3. Disabled entrance results remain inaccessible to ordinary visitors.
     */
    public function test_disabled_entrance_results_inaccessible_to_guests()
    {
        Setting::updateOrCreate(['type' => 'entrance_results_visible'], ['description' => '0']);

        $resGuest = $this->postJson(route('admissions.results.search'), [
            'symbol_no' => '1001',
        ]);
        $resGuest->assertStatus(403);
        $resGuest->assertJson(['success' => false, 'status' => 'inactive']);

        // Super Admin can preview even when hidden
        $sa = $this->getSuperAdmin();
        $resSa = $this->actingAs($sa)->postJson(route('admissions.results.search'), [
            'symbol_no' => 'NONEXISTENT_SYMBOL',
        ]);
        $resSa->assertStatus(404); // Passed 403 check, hit 404 not found
    }

    /**
     * 4. CSV upload validates marks range and rejects invalid/non-numeric rows.
     */
    public function test_csv_upload_rejects_non_numeric_and_out_of_range_marks()
    {
        $sa = $this->getSuperAdmin();

        // CSV with invalid non-numeric mark for Science
        $invalidCsv = "Symbol No,Roll No,Student Name,Section,Attendance,English,Science\n" .
                      "SYM101,1,Ram Sharma,A,PRESENT,85,INVALID_MARK\n";

        $file = UploadedFile::fake()->createWithContent('invalid_results.csv', $invalidCsv);

        $response = $this->actingAs($sa)->post(route('landing_settings.entrance_results.upload'), [
            'csv_file' => $file,
            'batch_year' => '2083 B.S.',
            'exam_name' => 'Entrance 2083',
            'class_name' => 'Grade 11',
        ]);

        $response->assertSessionHas('flash_danger');
        $this->assertDatabaseMissing('entrance_results', ['symbol_no' => 'SYM101']);
    }

    /**
     * 5. Landing settings update allowlist rejects unauthorized attributes.
     */
    public function test_landing_settings_update_enforces_allowlist()
    {
        $sa = $this->getSuperAdmin();

        $response = $this->actingAs($sa)->put(route('landing_settings.update'), [
            'system_title' => 'Updated SMS Portal',
            'unauthorized_setting_key' => 'malicious_injected_value',
        ]);

        $response->assertSessionHas('flash_success');
        $this->assertEquals('Updated SMS Portal', Setting::where('type', 'system_title')->value('description'));
        $this->assertNull(Setting::where('type', 'unauthorized_setting_key')->first());
    }
}
