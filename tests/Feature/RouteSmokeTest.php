<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use DatabaseTransactions;

    protected function getSuperAdmin(): User
    {
        return User::where('user_type', 'super_admin')->first();
    }

    public function test_students_index_route_resolves_and_redirects_cleanly()
    {
        $admin = $this->getSuperAdmin();
        $response = $this->actingAs($admin)->get(route('students.index'));

        // Should cleanly redirect to students.list for the first class (or 200 if no classes)
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
        if ($response->isRedirection()) {
            $this->assertStringContainsString('list', $response->headers->get('Location'));
        }
    }

    public function test_users_create_route_redirects_cleanly()
    {
        $admin = $this->getSuperAdmin();
        $response = $this->actingAs($admin)->get(route('users.create'));
        $response->assertRedirect(route('users.index'));
    }

    public function test_key_admin_views_load_successfully()
    {
        $admin = $this->getSuperAdmin();

        $routes = [
            'dashboard',
            'users.index',
            'classes.index',
            'sections.index',
            'subjects.index',
            'exams.index',
            'dorms.index',
            'payments.index',
            'notices.index',
            'study_materials.index',
            'academic_years.index',
            'events.index',
            'holidays.index',
            'books.index',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($admin)->get(route($routeName));
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Route [{$routeName}] returned HTTP {$response->getStatusCode()} instead of 200."
            );
        }
    }
}
