<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionReferenceSeeder extends Seeder
{
    /**
     * Run master and reference seeders required for a clean production school installation.
     * Strictly excludes demo users, mock students, fake classes, and sample transactions.
     *
     * @return void
     */
    public function run()
    {
        // Core master reference data
        $this->call(BloodGroupsTableSeeder::class);
        $this->call(UserTypesTableSeeder::class);
        $this->call(ClassTypesTableSeeder::class);
        $this->call(GradesTableSeeder::class);
        $this->call(NationalitiesTableSeeder::class);
        $this->call(NepalGeographySeeder::class);
        $this->call(AcademicYearsTableSeeder::class);
        $this->call(SkillsTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
    }
}

