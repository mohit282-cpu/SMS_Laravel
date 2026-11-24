<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\Exam;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExamManagementUiTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $this->exam = Exam::first();
        if (!$this->exam) {
            $this->exam = Exam::create([
                'name' => 'Mid Term Assessment ' . mt_rand(100, 999),
                'term' => 1,
                'year' => Qs::getCurrentSession(),
                'exam_type' => 'mid_term',
                'start_date_bs' => '2083-05-10',
                'end_date_bs' => '2083-05-18',
                'start_date' => '2026-08-26',
                'end_date' => '2026-09-03',
                'status' => 'published',
            ]);
        }
    }

    public function test_super_admin_can_access_exams_index_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('exams.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Examinations');
        $response->assertSee('Total Examinations');
        $response->assertSee('Published Results');
        $response->assertSee('Active & Scheduled');
        $response->assertSee('Current Session');
        $response->assertSee('All Examinations');
        $response->assertSee('Create Examination');
        $response->assertSee($this->exam->name);
    }

    public function test_admin_can_store_new_exam_with_bs_dates()
    {
        $unique = mt_rand(1000, 9999);
        $examName = 'Terminal Evaluation ' . $unique;

        $response = $this->actingAs($this->admin)->post(route('exams.store'), [
            'name' => $examName,
            'term' => 2,
            'exam_type' => 'second_terminal',
            'start_date_bs' => '2083-08-10',
            'end_date_bs' => '2083-08-20',
            'status' => 'draft',
            'description' => 'Winter Terminal Examination',
        ]);

        $response->assertStatus(302);

        $created = Exam::where('name', $examName)->first();
        $this->assertNotNull($created, 'Exam was not stored in database');
        $this->assertEquals('2083-08-10', $created->start_date_bs);
        $this->assertEquals('2083-08-20', $created->end_date_bs);
        $this->assertEquals('draft', $created->status);
    }

    public function test_admin_can_access_exam_edit_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('exams.edit', $this->exam->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Examination');
        $response->assertSee($this->exam->name);
    }

    public function test_admin_can_update_exam_details()
    {
        $updatedName = 'Renamed Exam ' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->admin)->put(route('exams.update', $this->exam->id), [
            'name' => $updatedName,
            'term' => 1,
            'exam_type' => 'first_terminal',
            'status' => 'completed',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('exams', [
            'id' => $this->exam->id,
            'name' => $updatedName,
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_publish_and_unpublish_exam()
    {
        // Unpublish
        $resUnpublish = $this->actingAs($this->admin)->put(route('exams.unpublish', $this->exam->id));
        $resUnpublish->assertStatus(302);
        $this->assertNotEquals('published', $this->exam->fresh()->status);
        $this->assertTrue(in_array($this->exam->fresh()->status, ['draft', 'completed']));

        // Publish
        $resPublish = $this->actingAs($this->admin)->put(route('exams.publish', $this->exam->id));
        $resPublish->assertStatus(302);
        $this->assertEquals('published', $this->exam->fresh()->status);
    }

    public function test_unauthenticated_user_cannot_access_exams()
    {
        $response = $this->get(route('exams.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_exams_management()
    {
        $student = User::where('user_type', 'student')->first()
            ?: User::factory()->create(['user_type' => 'student']);

        $response = $this->actingAs($student)->get(route('exams.index'));
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
