<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Production-hardened: never creates demo users or known default credentials.
     * Preserves existing user credentials and customized profiles.
     *
     * @return void
     */
    public function run()
    {
        // Never create demo accounts or overwrite users if accounts already exist
        if (DB::table('users')->count() > 0) {
            return;
        }

        // When table is completely empty, provision the initial administrator
        // strictly via explicitly configured environment variables.
        $this->provisionInitialAdministrator();
    }

    /**
     * Provision initial super-administrator using explicit environment variables.
     * Never logs sensitive credentials to application logs.
     */
    protected function provisionInitialAdministrator(): void
    {
        $initialEmail = env('INITIAL_ADMIN_EMAIL');
        $initialPassword = env('INITIAL_ADMIN_PASSWORD');

        if (!empty($initialEmail) && !empty($initialPassword)) {
            $username = env('INITIAL_ADMIN_USERNAME', 'admin');
            $name = env('INITIAL_ADMIN_NAME', 'System Administrator');

            $exists = DB::table('users')
                ->where('username', $username)
                ->orWhere('email', $initialEmail)
                ->exists();

            if (!$exists) {
                DB::table('users')->insert([
                    'name' => $name,
                    'email' => $initialEmail,
                    'username' => $username,
                    'password' => Hash::make($initialPassword),
                    'user_type' => 'super_admin',
                    'code' => strtoupper(Str::random(10)),
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
