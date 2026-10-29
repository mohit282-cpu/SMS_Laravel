<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGuestIsRedirectedToLogin()
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function testLandingPageLoads()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testLoginPageLoads()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
