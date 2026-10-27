<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentRecordsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (app()->environment('production')) {
            return;
        }

        $this->createStudentRecord();
        $this->createManyStudentRecords(3);
    }

    protected function createManyStudentRecords(int $count)
    {
        $sections = Section::all();

        foreach ($sections as $section){
          User::factory()
                ->has(
                    StudentRecord::factory()
                    ->state([
                    'section_id' => $section->id,
                    'my_class_id' => $section->my_class_id,
                    'user_id' => function(User $user){
                        return ['user_id' => $user->id];
                    },
                ]), 'student_record')
                ->count($count)
                ->create([
                    'user_type' => 'student',
                    'password' => Hash::make('student'),
                ]);
        }

    }

    protected function createStudentRecord()
    {
        $section = Section::first();

        $user = User::firstOrCreate(
            ['username' => 'student'],
            [
                'name' => 'Student CJ',
                'user_type' => 'student',
                'password' => Hash::make('cj'),
                'email' => 'student@student.com',
            ]
        );

        StudentRecord::firstOrCreate(
            ['user_id' => $user->id],
            [
                'my_class_id' => $section ? $section->my_class_id : null,
                'section_id' => $section ? $section->id : null,
            ]
        );
    }
}
