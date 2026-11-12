<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNepalAcademicAndCurriculumTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('nepal_education_levels')) {
            Schema::create('nepal_education_levels', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->string('name_ne', 150)->nullable();
                $table->string('grade_range', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('curricula')) {
            Schema::create('curricula', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->string('version', 40)->default('1.0');
                $table->unsignedInteger('academic_year_id')->nullable();
                $table->unsignedInteger('education_level_id')->nullable();
                $table->string('stream', 80)->nullable();
                $table->string('effective_from_bs', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('education_level_id')->references('id')->on('nepal_education_levels')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('curriculum_subjects')) {
            Schema::create('curriculum_subjects', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('curriculum_id');
                $table->unsignedInteger('subject_id')->nullable();
                $table->string('subject_code', 40)->nullable();
                $table->string('subject_name', 150);
                $table->string('subject_name_ne', 180)->nullable();
                $table->enum('subject_type', ['compulsory', 'optional', 'elective', 'practical', 'internal'])->default('compulsory');
                $table->unsignedSmallInteger('theory_max')->default(75);
                $table->unsignedSmallInteger('theory_pass')->default(27);
                $table->unsignedSmallInteger('practical_max')->default(25);
                $table->unsignedSmallInteger('practical_pass')->default(10);
                $table->unsignedSmallInteger('internal_max')->default(0);
                $table->decimal('credit_hour', 4, 2)->default(4.00);
                $table->timestamps();

                $table->foreign('curriculum_id')->references('id')->on('curricula')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('grading_scales')) {
            Schema::create('grading_scales', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('authority', 40)->default('CDC');
                $table->string('academic_year', 40)->nullable()->index();
                $table->string('education_level_code', 40)->nullable()->index();
                $table->string('grade_letter', 10);
                $table->decimal('min_percentage', 5, 2);
                $table->decimal('max_percentage', 5, 2);
                $table->decimal('grade_point', 3, 2);
                $table->string('remark', 80);
                $table->string('remark_ne', 100)->nullable();
                $table->boolean('is_passing')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('see_candidates')) {
            Schema::create('see_candidates', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->string('academic_year', 40)->index();
                $table->string('symbol_number', 40)->nullable()->index();
                $table->string('registration_number', 60)->nullable();
                $table->string('school_code', 40)->nullable();
                $table->boolean('is_eligible')->default(true);
                $table->string('optional_subject_1', 120)->nullable();
                $table->string('optional_subject_2', 120)->nullable();
                $table->decimal('internal_marks_total', 6, 2)->nullable();
                $table->decimal('practical_marks_total', 6, 2)->nullable();
                $table->decimal('theory_marks_total', 6, 2)->nullable();
                $table->decimal('gpa', 3, 2)->nullable();
                $table->string('overall_grade', 10)->nullable();
                $table->enum('result_status', ['registered', 'eligible', 'appeared', 'passed', 'ng', 'absent', 'withheld'])->default('registered');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->unique(['student_record_id', 'academic_year']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('see_candidates');
        Schema::dropIfExists('grading_scales');
        Schema::dropIfExists('curriculum_subjects');
        Schema::dropIfExists('curricula');
        Schema::dropIfExists('nepal_education_levels');
    }
}

