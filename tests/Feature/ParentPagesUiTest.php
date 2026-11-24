<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\ClassType;
use App\Models\Lga;
use App\Models\MyClass;
use App\Models\Nationality;
use App\Models\Section;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParentPagesUiTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $parentUser;
    protected $studentUser;
    protected $studentRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('user_type', 'super_admin')->first()
            ?: User::factory()->create(['user_type' => 'super_admin']);

        $nationality = Nationality::first() ?: Nationality::create(['name' => 'Nepali']);
        $state = \App\Models\State::first() ?: \App\Models\State::create(['name' => 'Bagmati', 'nal_id' => $nationality->id]);
        $lga = Lga::first() ?: Lga::create(['name' => 'Kathmandu', 'state_id' => $state->id]);
        $classType = ClassType::first() ?: ClassType::create(['name' => 'Primary', 'code' => 'P']);
        $myClass = MyClass::first() ?: MyClass::create(['name' => 'Class 1', 'class_type_id' => $classType->id]);
        $section = Section::where('my_class_id', $myClass->id)->first()
            ?: Section::create(['name' => 'A', 'my_class_id' => $myClass->id, 'active' => 1]);

        // Create a test parent
        $unique = mt_rand(10000, 99999);
        $this->parentUser = User::create([
            'name' => 'Hari Prasad Sharma ' . $unique,
            'phone' => '9841' . $unique,
            'email' => 'hari' . $unique . '@example.com',
            'user_type' => 'parent',
            'username' => 'p_hari' . $unique,
            'password' => bcrypt('password'),
            'code' => strtoupper(substr(md5(mt_rand()), 0, 10)),
            'is_active' => true,
        ]);

        // Create a test student linked to this parent
        $this->studentUser = User::create([
            'name' => 'Sita Sharma ' . $unique,
            'user_type' => 'student',
            'username' => 'st_sita' . $unique,
            'password' => bcrypt('password'),
            'code' => strtoupper(substr(md5(mt_rand()), 0, 10)),
        ]);

        $this->studentRecord = StudentRecord::create([
            'user_id' => $this->studentUser->id,
            'my_class_id' => $myClass->id,
            'section_id' => $section->id,
            'my_parent_id' => $this->parentUser->id,
            'parent_relationship' => 'Father',
            'adm_no' => 'ADM' . $unique,
            'year_admitted' => '2024',
            'session' => '2024-2025',
        ]);
    }

    public function test_super_admin_can_access_parent_index_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('parents.index'));

        $response->assertStatus(200);
        $response->assertSee('Parent Account Management');
        $response->assertSee('Total Parents');
        $response->assertSee('Active Accounts');
        $response->assertSee('Pending Activation');
        $response->assertSee('Unlinked Students');
        $response->assertSee('Parents Directory');
        $response->assertSee('Unlinked Students Queue');
        $response->assertSee('Quick Create Parent');
        $response->assertSee($this->parentUser->name);
        $response->assertSee($this->parentUser->phone);
    }

    public function test_parent_index_search_filters_results()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('parents.index', ['search' => $this->parentUser->phone]));

        $response->assertStatus(200);
        $response->assertSee($this->parentUser->name);
        $response->assertSee($this->parentUser->phone);
    }

    public function test_super_admin_can_access_parent_show_page()
    {
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'en'])->get(route('parents.show', Qs::hash($this->parentUser->id)));

        $response->assertStatus(200);
        $response->assertSee($this->parentUser->name);
        $response->assertSee($this->studentUser->name);
        $response->assertSee('Linked Students');
        $response->assertSee('Edit Parent Details');
        $response->assertSee('Generate Fresh Activation Link');
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get(route('parents.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_parent_management()
    {
        $response = $this->actingAs($this->studentUser)->get(route('parents.index'));
        // TeamSA middleware or redirect
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
