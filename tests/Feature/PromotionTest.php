<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\AcademicYear;
use App\Models\ClassType;
use App\Models\MyClass;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_promo_test@example.com'],
            [
                'name' => 'Promotion Super Admin',
                'username' => 'admin_promo_test',
                'password' => bcrypt('password'),
                'user_type' => 'super_admin',
                'code' => strtoupper(substr(md5(uniqid()), 0, 10)),
            ]
        );
    }

    public function test_get_next_session_formats()
    {
        // Standalone formats without DB constraint
        $this->assertEquals('2084/85 BS', Qs::getNextSession('2083/84 BS'));
        $this->assertEquals('2084-2085', Qs::getNextSession('2083-2084'));
        $this->assertEquals('2084/2085', Qs::getNextSession('2083/2084'));
        $this->assertEquals('2084', Qs::getNextSession('2083'));
        $this->assertEquals('2084 BS', Qs::getNextSession('2083 BS'));
        $this->assertEquals('2019-2020', Qs::getNextSession('2018-2019'));
    }

    public function test_get_next_session_resolves_from_academic_year_table()
    {
        $origActive = AcademicYear::where('is_active', true)->first();
        if ($origActive) {
            $origActive->update(['is_active' => false]);
        }

        $ayCurrent = AcademicYear::create([
            'name' => '2081/82 BS',
            'year_bs' => 2081,
            'start_date' => '2024-04-13',
            'end_date' => '2025-04-13',
            'start_date_bs' => '2081-01-01',
            'end_date_bs' => '2081-12-30',
            'is_active' => true,
        ]);

        $ayNext = AcademicYear::create([
            'name' => '2082/83 BS Target',
            'year_bs' => 2082,
            'start_date' => '2025-04-14',
            'end_date' => '2026-04-13',
            'start_date_bs' => '2082-01-01',
            'end_date_bs' => '2082-12-30',
            'is_active' => false,
        ]);

        $this->assertEquals('2081/82 BS', Qs::getCurrentSession());
        $this->assertEquals('2082/83 BS Target', Qs::getNextSession());

        // Clean up test records
        $ayNext->delete();
        $ayCurrent->delete();

        if ($origActive) {
            $origActive->update(['is_active' => true]);
        }
    }

    public function test_admin_can_view_promotion_page()
    {
        $response = $this->actingAs($this->admin)->get(route('students.promotion'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.students.promotion.index');
        $response->assertViewHas('old_year');
        $response->assertViewHas('new_year');
    }

    public function test_admin_can_view_promotion_manage_page()
    {
        $response = $this->actingAs($this->admin)->get(route('students.promotion_manage'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.support_team.students.promotion.reset');
        $response->assertViewHas('promotions');
        $response->assertViewHas('old_year');
        $response->assertViewHas('new_year');
    }

    public function test_student_promotion_and_reset_workflow()
    {
        $currentSession = Qs::getCurrentSession();
        $nextSession = Qs::getNextSession($currentSession);

        $classType = ClassType::first();
        $classFrom = MyClass::firstOrCreate(['name' => 'Grade 4 Promo'], ['class_type_id' => $classType ? $classType->id : 1]);
        $classTo = MyClass::firstOrCreate(['name' => 'Grade 5 Promo'], ['class_type_id' => $classType ? $classType->id : 1]);
        $sectionFrom = Section::firstOrCreate(['name' => 'A Promo', 'my_class_id' => $classFrom->id]);
        $sectionTo = Section::firstOrCreate(['name' => 'A Promo', 'my_class_id' => $classTo->id]);

        $studentUser = User::create([
            'name' => 'Test Promo Student',
            'username' => 'promo_student_' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(substr(md5(uniqid()), 0, 10)),
        ]);

        $studentRecord = StudentRecord::create([
            'user_id' => $studentUser->id,
            'my_class_id' => $classFrom->id,
            'section_id' => $sectionFrom->id,
            'adm_no' => 'ADM-PROMO-' . rand(1000, 9999),
            'session' => $currentSession,
        ]);

        // Test Selector redirect
        $selectorResponse = $this->actingAs($this->admin)->post(route('students.promote_selector'), [
            'fc' => $classFrom->id,
            'fs' => $sectionFrom->id,
            'tc' => $classTo->id,
            'ts' => $sectionTo->id,
        ]);

        $selectorResponse->assertRedirect(route('students.promotion', [
            $classFrom->id, $sectionFrom->id, $classTo->id, $sectionTo->id
        ]));

        // View Promotion page with selected classes
        $viewSelectedResponse = $this->actingAs($this->admin)->get(route('students.promotion', [
            $classFrom->id, $sectionFrom->id, $classTo->id, $sectionTo->id
        ]));
        $viewSelectedResponse->assertStatus(200);
        $viewSelectedResponse->assertSee($studentUser->name);

        // Perform Promotion
        $promoteResponse = $this->actingAs($this->admin)->post(route('students.promote', [
            $classFrom->id, $sectionFrom->id, $classTo->id, $sectionTo->id
        ]), [
            'p-' . $studentRecord->id => 'P',
        ]);

        $promoteResponse->assertRedirect(route('students.promotion'));

        // Verify student record updated
        $studentRecord->refresh();
        $this->assertEquals($classTo->id, $studentRecord->my_class_id);
        $this->assertEquals($sectionTo->id, $studentRecord->section_id);
        $this->assertEquals($nextSession, $studentRecord->session);

        // Verify promotion record created
        $promo = Promotion::where('student_id', $studentUser->id)->latest()->first();
        $this->assertNotNull($promo);
        $this->assertEquals($classFrom->id, $promo->from_class);
        $this->assertEquals($classTo->id, $promo->to_class);
        $this->assertEquals($currentSession, $promo->from_session);
        $this->assertEquals($nextSession, $promo->to_session);
        $this->assertEquals('P', $promo->status);

        // Reset Promotion
        $resetResponse = $this->actingAs($this->admin)->delete(route('students.promotion_reset', $promo->id));
        $resetResponse->assertRedirect(route('students.promotion_manage'));

        // Verify student record reverted
        $studentRecord->refresh();
        $this->assertEquals($classFrom->id, $studentRecord->my_class_id);
        $this->assertEquals($sectionFrom->id, $studentRecord->section_id);
        $this->assertEquals($currentSession, $studentRecord->session);

        // Clean up
        $studentRecord->delete();
        $studentUser->delete();
    }
}
