<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IntegrateSchoolDemoAndBranding extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Update Core Settings to Apex Model Secondary School
        $settingsToUpdate = [
            'system_name' => 'Apex Model Secondary School',
            'system_title' => 'Apex Model Secondary School',
            'address' => 'Ward No. 3, Main Highway Corridor, Itahari-5, Sunsari, Koshi Province, Nepal',
            'phone' => '+977-25-560000',
            'system_email' => 'info@apexschool.edu.np',
            'logo' => '/landing_assets/images/logo.png',
        ];

        foreach ($settingsToUpdate as $type => $val) {
            DB::table('settings')->updateOrInsert(['type' => $type], ['description' => $val]);
        }

        // Additional Landing Page Configuration Settings
        $landingSettings = [
            'admissions_open' => '1',
            'established_year' => '2052 B.S. (1995 A.D.)',
            'hero_title' => 'Inspiring Academic Excellence, Leadership & Character.',
            'hero_subtitle' => 'Nurturing young minds from Early Childhood through Grade 12 with forward-thinking STEM laboratories, comprehensive humanities, and dedicated character discipline in Koshi Province.',
            'stat_students' => '1,850+',
            'stat_teachers' => '95+',
            'stat_pass_rate' => '99.4%',
            'stat_experience' => '30+ Years',
            'pamphlet_image' => 'landing_assets/images/school_pamphlet.jpg',
            'director_name' => 'Er. Bijay Shrestha',
            'director_title' => 'Executive Director & Founder',
            'principal_name' => 'Prof. Dr. Madhav Prasad Khatiwada',
            'principal_title' => 'Principal & Academic Head',
            'vice_principal_name' => 'Mrs. Sunita Thapa',
            'vice_principal_title' => 'Vice Principal (Student Welfare)',
        ];

        foreach ($landingSettings as $type => $val) {
            DB::table('settings')->updateOrInsert(['type' => $type], ['description' => $val]);
        }

        // 2. Add image_paths to events table if missing
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'image_paths')) {
            Schema::table('events', function (Blueprint $table) {
                $table->text('image_paths')->nullable()->after('description');
            });
        }

        // 3. Create entrance_results table for New Admission Examination Verification
        if (!Schema::hasTable('entrance_results')) {
            Schema::create('entrance_results', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('batch_year', 100)->default('2083 B.S.')->index();
                $table->string('exam_name', 255)->index();
                $table->string('class_name', 100)->index();
                $table->string('section_name', 100)->default('GENERAL');
                $table->string('symbol_no', 100)->index();
                $table->string('roll_no', 100)->nullable();
                $table->string('student_name', 255)->index();
                $table->string('attendance', 100)->default('N/A');
                $table->decimal('gpa', 4, 2)->default(0.00);
                $table->string('remarks', 255)->default('ELIGIBLE FOR ADMISSION');
                $table->longText('marks_json');
                $table->timestamps();
            });
        }

        // 4. Create admission_inquiries table
        if (!Schema::hasTable('admission_inquiries')) {
            Schema::create('admission_inquiries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('full_name', 255);
                $table->string('phone', 100);
                $table->string('email', 255)->nullable();
                $table->string('academic_level', 255)->nullable();
                $table->text('message');
                $table->string('status', 50)->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. Seed historical entrance results from School-Demo SQLite database if available
        $resultsSqlitePath = base_path('School-Demo/results/database/results.sqlite');
        if (file_exists($resultsSqlitePath) && DB::table('entrance_results')->count() === 0) {
            try {
                $sqlite = new PDO('sqlite:' . $resultsSqlitePath);
                $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $rows = $sqlite->query("SELECT * FROM student_results")->fetchAll(PDO::FETCH_ASSOC);

                $insertBatch = [];
                foreach ($rows as $row) {
                    $insertBatch[] = [
                        'batch_year' => $row['batch_year'] ?? '2083 B.S.',
                        'exam_name' => $row['exam_name'],
                        'class_name' => $row['class_name'],
                        'section_name' => $row['section_name'] ?? 'GENERAL',
                        'symbol_no' => $row['symbol_no'],
                        'roll_no' => $row['roll_no'] ?? '',
                        'student_name' => $row['student_name'],
                        'attendance' => $row['attendance'] ?? 'N/A',
                        'gpa' => $row['gpa'] ?? 0.00,
                        'remarks' => $row['remarks'] ?? 'ELIGIBLE FOR ADMISSION',
                        'marks_json' => $row['marks_json'],
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => now(),
                    ];

                    if (count($insertBatch) >= 50) {
                        DB::table('entrance_results')->insert($insertBatch);
                        $insertBatch = [];
                    }
                }
                if (!empty($insertBatch)) {
                    DB::table('entrance_results')->insert($insertBatch);
                }
            } catch (\Exception $e) {
                // Ignore seed error if file is inaccessible
            }
        }

        // 6. Seed events from School-Demo SQLite database if events table is empty of campus events
        $eventsSqlitePath = base_path('School-Demo/database/school.sqlite');
        if (file_exists($eventsSqlitePath)) {
            try {
                $sqliteSchool = new PDO('sqlite:' . $eventsSqlitePath);
                $events = $sqliteSchool->query("SELECT * FROM events")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($events as $ev) {
                    $exists = DB::table('events')->where('title', $ev['title'])->exists();
                    if (!$exists) {
                        // Adjust image path prefix to landing_assets
                        $imagesJson = $ev['image_paths'];
                        $imagesJson = str_replace('assets/images/', 'landing_assets/images/', $imagesJson);

                        DB::table('events')->insert([
                            'title' => $ev['title'],
                            'description' => $ev['description'],
                            'category' => 'school_event',
                            'start_date' => now()->toDateString(),
                            'end_date' => now()->toDateString(),
                            'start_date_bs' => $ev['event_date_bs'] ?? '2083-02-18',
                            'end_date_bs' => $ev['event_date_bs'] ?? '2083-02-18',
                            'is_all_day' => true,
                            'is_published' => true,
                            'location' => 'Apex Campus & Sports Arena, Koshi Province',
                            'image_paths' => $imagesJson,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Ignore seed error
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entrance_results');
        Schema::dropIfExists('admission_inquiries');
        if (Schema::hasColumn('events', 'image_paths')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('image_paths');
            });
        }
    }
}
