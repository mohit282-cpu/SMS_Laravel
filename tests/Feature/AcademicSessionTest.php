<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicSessionTest extends TestCase
{
    protected ?int $initialActiveSessionId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $curr = AcademicYear::current();
        $this->initialActiveSessionId = $curr ? $curr->id : null;
    }

    protected function tearDown(): void
    {
        // Clean up test records
        AcademicYear::where('name', 'like', '%Test Session%')
            ->orWhere('name', 'like', '%Alpha%')
            ->orWhere('name', 'like', '%Beta%')
            ->orWhere('name', 'like', '%Session To Close%')
            ->orWhere('description', 'like', '%Automated Test%')
            ->delete();

        // Restore active session
        if ($this->initialActiveSessionId) {
            AcademicYear::activateSession($this->initialActiveSessionId);
        }

        parent::tearDown();
    }

    protected function getAdminUser()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    /**
     * Test admin can create a new academic session with explicit BS date range.
     */
    public function test_admin_can_create_academic_session()
    {
        $admin = $this->getAdminUser();

        $sessionName = '2085/86 BS Test Session ' . uniqid();
        $response = $this->actingAs($admin)->post(route('academic_years.store'), [
            'name' => $sessionName,
            'year_bs' => 2085,
            'start_date_bs' => '2085-01-01',
            'end_date_bs' => '2085-12-30',
            'is_active' => '1',
            'description' => 'Automated Test Session',
        ]);

        $response->assertSessionHas('flash_success');

        $session = AcademicYear::where('name', $sessionName)->first();
        $this->assertNotNull($session);
        $this->assertEquals(2085, $session->year_bs);
        $this->assertEquals('2085-01-01', $session->start_date_bs);
        $this->assertEquals('2085-12-30', $session->end_date_bs);
        $this->assertTrue((bool) $session->is_active);
    }

    /**
     * Test activating a session deactivates any existing active session (single-active rule).
     */
    public function test_activating_session_enforces_single_active_session()
    {
        $admin = $this->getAdminUser();

        $ayA = AcademicYear::create([
            'name' => 'Session 2087 BS Alpha ' . uniqid(),
            'year_bs' => 2087,
            'start_date' => '2030-04-14',
            'end_date' => '2031-04-13',
            'start_date_bs' => '2087-01-01',
            'end_date_bs' => '2087-12-30',
            'is_active' => true,
        ]);

        $ayB = AcademicYear::create([
            'name' => 'Session 2088 BS Beta ' . uniqid(),
            'year_bs' => 2088,
            'start_date' => '2031-04-14',
            'end_date' => '2032-04-13',
            'start_date_bs' => '2088-01-01',
            'end_date_bs' => '2088-12-30',
            'is_active' => false,
        ]);

        // Activate Session B
        $response = $this->actingAs($admin)->put(route('academic_years.activate', $ayB->id));
        $response->assertSessionHas('flash_success');

        $ayA->refresh();
        $ayB->refresh();
        $this->assertFalse((bool) $ayA->is_active);
        $this->assertTrue((bool) $ayB->is_active);

        // Verify only ONE session is active across the database
        $activeCount = AcademicYear::where('is_active', true)->count();
        $this->assertEquals(1, $activeCount);
    }

    /**
     * Test validation rejects invalid date range (end date earlier or same as start date).
     */
    public function test_rejects_invalid_date_range()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->post(route('academic_years.store'), [
            'name' => 'Invalid Chronology Session ' . uniqid(),
            'year_bs' => 2084,
            'start_date_bs' => '2084-10-01',
            'end_date_bs' => '2084-05-01', // earlier than start date
        ]);

        $response->assertSessionHas('flash_danger');
    }

    /**
     * Test closing an active session automatically activates another session.
     */
    public function test_closing_session_maintains_valid_active_state()
    {
        $admin = $this->getAdminUser();

        $ay = AcademicYear::create([
            'name' => 'Session To Close ' . uniqid(),
            'year_bs' => 2086,
            'start_date' => '2029-04-14',
            'end_date' => '2030-04-13',
            'start_date_bs' => '2086-01-01',
            'end_date_bs' => '2086-12-30',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('academic_years.close', $ay->id));
        $response->assertSessionHas('flash_success');

        $ay->refresh();
        $this->assertTrue((bool) $ay->is_closed);
        $this->assertFalse((bool) $ay->is_active);

        // Ensure there is still an active session in the system
        $newActive = AcademicYear::current();
        $this->assertNotNull($newActive);
        $this->assertTrue((bool) $newActive->is_active);
    }

    /**
     * Test historical sessions remain accessible for queries and reports.
     */
    public function test_historical_sessions_remain_accessible()
    {
        $sessions = AcademicYear::all();
        $this->assertNotEmpty($sessions);

        foreach ($sessions as $s) {
            $this->assertNotNull($s->code);
            $this->assertNotNull($s->name);
        }

        $current = AcademicYear::current();
        $this->assertNotNull($current);
        $this->assertTrue((bool) $current->is_active);
    }

    /**
     * Test deleting session is prevented if it is the active session.
     */
    public function test_cannot_delete_active_session()
    {
        $admin = $this->getAdminUser();

        $currentSession = AcademicYear::current();
        $this->assertNotNull($currentSession);

        // Active session cannot be deleted
        $response = $this->actingAs($admin)->delete(route('academic_years.destroy', $currentSession->id));
        $response->assertSessionHas('flash_danger');
        $this->assertDatabaseHas('academic_years', ['id' => $currentSession->id]);
    }
}
