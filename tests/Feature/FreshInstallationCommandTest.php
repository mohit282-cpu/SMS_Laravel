<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FreshInstallationCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'mysql_test');
    }

    /** @test */
    public function it_can_execute_fresh_installation_successfully()
    {
        DB::connection('mysql_test')->table('users')->where('email', 'test_fresh_admin@school.edu.np')->delete();
        DB::connection('mysql_test')->table('settings')->where('type', 'installation_completed')->delete();

        $this->artisan('sms:install', [
            '--force' => true,
            '--database' => 'sms_laravel_test',
            '--connection' => 'mysql_test',
            '--admin-name' => 'Test Super Admin',
            '--admin-username' => 'test_superadmin',
            '--admin-email' => 'test_fresh_admin@school.edu.np',
            '--admin-phone' => '+977-9811111111',
            '--admin-password' => 'TestingPassword2083!',
            '--school-name' => 'Apex Test Secondary School',
            '--school-short' => 'Apex Test',
            '--school-address' => 'Itahari-5, Sunsari, Nepal',
            '--school-phone' => '+977-25-560001',
            '--school-email' => 'info@apextest.edu.np',
            '--session' => '2083/84 BS',
            '--skip-migrations' => true,
            '--skip-seeds' => true,
        ])->assertExitCode(0);

        // Verify Super Admin created in DB
        $admin = DB::connection('mysql_test')->table('users')->where('email', 'test_fresh_admin@school.edu.np')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('super_admin', $admin->user_type);
        $this->assertEquals('test_superadmin', $admin->username);
        $this->assertTrue(Hash::check('TestingPassword2083!', $admin->password));

        // Verify clean school settings saved
        $schoolName = DB::connection('mysql_test')->table('settings')->where('type', 'system_name')->value('description');
        $this->assertEquals('Apex Test Secondary School', $schoolName);

        $installed = DB::connection('mysql_test')->table('settings')->where('type', 'installation_completed')->value('description');
        $this->assertEquals('1', $installed);
    }

    /** @test */
    public function it_blocks_repeated_installation_without_force_flag()
    {
        DB::connection('mysql_test')->table('settings')->updateOrInsert(
            ['type' => 'installation_completed'],
            ['description' => '1']
        );

        $this->artisan('sms:install', [
            '--database' => 'sms_laravel_test',
            '--connection' => 'mysql_test',
            '--admin-name' => 'Duplicate Admin',
            '--admin-username' => 'dup_admin',
            '--admin-email' => 'dup_admin@school.edu.np',
            '--admin-password' => 'DupPassword2083!',
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_rejects_invalid_admin_email_format()
    {
        $this->artisan('sms:install', [
            '--force' => true,
            '--database' => 'sms_laravel_test',
            '--connection' => 'mysql_test',
            '--admin-name' => 'Bad Email Admin',
            '--admin-username' => 'bad_admin',
            '--admin-email' => 'not-an-email',
            '--admin-password' => 'ValidPassword123!',
            '--skip-migrations' => true,
            '--skip-seeds' => true,
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_rejects_admin_password_shorter_than_eight_characters()
    {
        $this->artisan('sms:install', [
            '--force' => true,
            '--database' => 'sms_laravel_test',
            '--connection' => 'mysql_test',
            '--admin-name' => 'Short Pass Admin',
            '--admin-username' => 'short_admin',
            '--admin-email' => 'short@school.edu.np',
            '--admin-password' => 'short',
            '--skip-migrations' => true,
            '--skip-seeds' => true,
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_fails_fast_on_invalid_database_host_or_name()
    {
        $this->artisan('sms:install', [
            '--force' => true,
            '--database' => 'non_existent_database_xyz_999',
            '--connection' => 'mysql_test',
            '--admin-name' => 'Test Admin',
            '--admin-username' => 'test_admin',
            '--admin-email' => 'admin@school.edu.np',
            '--admin-password' => 'ValidPassword123!',
            '--skip-migrations' => true,
            '--skip-seeds' => true,
        ])->assertExitCode(1);
    }

    /** @test */
    public function newly_created_super_admin_can_authenticate_and_access_dashboard()
    {
        $password = 'AuthTestPassword2083!';
        $email = 'auth_verify_admin@school.edu.np';

        $this->artisan('sms:install', [
            '--force' => true,
            '--database' => 'sms_laravel_test',
            '--connection' => 'mysql_test',
            '--admin-name' => 'Auth Admin',
            '--admin-username' => 'auth_admin',
            '--admin-email' => $email,
            '--admin-password' => $password,
            '--skip-migrations' => true,
            '--skip-seeds' => true,
        ])->assertExitCode(0);

        $response = $this->post('/login', [
            'identity' => $email,
            'password' => $password,
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticated();

        $user = User::on('mysql_test')->where('email', $email)->first();
        $this->actingAs($user);

        $settingsResponse = $this->get('/super_admin/settings');
        $settingsResponse->assertStatus(200);
        $settingsResponse->assertSee('Update System Settings');
    }

    /** @test */
    public function unauthenticated_users_cannot_access_settings()
    {
        auth()->logout();
        $response = $this->get('/super_admin/settings');
        $response->assertRedirect('/login');
    }
}
