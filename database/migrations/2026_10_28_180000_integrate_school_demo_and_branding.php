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
        // 1. Introduce Default Core Settings only if missing (never overwrite existing branding)
        $defaultCoreSettings = [
            'system_name' => 'School Management System Academy',
            'system_title' => 'SMS Academy',
            'address' => '18B North Central Park, Behind Central Square Tourist Center',
            'phone' => '0123456789',
            'system_email' => 'smsacademy@sms.com',
            'logo' => '',
        ];

        foreach ($defaultCoreSettings as $type => $val) {
            if (!DB::table('settings')->where('type', $type)->exists()) {
                DB::table('settings')->insert(['type' => $type, 'description' => $val]);
            }
        }

        // Additional Landing Page Configuration Settings (only if not already set)
        $defaultLandingSettings = [
            'admissions_open' => '1',
            'established_year' => '2052 B.S. (1995 A.D.)',
            'hero_title' => 'Inspiring Academic Excellence, Leadership & Character.',
            'hero_subtitle' => 'Nurturing young minds from Early Childhood through Grade 12 with forward-thinking STEM laboratories, comprehensive humanities, and dedicated character discipline.',
            'stat_students' => '1,850+',
            'stat_teachers' => '95+',
            'stat_pass_rate' => '99.4%',
            'stat_experience' => '30+ Years',
            'pamphlet_image' => 'landing_assets/images/school_pamphlet.jpg',
            'director_name' => 'Executive Director',
            'director_title' => 'Executive Director & Founder',
            'principal_name' => 'Principal',
            'principal_title' => 'Principal & Academic Head',
            'vice_principal_name' => 'Vice Principal',
            'vice_principal_title' => 'Vice Principal (Student Welfare)',
        ];

        foreach ($defaultLandingSettings as $type => $val) {
            if (!DB::table('settings')->where('type', $type)->exists()) {
                DB::table('settings')->insert(['type' => $type, 'description' => $val]);
            }
        }

        // 2. Add image_paths to events table if missing
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'image_paths')) {
            Schema::table('events', function (Blueprint $table) {
                $table->text('image_paths')->nullable()->after('description');
            });
            if (Schema::hasTable('settings')) {
                DB::table('settings')->updateOrInsert(
                    ['type' => 'migration_created_events_image_paths'],
                    ['description' => '1']
                );
            }
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
            if (Schema::hasTable('settings')) {
                DB::table('settings')->updateOrInsert(
                    ['type' => 'migration_created_entrance_results'],
                    ['description' => '1']
                );
            }
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
            if (Schema::hasTable('settings')) {
                DB::table('settings')->updateOrInsert(
                    ['type' => 'migration_created_admission_inquiries'],
                    ['description' => '1']
                );
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
        if (Schema::hasTable('settings')) {
            if (DB::table('settings')->where('type', 'migration_created_entrance_results')->value('description') === '1') {
                Schema::dropIfExists('entrance_results');
                DB::table('settings')->where('type', 'migration_created_entrance_results')->delete();
            }

            if (DB::table('settings')->where('type', 'migration_created_admission_inquiries')->value('description') === '1') {
                Schema::dropIfExists('admission_inquiries');
                DB::table('settings')->where('type', 'migration_created_admission_inquiries')->delete();
            }

            if (Schema::hasTable('events') && Schema::hasColumn('events', 'image_paths')) {
                if (DB::table('settings')->where('type', 'migration_created_events_image_paths')->value('description') === '1') {
                    Schema::table('events', function (Blueprint $table) {
                        $table->dropColumn('image_paths');
                    });
                    DB::table('settings')->where('type', 'migration_created_events_image_paths')->delete();
                }
            }
        }
    }
}
