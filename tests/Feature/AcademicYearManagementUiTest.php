<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AcademicYearManagementUiTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $this->academicYear = AcademicYear::where('is_active', true)->first();
        if (!$this->academicYear) {
            $this->academicYear = AcademicYear::first() ?: AcademicYear::create([
                'name' => '2083 BS',
                'year_bs' => 2083,
                'start_date_bs' => '2083-01-01',
                'end_date_bs' => '2083-12-30',
                'start_date' => '2026-04-14',
                'end_date' => '2027-04-13',
                'is_active' => true,
                'is_closed' => false,
            ]);
        }
    }

    public function test_super_admin_can_access_academic_years_index_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('academic_years.index'));

        $response->assertStatus(200);
        $response->assertSee('Academic Years (BS)');
        $response->assertSee('Total Sessions');
        $response->assertSee('Active Session');
        $response->assertSee('Open Sessions');
        $response->assertSee('Archived Sessions');
        $response->assertSee('All Academic Years');
        $response->assertSee('Create Academic Year');
        $response->assertSee($this->academicYear->name);
    }

    public function test_admin_can_store_new_academic_year()
    {
        $yearBs = 2085;
        $sessionName = $yearBs . ' BS Test';

        $response = $this->actingAs($this->admin)->post(route('academic_years.store'), [
            'name' => $sessionName,
            'year_bs' => $yearBs,
            'start_date_bs' => $yearBs . '-01-01',
            'end_date_bs' => $yearBs . '-12-30',
            'description' => 'Test Academic Session',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('academic_years', [
            'name' => $sessionName,
            'year_bs' => $yearBs,
        ]);
    }

    public function test_admin_can_access_academic_year_edit_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('academic_years.edit', $this->academicYear->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Academic Session');
        $response->assertSee($this->academicYear->name);
    }

    public function test_admin_can_update_academic_year()
    {
        $updatedDesc = 'Updated Notes ' . mt_rand(100, 999);

        $response = $this->actingAs($this->admin)->put(route('academic_years.update', $this->academicYear->id), [
            'name' => $this->academicYear->name,
            'year_bs' => $this->academicYear->year_bs,
            'start_date_bs' => $this->academicYear->start_date_bs,
            'end_date_bs' => $this->academicYear->end_date_bs,
            'description' => $updatedDesc,
            'is_active' => 1,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('academic_years', [
            'id' => $this->academicYear->id,
            'description' => $updatedDesc,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_academic_years()
    {
        $response = $this->get(route('academic_years.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_academic_years()
    {
        $student = User::where('user_type', 'student')->first()
            ?: User::factory()->create(['user_type' => 'student']);

        $response = $this->actingAs($student)->get(route('academic_years.index'));
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
