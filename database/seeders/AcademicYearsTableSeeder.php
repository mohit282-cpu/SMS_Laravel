<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $years = [
            [
                'name' => '2083/84 BS',
                'year_bs' => 2083,
                'start_date' => '2026-04-14',
                'end_date' => '2027-04-13',
                'start_date_bs' => '2083-01-01',
                'end_date_bs' => '2083-12-30',
                'is_active' => true,
                'is_closed' => false,
                'description' => 'Current Academic Session 2083/84',
            ],
            [
                'name' => '2082/83 BS',
                'year_bs' => 2082,
                'start_date' => '2025-04-14',
                'end_date' => '2026-04-13',
                'start_date_bs' => '2082-01-01',
                'end_date_bs' => '2082-12-30',
                'is_active' => false,
                'is_closed' => true,
                'description' => 'Previous Academic Session 2082/83 (Archived)',
            ],
        ];

        foreach ($years as $year) {
            AcademicYear::firstOrCreate(
                ['year_bs' => $year['year_bs']],
                $year
            );
        }
    }
}
