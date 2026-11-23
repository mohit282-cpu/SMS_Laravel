<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['title' => 'super_admin', 'name' => 'Super Admin', 'level' => 1],
            ['title' => 'admin', 'name' => 'Admin', 'level' => 2],
            ['title' => 'teacher', 'name' => 'Teacher', 'level' => 3],
            ['title' => 'parent', 'name' => 'Parent', 'level' => 4],
            ['title' => 'accountant', 'name' => 'Accountant', 'level' => 5],
            ['title' => 'librarian', 'name' => 'Librarian', 'level' => 6],
        ];

        foreach ($data as $d) {
            DB::table('user_types')->updateOrInsert(['title' => $d['title']], $d);
        }
    }
}
