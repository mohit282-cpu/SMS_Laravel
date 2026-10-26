<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateExamsAndMarksForNepalSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Extend exams table
        if (!Schema::hasColumn('exams', 'exam_type')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->string('exam_type', 50)->default('terminal')->after('name');
                $table->date('start_date')->nullable()->after('year');
                $table->date('end_date')->nullable()->after('start_date');
                $table->string('start_date_bs', 10)->nullable()->after('end_date');
                $table->string('end_date_bs', 10)->nullable()->after('start_date_bs');
                $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'published'])->default('published')->after('end_date_bs');
                $table->text('description')->nullable()->after('status');
                $table->unsignedInteger('published_by')->nullable()->after('description');
                $table->timestamp('published_at')->nullable()->after('published_by');
            });

            try {
                Schema::table('exams', function (Blueprint $table) {
                    $table->dropUnique(['term', 'year']);
                    $table->unique(['name', 'year', 'term']);
                });
            } catch (\Exception $e) {}
        }

        // 2. Extend subjects table with theory / practical maximum and pass marks
        if (!Schema::hasColumn('subjects', 'theory_max')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unsignedSmallInteger('theory_max')->default(75)->after('slug');
                $table->unsignedSmallInteger('theory_pass')->default(27)->after('theory_max');
                $table->unsignedSmallInteger('practical_max')->default(25)->after('theory_pass');
                $table->unsignedSmallInteger('practical_pass')->default(10)->after('practical_max');
                $table->unsignedSmallInteger('total_max')->default(100)->after('practical_pass');
                $table->unsignedSmallInteger('total_pass')->default(35)->after('total_max');
                $table->decimal('credit_hour', 4, 2)->default(4.00)->after('total_pass');
                $table->boolean('has_practical')->default(false)->after('credit_hour');
            });
        }

        // 3. Extend marks table with theory/practical and absent/exempt flags
        if (!Schema::hasColumn('marks', 'theory_mark')) {
            Schema::table('marks', function (Blueprint $table) {
                $table->decimal('theory_mark', 5, 2)->nullable()->after('exm');
                $table->decimal('practical_mark', 5, 2)->nullable()->after('theory_mark');
                $table->decimal('total_mark', 5, 2)->nullable()->after('practical_mark');
                $table->decimal('gpa_point', 3, 2)->nullable()->after('grade_id');
                $table->string('grade_name', 5)->nullable()->after('gpa_point');
                $table->boolean('is_absent')->default(false)->after('grade_name');
                $table->boolean('is_exempt')->default(false)->after('is_absent');
                $table->boolean('is_passed')->default(true)->after('is_exempt');
                $table->text('remarks')->nullable()->after('is_passed');
                $table->unsignedInteger('updated_by')->nullable()->after('remarks');

                $table->unique(['exam_id', 'student_id', 'subject_id', 'year'], 'unique_exam_student_subject_year');
            });
        }

        // 4. Extend exam_records table with overall GPA and result status
        if (!Schema::hasColumn('exam_records', 'gpa')) {
            Schema::table('exam_records', function (Blueprint $table) {
                $table->decimal('gpa', 3, 2)->nullable()->after('ave');
                $table->string('grade', 5)->nullable()->after('gpa');
                $table->enum('result_status', ['PASS', 'FAIL', 'WITHHELD', 'EXEMPT', 'PENDING'])->default('PENDING')->after('grade');
                $table->unsignedSmallInteger('attendance_days')->nullable()->after('result_status');
                $table->unsignedSmallInteger('working_days')->nullable()->after('attendance_days');
            });
        }

        // 5. Extend grades table with grade point and GPA calculation bounds
        if (!Schema::hasColumn('grades', 'grade_point')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->decimal('grade_point', 3, 2)->default(0.00)->after('mark_to');
                $table->string('grade_nepali', 20)->nullable()->after('remark');
            });
        }

        // 6. Create mark_audit_trails table
        if (!Schema::hasTable('mark_audit_trails')) {
            Schema::create('mark_audit_trails', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('mark_id');
                $table->unsignedInteger('student_id');
                $table->unsignedInteger('subject_id');
                $table->unsignedInteger('exam_id');
                $table->string('action', 50);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->unsignedInteger('user_id');
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mark_audit_trails');

        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['grade_point', 'grade_nepali']);
        });

        Schema::table('exam_records', function (Blueprint $table) {
            $table->dropColumn(['gpa', 'grade', 'result_status', 'attendance_days', 'working_days']);
        });

        Schema::table('marks', function (Blueprint $table) {
            $table->index('exam_id');
            $table->dropUnique('unique_exam_student_subject_year');
            $table->dropColumn([
                'theory_mark', 'practical_mark', 'total_mark', 'gpa_point', 'grade_name',
                'is_absent', 'is_exempt', 'is_passed', 'remarks', 'updated_by'
            ]);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'theory_max', 'theory_pass', 'practical_max', 'practical_pass',
                'total_max', 'total_pass', 'credit_hour', 'has_practical'
            ]);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropUnique(['name', 'year', 'term']);
            $table->unique(['term', 'year']);
            $table->dropColumn([
                'exam_type', 'start_date', 'end_date', 'start_date_bs', 'end_date_bs',
                'status', 'description', 'published_by', 'published_at'
            ]);
        });
    }
}
