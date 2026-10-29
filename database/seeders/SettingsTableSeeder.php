<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['type' => 'current_session', 'description' => '2083/84 BS'],
            ['type' => 'system_title', 'description' => 'SMS Academy'],
            ['type' => 'system_name', 'description' => 'School Management System Academy'],
            ['type' => 'term_ends', 'description' => ''],
            ['type' => 'term_begins', 'description' => ''],
            ['type' => 'phone', 'description' => ''],
            ['type' => 'address', 'description' => ''],
            ['type' => 'system_email', 'description' => 'info@smsacademy.edu'],
            ['type' => 'alt_email', 'description' => ''],
            ['type' => 'email_host', 'description' => ''],
            ['type' => 'email_pass', 'description' => ''],
            ['type' => 'lock_exam', 'description' => 0],
            ['type' => 'logo', 'description' => ''],
            ['type' => 'next_term_fees_j', 'description' => '20000'],
            ['type' => 'next_term_fees_pn', 'description' => '25000'],
            ['type' => 'next_term_fees_p', 'description' => '25000'],
            ['type' => 'next_term_fees_n', 'description' => '25600'],
            ['type' => 'next_term_fees_s', 'description' => '15600'],
            ['type' => 'next_term_fees_c', 'description' => '1600'],
        ];

        foreach ($data as $d) {
            $exists = DB::table('settings')->where('type', $d['type'])->exists();
            if (!$exists) {
                DB::table('settings')->insert($d);
            }
        }
    }
}
