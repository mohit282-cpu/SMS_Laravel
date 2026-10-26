<?php

namespace Database\Seeders;

use App\Helpers\Qs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds for development and testing demo accounts only.
     * This seeder MUST NOT run in production environments.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function run()
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('DemoUsersSeeder is strictly forbidden in production environments.');
        }

        $this->createDemoUsers();
    }

    /**
     * Create sample demo accounts if they do not already exist.
     * Never overwrites existing accounts or customized passwords.
     */
    protected function createDemoUsers(): void
    {
        $defaultPassword = Hash::make('cj');

        $demoUsers = [
            [
                'name' => 'SMS Super Admin',
                'email' => 'cj@cj.com',
                'username' => 'cj',
                'password' => $defaultPassword,
                'user_type' => 'super_admin',
                'code' => strtoupper(Str::random(10)),
                'remember_token' => Str::random(10),
            ],
            [
                'name' => 'Admin KORA',
                'email' => 'admin@admin.com',
                'password' => $defaultPassword,
                'user_type' => 'admin',
                'username' => 'admin',
                'code' => strtoupper(Str::random(10)),
                'remember_token' => Str::random(10),
            ],
            [
                'name' => 'Teacher Chike',
                'email' => 'teacher@teacher.com',
                'user_type' => 'teacher',
                'username' => 'teacher',
                'password' => $defaultPassword,
                'code' => strtoupper(Str::random(10)),
                'remember_token' => Str::random(10),
            ],
            [
                'name' => 'Parent Kaba',
                'email' => 'parent@parent.com',
                'user_type' => 'parent',
                'username' => 'parent',
                'password' => $defaultPassword,
                'code' => strtoupper(Str::random(10)),
                'remember_token' => Str::random(10),
            ],
            [
                'name' => 'Accountant Jeff',
                'email' => 'accountant@accountant.com',
                'user_type' => 'accountant',
                'username' => 'accountant',
                'password' => $defaultPassword,
                'code' => strtoupper(Str::random(10)),
                'remember_token' => Str::random(10),
            ],
            [
                'name' => 'Librarian Sita',
                'email' => 'librarian@librarian.com',
                'user_type' => 'librarian',
                'username' => 'librarian',
                'password' => $defaultPassword,
                'code' => strtoupper(Str::random(10)),
                'remember_token' => Str::random(10),
            ],
        ];

        foreach ($demoUsers as $user) {
            $exists = DB::table('users')
                ->where('username', $user['username'])
                ->orWhere('email', $user['email'])
                ->exists();

            if (!$exists) {
                DB::table('users')->insert($user);
            }
        }
    }
}
