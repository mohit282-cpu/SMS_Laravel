<?php

namespace Tests\Feature;

use App\Models\Dorm;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DormManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $dorm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $this->dorm = Dorm::first() ?: Dorm::create([
            'name' => 'Lhotse Hall ' . mt_rand(100, 999),
            'description' => 'Junior Boys Wing',
        ]);
    }

    public function test_super_admin_can_access_dorms_index_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('dorms.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Dormitories');
        $response->assertSee('Total Hostels');
        $response->assertSee('Boarding Students');
        $response->assertSee('Day Scholars');
        $response->assertSee('Total Enrolled');
        $response->assertSee('Create New Dorm');
        $response->assertSee($this->dorm->name);
    }

    public function test_admin_can_store_new_dorm()
    {
        $unique = mt_rand(1000, 9999);
        $dormName = 'Annapurna House ' . $unique;
        $description = 'North Wing Dormitory';

        $response = $this->actingAs($this->admin)->post(route('dorms.store'), [
            'name' => $dormName,
            'description' => $description,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('dorms', [
            'name' => $dormName,
            'description' => $description,
        ]);
    }

    public function test_admin_can_access_dorm_edit_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('dorms.edit', $this->dorm->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Dormitory');
        $response->assertSee($this->dorm->name);
    }

    public function test_admin_can_update_dorm_details()
    {
        $updatedName = 'Renamed Hostel ' . mt_rand(1000, 9999);
        $updatedDesc = 'Updated description notes';

        $response = $this->actingAs($this->admin)->put(route('dorms.update', $this->dorm->id), [
            'name' => $updatedName,
            'description' => $updatedDesc,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('dorms', [
            'id' => $this->dorm->id,
            'name' => $updatedName,
            'description' => $updatedDesc,
        ]);
    }

    public function test_super_admin_can_delete_dorm()
    {
        $temporaryDorm = Dorm::create([
            'name' => 'Disposable Hostel ' . mt_rand(1000, 9999),
            'description' => 'Temporary test dorm',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('dorms.destroy', $temporaryDorm->id));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('dorms', [
            'id' => $temporaryDorm->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_dorms()
    {
        $response = $this->get(route('dorms.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_dorms_management()
    {
        $student = User::where('user_type', 'student')->first()
            ?: User::factory()->create(['user_type' => 'student']);

        $response = $this->actingAs($student)->get(route('dorms.index'));
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
