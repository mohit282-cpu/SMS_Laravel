<?php

namespace Tests\Feature;

use App\User;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that UsersTableSeeder never overwrites existing user passwords, emails, or names.
     */
    public function test_seeder_does_not_overwrite_existing_user_data()
    {
        $customPassword = Hash::make('SuperSecretCustomPassword123!');
        $customEmail = 'my_custom_admin_email@school.edu.np';
        $customName = 'Original Administrator Name';

        // Update or ensure 'admin' exists with custom details
        $admin = User::where('username', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => $customName,
                'email' => $customEmail,
                'username' => 'admin',
                'password' => $customPassword,
                'user_type' => 'admin',
                'code' => 'CUSTOMCODE',
            ]);
        } else {
            $admin->update([
                'name' => $customName,
                'email' => $customEmail,
                'password' => $customPassword,
            ]);
        }

        // Run the seeder
        $seeder = new UsersTableSeeder();
        $seeder->run();

        // Refresh admin from database
        $admin->refresh();

        // Verify that custom details were NOT overwritten with default seeder values ('cj', 'admin@admin.com', etc.)
        $this->assertEquals($customName, $admin->name, 'User name was overwritten by seeder');
        $this->assertEquals($customEmail, $admin->email, 'User email was overwritten by seeder');
        $this->assertTrue(
            Hash::check('SuperSecretCustomPassword123!', $admin->password),
            'User password was overwritten or reset by seeder'
        );
        $this->assertFalse(
            Hash::check('cj', $admin->password),
            'User password was reset to the default seeder password'
        );
    }

    /**
     * Test that running seeder multiple times is idempotent and throws no duplicate exceptions.
     */
    public function test_seeder_is_idempotent()
    {
        $seeder = new UsersTableSeeder();

        // Run twice in a row
        $seeder->run();
        $seeder->run();

        $this->assertTrue(true, 'Seeder ran multiple times without duplicate exceptions');
    }

    /**
     * Test that production environment prevents creating demo accounts or known default passwords.
     */
    public function test_production_seeding_does_not_create_demo_users_or_default_credentials()
    {
        $originalEnv = app()->environment();
        $this->app['env'] = 'production';

        try {
            $initialCount = User::count();

            $seeder = new UsersTableSeeder();
            $seeder->run();

            // Assert that running normal seeder in production did not create any new demo accounts
            $this->assertEquals($initialCount, User::count(), 'Normal seeder in production must not add demo users');
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    /**
     * Test that DemoUsersSeeder throws a RuntimeException in production.
     */
    public function test_demo_users_seeder_is_blocked_in_production()
    {
        $originalEnv = app()->environment();
        $this->app['env'] = 'production';

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('DemoUsersSeeder is strictly forbidden in production environments.');

            $demoSeeder = new DemoUsersSeeder();
            $demoSeeder->run();
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    /**
     * Test that DemoUsersSeeder works safely in testing/local environment without throwing.
     */
    public function test_demo_users_seeder_executes_safely_in_non_production()
    {
        $originalEnv = app()->environment();
        $this->app['env'] = 'testing';

        try {
            $demoSeeder = new DemoUsersSeeder();
            $demoSeeder->run();

            $this->assertTrue(true, 'Demo seeder executed successfully in non-production');
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }
}
