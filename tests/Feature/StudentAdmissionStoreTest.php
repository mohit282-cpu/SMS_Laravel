<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\ClassType;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StudentAdmissionStoreTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $class;
    protected $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'admin')->first()
            ?: User::factory()->create(['user_type' => 'admin']);

        $classType = ClassType::first() ?: ClassType::create(['name' => 'Secondary', 'code' => 'S']);
        $this->class = MyClass::first() ?: MyClass::create(['name' => 'Grade 10', 'class_type_id' => $classType->id]);
        $this->section = Section::where('my_class_id', $this->class->id)->first()
            ?: Section::create(['name' => 'A', 'my_class_id' => $this->class->id, 'active' => 1]);
    }

    public function test_admitting_student_with_long_school_title_succeeds_without_truncation(): void
    {
        // Simulate school setting: Apex Model Secondary School (27 characters)
        Setting::updateOrCreate(['type' => 'system_title'], ['description' => 'Apex Model Secondary School']);
        Setting::updateOrCreate(['type' => 'system_name'], ['description' => 'Apex Model Secondary School']);

        $this->assertSame('Apex Model Secondary School', Qs::getAppCode());

        $payload = [
            'name' => 'Subin Shrestha',
            'gender' => 'Male',
            'year_admitted' => '2026',
            'my_class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'address' => 'Ward 4, Itahari, Sunsari',
            'nal_id' => 1,
            'state_id' => 1,
            'lga_id' => 1,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('students.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $createdUser = User::where('name', 'Subin Shrestha')->latest('id')->first();
        $this->assertNotNull($createdUser);
        $this->assertStringStartsWith('APEX MODEL SECONDARY SCHOOL/', $createdUser->username);
        $this->assertGreaterThan(30, strlen($createdUser->username)); // Proves > 30 chars

        $record = StudentRecord::where('user_id', $createdUser->id)->first();
        $this->assertNotNull($record);
        $this->assertSame($createdUser->username, $record->adm_no);
        $this->assertGreaterThan(30, strlen($record->adm_no)); // Proves adm_no accommodated > 30 chars
        $this->assertLessThanOrEqual(100, strlen($record->adm_no));
    }

    public function test_duplicate_adm_no_returns_422_validation_error_not_500(): void
    {
        Setting::updateOrCreate(['type' => 'system_title'], ['description' => 'Apex Model Secondary School']);

        $ct = $this->class->class_type->code;
        $existingAdm = 'CUSTOMDUP999';
        $fullAdmNo = 'APEX MODEL SECONDARY SCHOOL/' . $ct . '/2026/' . $existingAdm;

        $existingUser = User::factory()->create([
            'user_type' => 'student',
            'username' => $fullAdmNo,
        ]);

        StudentRecord::create([
            'user_id' => $existingUser->id,
            'my_class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'adm_no' => $fullAdmNo,
            'year_admitted' => '2026',
            'session' => Qs::getCurrentSession(),
        ]);

        $payload = [
            'name' => 'Prabesh Adhikari',
            'gender' => 'Male',
            'year_admitted' => '2026',
            'adm_no' => $existingAdm,
            'my_class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'address' => 'Bargachi, Biratnagar, Morang',
            'nal_id' => 1,
            'state_id' => 1,
            'lga_id' => 1,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('students.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['msg']);
    }

    public function test_app_code_helper_returns_system_title_or_default(): void
    {
        Setting::updateOrCreate(['type' => 'system_title'], ['description' => 'Apex Model Secondary School']);
        $this->assertSame('Apex Model Secondary School', Qs::getAppCode());

        Setting::updateOrCreate(['type' => 'system_title'], ['description' => 'AMSS']);
        $this->assertSame('AMSS', Qs::getAppCode());

        Setting::updateOrCreate(['type' => 'system_title'], ['description' => 'SMS Academy']);
        $this->assertSame('SMS Academy', Qs::getAppCode());
    }
}
