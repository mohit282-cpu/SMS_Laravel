<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\User;
use Tests\TestCase;

class NoticeboardUiTest extends TestCase
{
    protected function tearDown(): void
    {
        Notice::where('title', 'like', '%Test UI Notice%')->delete();
        parent::tearDown();
    }

    protected function getSuperAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    public function test_noticeboard_index_loads_with_executive_header_and_kpis()
    {
        $admin = $this->getSuperAdmin();

        Notice::create([
            'title' => 'Test UI Notice Assembly',
            'content' => 'Morning assembly at 9:30 AM.',
            'notice_date' => now()->toDateString(),
            'notice_date_bs' => '2083-08-10',
            'role_visibility' => 'all',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('notices.index'));

        $response->assertStatus(200);
        $response->assertSee(__('School Noticeboard'));
        $response->assertSee(__('Total Notices'));
        $response->assertSee(__('Published'));
        $response->assertSee(__('Active Notices'));
        $response->assertSee(__('Drafts'));
        $response->assertSee(__('All Notices'));
        $response->assertSee(__('Create Notice'));
        $response->assertSee('Test UI Notice Assembly');
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('notices.index'))->assertRedirect(route('login'));
    }

    public function test_student_sees_published_notices_without_admin_controls()
    {
        $admin = $this->getSuperAdmin();
        $student = $this->getStudent();

        Notice::create([
            'title' => 'Test UI Notice Public Student',
            'content' => 'Public student circular.',
            'notice_date' => now()->toDateString(),
            'notice_date_bs' => '2083-08-10',
            'role_visibility' => 'all',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        Notice::create([
            'title' => 'Test UI Notice Internal Draft',
            'content' => 'Draft only.',
            'notice_date' => now()->toDateString(),
            'notice_date_bs' => '2083-08-10',
            'role_visibility' => 'all',
            'is_published' => false,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($student)->get(route('notices.index'));

        $response->assertStatus(200);
        $response->assertSee('Test UI Notice Public Student');
        $response->assertDontSee('Test UI Notice Internal Draft');
        $response->assertDontSee('tab-new-notice-link');
    }

    public function test_noticeboard_filtering_by_keyword_and_audience()
    {
        $admin = $this->getSuperAdmin();

        Notice::create([
            'title' => 'Test UI Notice Faculty Meeting',
            'content' => 'Teachers briefing.',
            'notice_date' => now()->toDateString(),
            'notice_date_bs' => '2083-08-10',
            'role_visibility' => 'teacher',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        Notice::create([
            'title' => 'Test UI Notice Picnic Day',
            'content' => 'Student excursion.',
            'notice_date' => now()->toDateString(),
            'notice_date_bs' => '2083-08-10',
            'role_visibility' => 'student',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('notices.index', [
            'q' => 'Faculty',
            'role_visibility' => 'teacher',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Test UI Notice Faculty Meeting');
        $response->assertDontSee('Test UI Notice Picnic Day');
    }

    public function test_notice_show_and_edit_pages_load_cleanly()
    {
        $admin = $this->getSuperAdmin();

        $notice = Notice::create([
            'title' => 'Test UI Notice Show Edit',
            'content' => 'Detailed announcement body.',
            'notice_date' => now()->toDateString(),
            'notice_date_bs' => '2083-08-10',
            'role_visibility' => 'all',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('notices.show', $notice->id))
            ->assertStatus(200)
            ->assertSee('Test UI Notice Show Edit')
            ->assertSee('Detailed announcement body.');

        $this->actingAs($admin)->get(route('notices.edit', $notice->id))
            ->assertStatus(200)
            ->assertSee('Test UI Notice Show Edit');
    }
}
