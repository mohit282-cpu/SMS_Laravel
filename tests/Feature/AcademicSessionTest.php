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
        // Clean up test events first so foreign keys don't block deletion
        \App\Models\Event::where('title', 'like', '%Test Event%')
            ->orWhere('title', 'like', '%Session%')
            ->delete();

        // Clean up test records
        AcademicYear::where('name', 'like', '%Test%')
            ->orWhere('name', 'like', '%Alpha%')
            ->orWhere('name', 'like', '%Beta%')
            ->orWhere('name', 'like', '%Session%')
            ->orWhere('description', 'like', '%Automated Test%')
            ->delete();

        // Restore active session
        if ($this->initialActiveSessionId) {
            $initial = AcademicYear::find($this->initialActiveSessionId);
            if ($initial && !$initial->is_closed) {
                AcademicYear::activateSession($this->initialActiveSessionId);
            } else {
                $openSession = AcademicYear::where('is_closed', false)->first();
                if ($openSession) {
                    AcademicYear::activateSession($openSession->id);
                }
            }
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

    /**
     * Test AcademicYear::current() is a pure read query without side effects and does not auto-activate.
     */
    public function test_current_session_is_pure_query_without_side_effects()
    {
        // Deactivate all sessions
        AcademicYear::where('is_active', true)->update(['is_active' => false]);

        // Calling current() must return null and NOT mutate any records
        $current = AcademicYear::current();
        $this->assertNull($current, 'AcademicYear::current() must return null when no open session is active.');

        $activeCount = AcademicYear::where('is_active', true)->count();
        $this->assertEquals(0, $activeCount, 'AcademicYear::current() must not auto-activate any session as a side effect.');
    }

    /**
     * Test closed session cannot be activated without an explicit reopen operation.
     */
    public function test_cannot_activate_closed_session_without_explicit_reopen()
    {
        $admin = $this->getAdminUser();

        $closedAy = AcademicYear::create([
            'name' => 'Session Closed Test ' . uniqid(),
            'year_bs' => 2081,
            'start_date' => '2024-04-13',
            'end_date' => '2025-04-13',
            'start_date_bs' => '2081-01-01',
            'end_date_bs' => '2081-12-30',
            'is_active' => false,
            'is_closed' => true,
        ]);

        // Attempt direct activation via controller
        $response = $this->actingAs($admin)->put(route('academic_years.activate', $closedAy->id));
        $response->assertSessionHas('flash_danger');

        $closedAy->refresh();
        $this->assertFalse((bool) $closedAy->is_active);
        $this->assertTrue((bool) $closedAy->is_closed);

        // Attempt activation via model method: must throw InvalidArgumentException
        try {
            AcademicYear::activateSession($closedAy->id);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Cannot activate a closed academic session', $e->getMessage());
        }

        $closedAy->delete();
    }

    /**
     * Test reopening a session does not automatically activate it.
     */
    public function test_reopening_session_does_not_automatically_activate()
    {
        $admin = $this->getAdminUser();

        $closedAy = AcademicYear::create([
            'name' => 'Session To Reopen ' . uniqid(),
            'year_bs' => 2082,
            'start_date' => '2025-04-14',
            'end_date' => '2026-04-13',
            'start_date_bs' => '2082-01-01',
            'end_date_bs' => '2082-12-30',
            'is_active' => false,
            'is_closed' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('academic_years.reopen', $closedAy->id));
        $response->assertSessionHas('flash_success');

        $closedAy->refresh();
        $this->assertFalse((bool) $closedAy->is_closed, 'Session should be unclosed.');
        $this->assertFalse((bool) $closedAy->is_active, 'Reopening must NOT automatically activate the session.');

        $closedAy->delete();
    }

    /**
     * Test cannot delete academic session if referenced by calendar events.
     */
    public function test_cannot_delete_session_referenced_by_events()
    {
        $admin = $this->getAdminUser();

        $ay = AcademicYear::create([
            'name' => 'Session With Event ' . uniqid(),
            'year_bs' => 2089,
            'start_date' => '2032-04-13',
            'end_date' => '2033-04-13',
            'start_date_bs' => '2089-01-01',
            'end_date_bs' => '2089-12-30',
            'is_active' => false,
            'is_closed' => false,
        ]);

        $event = \App\Models\Event::create([
            'title' => 'Test Event In Session',
            'category' => 'school_event',
            'start_date' => '2032-05-01',
            'end_date' => '2032-05-01',
            'start_date_bs' => '2089-01-18',
            'end_date_bs' => '2089-01-18',
            'academic_year_id' => $ay->id,
            'session' => $ay->name,
            'is_all_day' => true,
            'is_published' => true,
            'target_audience' => 'all',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('academic_years.destroy', $ay->id));
        $response->assertSessionHas('flash_danger');
        $this->assertDatabaseHas('academic_years', ['id' => $ay->id]);

        $event->delete();
        $ay->delete();
    }

    /**
     * Test session model consistency: year_bs matches start date BS and range bounds.
     */
    public function test_session_model_consistency_rules()
    {
        $admin = $this->getAdminUser();

        // 1. year_bs does not match start_date_bs start year
        $resMismatch = $this->actingAs($admin)->post(route('academic_years.store'), [
            'name' => 'Session Year Mismatch ' . uniqid(),
            'year_bs' => 2083,
            'start_date_bs' => '2084-01-01', // Year is 2084, but year_bs is 2083
            'end_date_bs' => '2084-12-30',
        ]);
        $resMismatch->assertSessionHas('flash_danger');

        // 2. Session spanning more than 2 calendar years
        $resTooLong = $this->actingAs($admin)->post(route('academic_years.store'), [
            'name' => 'Session Too Long ' . uniqid(),
            'year_bs' => 2083,
            'start_date_bs' => '2083-01-01',
            'end_date_bs' => '2086-12-30', // Spans 4 years
        ]);
        $resTooLong->assertSessionHas('flash_danger');
    }
}
